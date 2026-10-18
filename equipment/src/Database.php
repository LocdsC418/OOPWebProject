<?php
declare(strict_types=1);

/**
 * Singleton: สร้างการเชื่อมต่อ PDO เพียงครั้งเดียวแล้วใช้ซ้ำทั้งระบบ
 */
final class Database
{
    // แก้ค่าให้ตรงกับเครื่อง (XAMPP ค่าเริ่มต้นคือ root / ไม่มีรหัสผ่าน)
    private const HOST = '127.0.0.1';
    private const PORT = '3306';
    private const NAME = 'equipment_lending';
    private const USER = 'root';
    private const PASS = '';

    private static ?PDO $pdo = null;

    // ห้ามสร้างออบเจ็กต์จากภายนอก
    private function __construct() {}

    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: self::HOST,
                getenv('DB_PORT') ?: self::PORT,
                getenv('DB_NAME') ?: self::NAME
            );
            self::$pdo = new PDO($dsn, getenv('DB_USER') ?: self::USER, getenv('DB_PASS') ?: self::PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // ให้เวลาใน MySQL ตรงกับเวลาของ PHP (Asia/Bangkok)
            self::$pdo->exec("SET time_zone = '+07:00'");
        }
        return self::$pdo;
    }
}
