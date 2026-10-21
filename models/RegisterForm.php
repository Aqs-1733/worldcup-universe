<?php
/** Registration input cannot mass-assign administrator roles or identity keys. */
declare(strict_types=1);
namespace app\models;

use Yii;
use yii\base\Model;

final class RegisterForm extends Model
{
    public $username = ''; public $email = ''; public $password = ''; public $display_name = '';
    public function rules(): array
    {
        return [
            [['username','email','display_name'],'trim'], [['username','email','password'],'required'],
            ['username','match','pattern'=>'/^[a-zA-Z0-9_]{3,40}$/'], ['username','unique','targetClass'=>User::class],
            ['email','email'], ['email','string','max'=>120], ['email','unique','targetClass'=>User::class],
            ['password','string','min'=>12,'max'=>128], ['display_name','string','max'=>80],
        ];
    }
    public function register(): ?User
    {
        if (!$this->validate()) { return null; }
        return Yii::$app->db->transaction(function () {
            $user = new User();
            $user->setAttributes(['username'=>$this->username,'email'=>$this->email,'display_name'=>$this->display_name]);
            $user->password_hash = password_hash($this->password, PASSWORD_DEFAULT);
            if (!$user->save()) { $this->addErrors($user->getErrors()); return null; }
            $role = Role::findOne(['name'=>'user']);
            if ($role) { Yii::$app->db->createCommand()->insert('user_roles',['user_id'=>$user->id,'role_id'=>$role->id])->execute(); }
            $profile = new FanProfile(['user_id'=>$user->id]); $profile->save(false);
            return $user;
        });
    }
}
