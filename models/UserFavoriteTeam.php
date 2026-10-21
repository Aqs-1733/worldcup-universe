<?php
/** Native Yii2 ActiveRecord: user_favorite_teams; field validation and relational integrity. */
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
