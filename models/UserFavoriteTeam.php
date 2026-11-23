<?php
/**
 * 文件作用：模型：user_favorite_teams 表（用户关注的球队）。
 * 作者：王麒萱_2412096
 */
declare(strict_types=1);
namespace app\models;

class UserFavoriteTeam extends Record
{
    public static function tableName(): string { return '{{%user_favorite_teams}}'; }
    public function rules(): array
    {
        return [
            [['user_id'], 'required'],
            [['user_id'], 'integer'],
            [['team_id'], 'required'],
            [['team_id'], 'integer'],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['team_id'], 'exist', 'targetClass' => Team::class, 'targetAttribute' => ['team_id' => 'id']],
        ];
    }

}
