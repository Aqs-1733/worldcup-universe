<?php
/**
 * 文件作用：模型：球迷资料（主队只能有一支，关注的球队/球员可以多个）。
 * 作者：王麒萱_2412096
 */
declare(strict_types=1);
namespace app\models;

class FanProfile extends Record
{
    public static function tableName(): string { return '{{%fan_profiles}}'; }
    // 主队可以为空；不为空时必须是存在的球队
    public function rules(): array { return [[['main_team_id'],'default','value'=>null], [['main_team_id'],'integer'], ['main_team_id','exist','targetClass'=>Team::class,'targetAttribute'=>'id'], ['bio','string','max'=>500]]; }
    // 关联：主队
    public function getMainTeam() { return $this->hasOne(Team::class, ['id'=>'main_team_id']); }
}
