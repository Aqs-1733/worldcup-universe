<?php
/** A main team is singular; existing followed-team and player tables stay intact. */
declare(strict_types=1);
namespace app\models;

class FanProfile extends Record
{
    public static function tableName(): string { return '{{%fan_profiles}}'; }
    public function rules(): array { return [[['main_team_id'],'default','value'=>null], [['main_team_id'],'integer'], ['main_team_id','exist','targetClass'=>Team::class,'targetAttribute'=>'id'], ['bio','string','max'=>500]]; }
    public function getMainTeam() { return $this->hasOne(Team::class, ['id'=>'main_team_id']); }
}
