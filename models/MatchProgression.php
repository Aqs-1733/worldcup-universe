<?php
/** Explicit bracket edges: no position-based assumptions or invented matches. */
declare(strict_types=1);
namespace app\models;
class MatchProgression extends Record
{
    public static function tableName(): string { return '{{%match_progressions}}'; }
    public function rules(): array
    {
        return [[['from_match_id','to_match_id','slot','source_url'],'required'], [['from_match_id','to_match_id'],'integer'], ['slot','in','range'=>['home','away']], ['outcome','in','range'=>['winner','loser']], ['source_url','url'], ['source_url','string','max'=>800], ['is_verified','boolean'], ['from_match_id','validateGraph'], [['to_match_id','slot'],'unique','targetAttribute'=>['to_match_id','slot']], [['from_match_id','outcome'],'unique','targetAttribute'=>['from_match_id','outcome']]];
    }
    public function validateGraph(string $field): void
    {
        $from=MatchGame::findOne($this->from_match_id); $to=MatchGame::findOne($this->to_match_id);
        if (!$from || !$to || $from->id==$to->id) { $this->addError($field,'晋级边须连接两场不同的有效比赛。'); return; }
        if ($from->starts_at && $to->starts_at && $from->starts_at >= $to->starts_at) { $this->addError($field,'后续比赛时间必须晚于源比赛。'); }
        $edges=self::find()->andFilterWhere(['<>','id',$this->id])->asArray()->all();
        $queue=[(int)$this->to_match_id]; $seen=[];
        while ($queue) { $node=array_pop($queue); if ($node== (int)$this->from_match_id) { $this->addError($field,'晋级关系不可形成环。'); break; } if (isset($seen[$node])) { continue; } $seen[$node]=true; foreach ($edges as $edge) { if ((int)$edge['from_match_id']===$node) { $queue[]=(int)$edge['to_match_id']; } } }
    }
}
