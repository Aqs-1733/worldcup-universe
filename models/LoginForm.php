<?php
/** Login validation with generic errors and bounded cache-backed throttling. */
declare(strict_types=1);
namespace app\models;

use Yii;
use yii\base\Model;

final class LoginForm extends Model
{
    public $username = '';
    public $password = '';
    public $rememberMe = false;
    public function rules(): array { return [[['username','password'], 'required'], ['username','string','max'=>120], ['password','string','max'=>256], ['rememberMe','boolean']]; }
    public function login(): bool
    {
        if (!$this->validate()) { return false; }
        $key = ['login', Yii::$app->request->userIP, mb_strtolower($this->username)];
        $attempts = (int) Yii::$app->cache->get($key);
        if ($attempts >= 8) { $this->addError('password', '尝试次数过多，请十五分钟后重试。'); return false; }
        $user = User::findOne(['username' => $this->username, 'status'=>'active']);
        if (!$user || !$user->validatePassword($this->password)) {
            // Equalize the no-account path without exposing account existence.
            if (!$user) { password_verify($this->password, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'); }
            Yii::$app->cache->set($key, $attempts + 1, 900);
            $this->addError('password', '用户名或密码错误。'); return false;
        }
        if (!$user->auth_key) { $user->auth_key = Yii::$app->security->generateRandomString(48); }
        $user->last_login_at = date('Y-m-d H:i:s'); $user->save(false);
        Yii::$app->cache->delete($key);
        return Yii::$app->user->login($user, $this->rememberMe ? 86400 * 14 : 0);
    }
}
