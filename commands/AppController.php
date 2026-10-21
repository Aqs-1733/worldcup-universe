<?php
/** Deployment and audit commands use the same Yii2 DB component as the website. */
declare(strict_types=1);
namespace app\commands;
use app\models\{User,Role};
use Yii;
use yii\console\Controller;
use yii\db\Connection;
final class AppController extends Controller
{
    public function actionInstall(): int
    {
        $database=(string)env('DB_DATABASE','worldcup_universe');
        if (!preg_match('/^[a-zA-Z0-9_]+$/',$database)) { $this->stderr("Invalid database name.\n"); return 1; }
        $server=new Connection(['dsn'=>'mysql:host='.env('DB_HOST','127.0.0.1').';port='.env('DB_PORT','3306'), 'username'=>env('DB_USERNAME','root'),'password'=>env('DB_PASSWORD',''),'charset'=>'utf8mb4']);
        $sql=(string)file_get_contents(Yii::getAlias('@app/data/install.sql'));
        $sql=str_replace('`worldcup_universe`','`'.$database.'`',$sql);
        $parser=new \app\services\SqlFileService();
        foreach ($parser->statements($sql) as $statement) { $server->createCommand($statement)->execute(); }
        $server->close();
        if (Yii::$app->runAction('migrate/up',['interactive'=>false])!==0) { return 1; }
        $seed=Yii::getAlias('@app/data/sports-seed.sql');
        $emptySports=!(\app\models\Team::find()->exists() || \app\models\Player::find()->exists() || \app\models\MatchGame::find()->exists());
        if(is_file($seed) && $emptySports) { Yii::$app->db->transaction(function()use($parser,$seed) { foreach($parser->statements((string)file_get_contents($seed)) as $statement) { Yii::$app->db->createCommand($statement)->execute(); } }); }
        elseif(!$emptySports) { $this->stdout("Existing sports records detected; bundled seed skipped.\n"); }
        foreach (['admin'=>'管理员','user'=>'普通用户'] as $name=>$label) { Yii::$app->db->createCommand()->upsert('roles',['name'=>$name,'label'=>$label],false)->execute(); }
        foreach (['match'=>'比赛内容','tactics'=>'战术分析','team'=>'球队动态','fan'=>'球迷内容','entertainment'=>'娱乐内容','fanmade'=>'二创内容'] as $slug=>$name) { Yii::$app->db->createCommand()->upsert('tags',['slug'=>$slug,'name_cn'=>$name],false)->execute(); }
        $this->stdout("Schema and native Yii2 migrations installed; existing records preserved.\n");
        $result=$this->actionAdmin(); if($result===0) { $this->actionWelcome(); } return $result;
    }
    public function actionAdmin(): int
    {
        $username=(string)env('ADMIN_USERNAME','admin');
        if (User::find()->where(['username'=>$username])->exists()) { $this->stdout("Administrator already exists; password unchanged.\n"); return 0; }
        $password=(string)env('ADMIN_PASSWORD','');
        if (strlen($password)<12) { $this->stderr("Set ADMIN_PASSWORD (at least 12 characters), then run php yii app/admin.\n"); return 1; }
        Yii::$app->db->transaction(function () use ($username,$password) {
            $user=new User(['username'=>$username,'email'=>env('ADMIN_EMAIL','admin@example.com'),'display_name'=>'管理员']);
            $user->password_hash=password_hash($password,PASSWORD_DEFAULT);
            if (!$user->save()) { throw new \RuntimeException(json_encode($user->errors,JSON_UNESCAPED_UNICODE)); }
            $role=Role::findOne(['name'=>'admin']); Yii::$app->db->createCommand()->insert('user_roles',['user_id'=>$user->id,'role_id'=>$role->id])->execute();
        });
        $this->stdout("Administrator created.\n"); return 0;
    }
    public function actionAudit(): int
    {
        $counts=[]; foreach (Yii::$app->db->schema->getTableNames() as $table) { $counts[$table]=(int)(new \yii\db\Query())->from($table)->count('*',Yii::$app->db); }
        $coverage=[]; foreach (['players'=>'photo_url','teams'=>'flag_url','team_members'=>'photo_url'] as $table=>$field) { $coverage[$table]=['total'=>$counts[$table]??0,'nonempty'=>(int)(new \yii\db\Query())->from($table)->where(['not',[$field=>null]])->andWhere(['<>',$field,''])->count()]; }
        $this->stdout(json_encode(['tables'=>$counts,'media_field_coverage'=>$coverage,'note'=>'URL nonempty counts do not establish accessibility or licence.'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n"); return 0;
    }
    public function actionWelcome(): int
    {
        $admin=User::findOne(['username'=>env('ADMIN_USERNAME','admin')]);
        $category=\app\models\ForumCategory::findOne(['slug'=>'worldcup']);
        if(!$admin || !$admin->isAdmin() || !$category) { return 1; }
        $title='论坛使用说明';
        if(\app\models\ForumThread::find()->where(['author_id'=>$admin->id,'title'=>$title])->exists()) { return 0; }
        $thread=(new \app\services\ForumService())->saveThread($admin,['title'=>$title,'body'=>'在个人中心选择主队，关注球队与球员。发布主题时可关联球队和比赛，回复、点赞与收藏会保留在你的账号中。请理性讨论，遇到违规内容可提交举报。','category_id'=>$category->id]);
        if($thread->hasErrors()) { return 1; } (new \app\services\ForumService())->moderate($admin,'thread',(int)$thread->id,'pin');
        $this->stdout("Forum welcome guide published.\n"); return 0;
    }
    public function actionDictionary(): int
    {
        $text="# WorldCup Universe V2｜数据库字典\n\n从本次迁移后的实际 MySQL schema 导出。空白来源不推断为已核验。\n\n";
        foreach(Yii::$app->db->schema->getTableNames() as $name) {
            $table=Yii::$app->db->schema->getTableSchema($name);
            $text.="## `{$name}`\n\n主键：`".implode('`,`',$table->primaryKey)."`。\n\n字段 | 类型 | 可空 | 默认值 | 注释\n---|---|---|---|---\n";
            foreach($table->columns as $col) { $default=$col->defaultValue===null?'NULL':(string)$col->defaultValue; $text.='`'.$col->name.'` | `'.$col->dbType.'` | '.($col->allowNull?'是':'否').' | '.str_replace('|','\\|',$default).' | '.str_replace('|','\\|',$col->comment?:'—')."\n"; }
            foreach($table->foreignKeys as $fk) { $target=array_shift($fk); $text.="\n外键：`".implode('`,`',array_keys($fk))."` → `{$target}` (`".implode('`,`',array_values($fk))."`)。\n"; }
            $text.="\n";
        }
        file_put_contents(Yii::getAlias('@app/docs/v2-database-dictionary.md'),$text); $this->stdout("Database dictionary generated.\n"); return 0;
    }
}
