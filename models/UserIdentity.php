<?php
/** Yii2 session identity; roles come from the existing user_roles relation. */
declare(strict_types=1);
namespace app\models;

use Yii;
use yii\web\IdentityInterface;

abstract class UserIdentity extends Record implements IdentityInterface
{
    public static function findIdentity($id): ?User { return User::findOne(['id' => $id, 'status' => 'active']); }
    public static function findIdentityByAccessToken($token, $type = null) { return null; }
    public function getId() { return $this->id; }
    public function getAuthKey(): string { return (string) $this->auth_key; }
    public function validateAuthKey($authKey): bool { return $this->auth_key !== '' && hash_equals((string) $this->auth_key, (string) $authKey); }
    public function validatePassword(string $password): bool { return password_verify($password, (string) $this->password_hash); }
    public function getRoles() { return $this->hasMany(Role::class, ['id' => 'role_id'])->viaTable('user_roles', ['user_id' => 'id']); }
    public function getProfile() { return $this->hasOne(FanProfile::class, ['user_id' => 'id']); }
    public function isAdmin(): bool { foreach ($this->roles as $role) { if ($role->name === 'admin') { return true; } } return false; }
    public function getDisplayName(): string { return $this->display_name ?: $this->username; }
    public function fields(): array { return ['id', 'username', 'display_name', 'avatar_url']; }
    public function beforeSave($insert): bool
    {
        if ($insert && !$this->auth_key) { $this->auth_key = Yii::$app->security->generateRandomString(48); }
        return parent::beforeSave($insert);
    }
}
