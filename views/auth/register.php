<?php
/** Native registration page does not expose role or identity-key fields. */
use yii\helpers\Html;
$this->title='加入球迷论坛';
?>
<section class="auth-shell"><div class="auth-message"><h1>注册账号</h1><p>选择主队，从这里开始。</p></div><form method="post" class="form-panel"><?= csrf_field() ?><h2>创建账号</h2><?= Html::errorSummary($model,['class'=>'form-errors']) ?><label>用户名<input name="RegisterForm[username]" value="<?= e($model->username) ?>" autocomplete="username" required pattern="[a-zA-Z0-9_]{3,40}" placeholder="3–40 位字母、数字或下划线"></label><label>邮箱<input type="email" name="RegisterForm[email]" value="<?= e($model->email) ?>" autocomplete="email" required maxlength="120"></label><label>球迷昵称<input name="RegisterForm[display_name]" value="<?= e($model->display_name) ?>" maxlength="80"></label><label>密码<input type="password" name="RegisterForm[password]" autocomplete="new-password" required minlength="12" maxlength="128" placeholder="至少 12 个字符"></label><button class="button">注册账号</button><p>已经有账号？<a href="/login">去登录 →</a></p></form></section>
