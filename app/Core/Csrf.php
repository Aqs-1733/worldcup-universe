<?php
/**
 * 文件作用：CSRF 令牌工具类，供命令行之外的旧表单辅助函数使用；网页请求的 CSRF 由 Yii2 负责。
 */

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['_token'];
    }

    public static function verify(?string $token): bool
    {
        return is_string($token) && hash_equals(self::token(), $token);
    }

    public static function requireValid(): void
    {
        if (!self::verify($_POST['_token'] ?? null)) {
            http_response_code(419);
            exit('CSRF token mismatch.');
        }
    }
}
