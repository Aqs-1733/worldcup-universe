<?php
/**
 * 文件作用：模型：user_favorite_players 表（用户关注的球员）。
 * 作者：王麒萱_2412096
 */
declare(strict_types=1);
namespace app\models;

class UserFavoritePlayer extends Record
{
    public static function tableName(): string { return '{{%user_favorite_players}}'; }
    public function rules(): array
    {
        return [
            [['user_id'], 'required'],
            [['user_id'], 'integer'],
            [['player_id'], 'required'],
            [['player_id'], 'integer'],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['player_id'], 'exist', 'targetClass' => Player::class, 'targetAttribute' => ['player_id' => 'id']],
        ];
    }

}
