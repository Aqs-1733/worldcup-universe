<?php
/**
 * 文件作用：审计服务：记录后台和关键操作，不记录密码等敏感信息。
 * 作者：蒋林瀞_2413507
 */
declare(strict_types=1);
namespace app\services;
use Yii;
final class AuditService
{
    public static function record(string $action, string $entity, ?int $id, array $details = []): void
    {
        Yii::$app->db->createCommand()->insert('audit_logs', [
            'user_id' => Yii::$app instanceof \yii\web\Application ? Yii::$app->user->id : null,
            'action'=>$action,'entity_type'=>$entity,'entity_id'=>$id,
            'ip_address'=>Yii::$app instanceof \yii\web\Application ? Yii::$app->request->userIP : null,
            'detail_json'=>new \yii\db\JsonExpression($details),
        ])->execute();
    }
}
