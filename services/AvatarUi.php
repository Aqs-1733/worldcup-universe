<?php
/** Renders local or approved user avatar paths without trusting arbitrary markup. */
declare(strict_types=1);
namespace app\services;
use app\models\User;
use yii\helpers\Html;
final class AvatarUi
{
    public static function render(?User $user,string $classes=''): string
    {
        $name=$user?->displayName??'球迷';
        $class=trim('avatar avatar-photo '.$classes);
        $url=safe_url($user?->avatar_url);
        if ($url!=='#') { return '<span class="'.Html::encode($class).'"><img src="'.Html::encode($url).'" alt="'.Html::encode($name).'的头像"></span>'; }
        return '<span class="'.Html::encode($class).'" aria-label="'.Html::encode($name).'">'.Html::encode(mb_substr($name,0,1)).'</span>';
    }
}
