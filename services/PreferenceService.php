<?php
/** One transaction replaces a member's main team and followed/blocked entities. */
declare(strict_types=1);
namespace app\services;
use app\models\{FanProfile,Team,Player,User};
use Yii;
use yii\web\BadRequestHttpException;
final class PreferenceService
{
    public function save(User $actor,array $input): FanProfile
    {
        return Yii::$app->db->transaction(function () use ($actor,$input) {
            $profile=FanProfile::findOne($actor->id)??new FanProfile(); $profile->user_id=$actor->id;
            $profile->setAttributes(array_intersect_key($input,array_flip(['main_team_id','bio'])));
            if (!$profile->validate()) { return $profile; }
            $full=($input['replace_preferences']??null)==='1';
            $current=fn($table,$field)=>(new \yii\db\Query())->select($field)->from($table)->where(['user_id'=>$actor->id])->column();
            $teamIds=$this->ids($input['teams']??($full?[]:$current('user_favorite_teams','team_id')),Team::class); $playerIds=$this->ids($input['players']??($full?[]:$current('user_favorite_players','player_id')),Player::class); $blocked=$this->ids($input['blocked_teams']??($full?[]:$current('user_blocked_teams','team_id')),Team::class);
            if ($profile->main_team_id) { $teamIds[]= (int)$profile->main_team_id; }
            $teamIds=array_unique($teamIds);
            if (array_intersect($teamIds,$blocked)) { $profile->addError('main_team_id','主队/关注球队与屏蔽球队不可重叠。'); return $profile; }
            $profile->save(false);
            foreach (['user_favorite_teams'=>['team_id',$teamIds],'user_favorite_players'=>['player_id',$playerIds],'user_blocked_teams'=>['team_id',$blocked]] as $table=>[$field,$ids]) {
                Yii::$app->db->createCommand()->delete($table,['user_id'=>$actor->id])->execute();
                foreach ($ids as $id) { Yii::$app->db->createCommand()->insert($table,['user_id'=>$actor->id,$field=>$id])->execute(); }
            }
            $existing=(new \yii\db\Query())->from('fan_preferences')->where(['user_id'=>$actor->id])->one();
            $prefs=['user_id'=>$actor->id]; foreach (['match','tactics','team','fanmade','entertainment'] as $kind) { $field='push_'.$kind; $prefs[$field]=array_key_exists($field,$input)||$full?(empty($input[$field])?0:1):($existing[$field]??1); }
            Yii::$app->db->createCommand()->upsert('fan_preferences',$prefs)->execute();
            $actor->survey_completed_at=date('Y-m-d H:i:s'); $actor->save(false);
            AuditService::record('profile.update','fan_profiles',(int)$actor->id); $profile->refresh(); return $profile;
        });
    }
    private function ids($value,string $class): array
    {
        if (!is_array($value) || count($value)>100) { throw new BadRequestHttpException('偏好列表格式错误。'); }
        $ids=[]; foreach($value as $id) { $n=filter_var($id,FILTER_VALIDATE_INT); if (!$n || $n<1) { throw new BadRequestHttpException('偏好包含无效 ID。'); } $ids[]=$n; }
        $ids=array_values(array_unique($ids)); if ($ids && (int)$class::find()->where(['id'=>$ids])->count()!==count($ids)) { throw new BadRequestHttpException('偏好包含不存在的对象。'); } return $ids;
    }
}
