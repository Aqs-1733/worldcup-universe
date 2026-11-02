<?php
/** Register the home page's built-in images as 媒体资产 so administrators can replace them; existing rows are left alone. */
declare(strict_types=1);
use yii\db\Migration;

final class m261103_000001_home_media_slots extends Migration
{
    private const SLOTS=[
        'home-stadium'=>['/assets/media/stadium-hero-v2.png','灯光下座无虚席的足球场','https://github.com/Aqs-1733/worldcup-universe','项目自制设计稿','WorldCup Universe 设计稿（首页第二幕背景）'],
        'home-final'=>['/assets/media/photos/display/metlife.jpg','纽约／新泽西体育场','https://commons.wikimedia.org/wiki/File:MetLife_Stadium_2022.jpg','CC BY 4.0','Thecoolone1223; Wikimedia Commons; CSS crop and overlay'],
    ];

    public function safeUp()
    {
        foreach (self::SLOTS as $key=>[$path,$alt,$source,$license,$credit]) {
            if ((new \yii\db\Query())->from('{{%media_assets}}')->where(['asset_key'=>$key])->exists($this->db)) { continue; }
            $this->insert('{{%media_assets}}',['asset_key'=>$key,'path'=>$path,'alt_text'=>$alt,'source_url'=>$source,'license_name'=>$license,'credit'=>$credit,'status'=>'approved']);
        }
    }

    public function safeDown()
    {
        // Only rows still pointing at the built-in file are ours to remove; an administrator's replacement is kept.
        foreach (self::SLOTS as $key=>[$path]) { $this->delete('{{%media_assets}}',['asset_key'=>$key,'path'=>$path]); }
    }
}
