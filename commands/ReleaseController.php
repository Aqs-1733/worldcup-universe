<?php
/** Prepare verified display metadata for the course release; no human contribution claims. */
declare(strict_types=1);
namespace app\commands;
final class ReleaseController extends \yii\console\Controller
{
    public function actionMetadata(): int
    {
        $members=[['蒋林瀞','2413507','密码与网络空间安全学院','队长'],['崔迪','2412439','计算机学院','项目成员'],['梁靖泽','2412852','计算机学院','项目成员'],['王麒萱','2412096','密码与网络空间安全学院','项目成员']];
        foreach($members as $i=>[$name,$no,$school,$role]){$m=\app\models\TeamMember::findOne(['student_no'=>$no])??new \app\models\TeamMember();$m->setAttributes(['name'=>$name,'student_no'=>$no,'role_name'=>$role,'bio'=>$school.' · '.$no,'sort_order'=>$i+1,'is_visible'=>1]);if(!$m->save())throw new \RuntimeException(json_encode($m->errors,JSON_UNESCAPED_UNICODE));}
        $cn=['BC Place Vancouver'=>'温哥华卑诗体育场','Mexico City Stadium'=>'墨西哥城体育场','Guadalajara Stadium'=>'瓜达拉哈拉体育场','Toronto Stadium'=>'多伦多体育场','Los Angeles Stadium'=>'洛杉矶体育场','San Francisco Bay Area Stadium'=>'旧金山湾区体育场','New York/New Jersey Stadium'=>'纽约／新泽西体育场','Boston Stadium'=>'波士顿体育场','Houston Stadium'=>'休斯敦体育场','Dallas Stadium'=>'达拉斯体育场','Philadelphia Stadium'=>'费城体育场','Monterrey Stadium'=>'蒙特雷体育场','Atlanta Stadium'=>'亚特兰大体育场','Seattle Stadium'=>'西雅图体育场','Miami Stadium'=>'迈阿密体育场','Kansas City Stadium'=>'堪萨斯城体育场'];
        foreach($cn as $original=>$name)\Yii::$app->db->createCommand()->update('venues',['name_cn'=>$name],['name_original'=>$original])->execute();
        $labels=json_decode(file_get_contents(ROOT_PATH.'/data/player-name-labels.json'),true,512,JSON_THROW_ON_ERROR);
        foreach(\app\models\Player::find()->where(['not',['official_import_id'=>null]])->each(100) as $player){$key=strtolower(iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$player->name_original));$key=trim(preg_replace('/[^a-z0-9]+/',' ',$key));if(isset($labels[$key]))\Yii::$app->db->createCommand()->update('players',['name_cn'=>$labels[$key]],['id'=>$player->id])->execute();}
        \Yii::$app->db->createCommand()->update('media_assets',['path'=>'/assets/media/photos/display/azteca.jpg','alt_text'=>'阿兹特克体育场全景','source_url'=>'https://commons.wikimedia.org/wiki/File:Panor%C3%A1mica_Estadio_Azteca.jpg','license_name'=>'CC BY-SA 4.0','credit'=>'Luicheto; MediaWiki 1280px preview; CSS crop and overlay','status'=>'approved'],['asset_key'=>'home-hero'])->execute();
        $this->stdout("PASS display metadata: 4 documented members; 16 translated venue labels; no invented contributions.\n");return 0;
    }
}
