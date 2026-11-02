<?php
/** Native Yii2 administrator CRUD with allowlisted models and immutable audit log. */
declare(strict_types=1);
namespace app\controllers;
use app\models\{User,AuditLog,ForumReport};
use app\services\{AuditService,ForumService,MediaUploadService};
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\{NotFoundHttpException,BadRequestHttpException,UploadedFile};
final class AdminController extends BaseController
{
    public $layout='admin';
    public function behaviors(): array { return ['access'=>['class'=>AccessControl::class,'rules'=>[['allow'=>true,'roles'=>['@'],'matchCallback'=>fn()=>Yii::$app->user->identity->isAdmin()]]]]; }
    public function actionIndex(): string
    {
        $counts=[]; foreach ($this->registry() as $table=>[$label,$class]) { $counts[$table]=['label'=>$label,'count'=>(int)$class::find()->count()]; }
        return $this->render('/admin-v2/index',['counts'=>$counts,'reports'=>ForumReport::find()->where(['status'=>'open'])->orderBy(['id'=>SORT_DESC])->limit(10)->all()]);
    }
    public function actionTable(string $table): string
    {
        [$label,$class]=$this->entry($table); $q=$class::find()->orderBy(['id'=>SORT_DESC]);
        if ($search=Yii::$app->request->get('q')) { $conditions=['or']; foreach ($class::getTableSchema()->columns as $col) { if (in_array($col->type,['string','text'],true) && !str_contains($col->name,'json')) { $conditions[]=['like',$col->name,mb_substr((string)$search,0,120)]; } } if(count($conditions)>1) { $q->andWhere($conditions); } }
        return $this->render('/admin-v2/table',['table'=>$table,'label'=>$label,'provider'=>new ActiveDataProvider(['query'=>$q,'pagination'=>['pageSize'=>25],'sort'=>false]),'readonly'=>in_array($table,['forum_threads','forum_replies','forum_reports'],true),'media'=>$this->mediaFields($table)]);
    }
    public function actionCreate(string $table) { return $this->edit($table,null); }
    public function actionEdit(string $table,?int $id=null) { return $this->edit($table,$id??(int)Yii::$app->request->get('id')); }
    public function actionSave(string $table) { $this->posted(); return $this->edit($table,empty(Yii::$app->request->post('id'))?null:(int)Yii::$app->request->post('id')); }
    public function actionDelete(string $table)
    {
        $this->posted(); [$label,$class]=$this->entry($table); if(in_array($table,['forum_threads','forum_replies','forum_reports'],true)) { throw new BadRequestHttpException('论坛内容请使用审核操作。'); }
        $id=(int)Yii::$app->request->post('id'); $model=$class::findOne($id); if(!$model) { throw new NotFoundHttpException(); }
        try { Yii::$app->db->transaction(function()use($model,$id,$table) { $model->delete(); AuditService::record('admin.delete',$table,$id); }); Yii::$app->session->setFlash('success','记录已删除。'); }
        catch(\yii\db\IntegrityException $e) { Yii::$app->session->setFlash('error','该记录仍被其他数据引用，请先处理关联记录。'); }
        return $this->redirect('/admin/'.$table);
    }
    /** Administrators can open any registered coursework file, including ones not yet visible to the public. */
    public function actionFile(string $table)
    {
        if($table!=='coursework_files') { throw new NotFoundHttpException(); }
        $file=\app\models\CourseworkFile::findOne((int)Yii::$app->request->get('id')); if(!$file || !($path=$file->resolvedPath())) { throw new NotFoundHttpException('文件不存在。'); }
        return Yii::$app->response->sendFile($path,basename($path),['inline'=>false]);
    }
    public function actionModerate()
    {
        $this->posted(); (new ForumService())->moderate($this->actor(),(string)Yii::$app->request->post('type'),(int)Yii::$app->request->post('id'),(string)Yii::$app->request->post('operation'));
        Yii::$app->session->setFlash('success','审核操作已保存并写入审计日志。'); return $this->redirect('/admin');
    }
    public function actionAudit(): string { return $this->render('/admin-v2/audit',['provider'=>new ActiveDataProvider(['query'=>AuditLog::find()->orderBy(['id'=>SORT_DESC]),'pagination'=>['pageSize'=>30],'sort'=>false])]); }
    public function actionUsers(): string { return $this->render('/admin-v2/users',['provider'=>new ActiveDataProvider(['query'=>User::find()->with('roles')->orderBy(['id'=>SORT_DESC]),'pagination'=>['pageSize'=>30],'sort'=>false])]); }
    public function actionUserStatus(int $id)
    {
        $this->posted(); $actor=$this->actor();
        if((int)$actor->id===$id) { throw new BadRequestHttpException('当前登录管理员不可停用自己。'); }
        $status=Yii::$app->request->post('status'); if(!in_array($status,['active','disabled'],true)) { throw new BadRequestHttpException(); }
        Yii::$app->db->transaction(function()use($id,$status) {
            $user=User::findOne($id); if(!$user) { throw new NotFoundHttpException(); }
            if($user->isAdmin()) { throw new BadRequestHttpException('管理员账号状态须由部署管理员通过命令行维护。'); }
            $user->status=$status; $user->auth_key=Yii::$app->security->generateRandomString(48); $user->save(false); AuditService::record('user.'.$status,'users',$id);
        });
        return $this->redirect('/admin/users');
    }
    private function edit(string $table,?int $id)
    {
        [$label,$class]=$this->entry($table); if(in_array($table,['forum_threads','forum_replies','forum_reports'],true)) { throw new BadRequestHttpException('论坛作者信息不可由通用表单覆盖，请使用审核操作。'); }
        $model=$id?$class::findOne($id):new $class(); if(!$model) { throw new NotFoundHttpException(); }
        if($model->isNewRecord) { $model->loadDefaultValues(); }
        if($table==='news_articles' && !Yii::$app->request->isPost) { $model->tagIds=array_map(fn($tag)=>(int)$tag->id,$model->tags); }
        $media=$this->mediaFields($table);
        if(Yii::$app->request->isPost) {
            $payload=Yii::$app->request->post('Record',[]); if(!is_array($payload)) { throw new BadRequestHttpException(); }
            if($table==='news_articles') { $payload['tagIds']=Yii::$app->request->post('tag_ids',[]); }
            $model->setAttributes($payload);
            if($table==='admin_posts' && !$id) { $model->author_id=$this->actor()->id; }
            // A browser upload replaces whatever URL/path was typed for the same field.
            $uploader=new MediaUploadService(); $stored=[]; $uploadErrors=[];
            foreach($media as $field=>$kinds) {
                $file=UploadedFile::getInstanceByName('upload['.$field.']'); if(!$file || $file->error===UPLOAD_ERR_NO_FILE) { continue; }
                try { $value=$kinds===['coursework']?$uploader->storeCoursework($file,(string)$model->scope):$uploader->storePublic($file,$kinds); $stored[$field]=$value; $this->setMediaValue($model,$field,$value); }
                catch(\InvalidArgumentException|\RuntimeException $e) { $uploadErrors[$field]=$e->getMessage(); }
            }
            if($model->validate() && !$uploadErrors) {
                try { Yii::$app->db->transaction(function()use($model,$table,$id,$stored) { $changed=array_keys($model->getDirtyAttributes()); if(!$model->save(false)) {throw new \DomainException(implode(' ',$model->getFirstErrors())?:'记录未保存。');} if($table==='news_articles') { Yii::$app->db->createCommand()->delete('news_article_tags',['article_id'=>$model->id])->execute(); foreach(array_unique($model->tagIds) as $tagId) { Yii::$app->db->createCommand()->insert('news_article_tags',['article_id'=>$model->id,'tag_id'=>$tagId])->execute(); } } AuditService::record($id?'admin.update':'admin.create',$table,(int)$model->id,['fields'=>$changed]+($stored?['uploads'=>$stored]:[])); }); Yii::$app->session->setFlash('success',$stored?'已保存，上传文件已生效，前台读取同一 MySQL 数据。':'已保存，前台读取同一 MySQL 数据。'); return $this->redirect('/admin/'.$table); }
                catch(\yii\db\IntegrityException $e) { $model->addError('id','记录重复或关联条件未满足。'); }
                catch(\DomainException $e) { $model->addError('source_url',$e->getMessage()); }
            }
            foreach($uploadErrors as $field=>$message) { $model->addError($field,$message); }
            // The record was not saved, so files stored during this request would be orphans.
            foreach($stored as $value) { $uploader->discard($value); }
            Yii::$app->response->statusCode=422;
        }
        return $this->render('/admin-v2/edit',['table'=>$table,'label'=>$label,'model'=>$model,'tags'=>$table==='news_articles'?\app\models\Tag::find()->all():[],'media'=>$media]);
    }
    /** @return array<string,list<string>> upload-capable fields of one table */
    private function mediaFields(string $table): array { return (require Yii::getAlias('@app/config/media-fields.php'))[$table]??[]; }
    private function setMediaValue($model,string $field,string $value): void
    {
        if($field==='image' && $model instanceof \app\models\NewsArticle) {
            $raw=$model->raw_payload_json; if($raw instanceof \yii\db\JsonExpression) { $raw=$raw->getValue(); } elseif(is_string($raw)) { $raw=json_decode($raw,true); }
            $raw=is_array($raw)?$raw:[]; $raw['image']=$value; $model->raw_payload_json=$raw; return;
        }
        $model->$field=$value;
    }
    private function registry(): array { return require Yii::getAlias('@app/config/v2-tables.php'); }
    private function entry(string $table): array { $all=$this->registry(); if(!isset($all[$table])) { throw new NotFoundHttpException(); } return $all[$table]; }
}
