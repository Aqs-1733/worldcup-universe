<?php
/** Native login form renders Yii validation feedback and CSRF token. */
use yii\helpers\Html;
$this->title='账号登录';
?>
<section class="auth-shell"><div class="auth-message"><h1>账号登录</h1><p>登录后可选择主队、关注球员和参与论坛讨论。</p></div><form method="post" class="form-panel"><?= csrf_field() ?><h2>登录账号</h2><?= Html::errorSummary($model,['class'=>'form-errors']) ?><label>用户名<input name="LoginForm[username]" value="<?= e($model->username) ?>" autocomplete="username" required maxlength="120"></label><label>密码<input type="password" name="LoginForm[password]" autocomplete="current-password" required maxlength="256"></label><label class="choice"><input type="checkbox" name="LoginForm[rememberMe]" value="1"> 两周内记住我</label><button class="button">登录</button><p>还没有账号？<a href="/register">注册账号 →</a></p></form></section>
