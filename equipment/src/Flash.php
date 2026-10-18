<?php
declare(strict_types=1);

/**
 * ข้อความแจ้งเตือนครั้งเดียว (เก็บใน session แล้วแสดงหลัง redirect)
 */
class Flash
{
    public static function success(string $message): void
    {
        $_SESSION['flash'] = ['type' => 'success', 'message' => $message];
    }

    public static function error(string $message): void
    {
        $_SESSION['flash'] = ['type' => 'error', 'message' => $message];
    }

    public static function take(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
