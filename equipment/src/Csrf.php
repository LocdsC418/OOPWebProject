<?php
declare(strict_types=1);

/**
 * ป้องกัน CSRF: ทุกฟอร์ม POST ต้องแนบ token ที่ตรงกับใน session
 */
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf" value="' . self::token() . '">';
    }

    public static function verify(): void
    {
        if (!hash_equals(self::token(), (string) ($_POST['csrf'] ?? ''))) {
            http_response_code(400);
            exit('Invalid CSRF token');
        }
    }
}
