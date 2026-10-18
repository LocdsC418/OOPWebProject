<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Bangkok');
session_start();

// โหลดคลาสอัตโนมัติจากโฟลเดอร์ src/ ตามชื่อคลาส
spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// ข้อผิดพลาดที่ไม่ได้ดักไว้ (เช่น เชื่อมต่อฐานข้อมูลไม่ได้) แสดงข้อความสุภาพแทนหน้า error ของ PHP
set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    http_response_code(500);
    echo '<!DOCTYPE html><meta charset="UTF-8"><body style="font-family:sans-serif;padding:40px;text-align:center">'
        . '<h2>เกิดข้อผิดพลาดในระบบ</h2><p>กรุณาตรวจสอบการเชื่อมต่อฐานข้อมูลใน src/Database.php แล้วลองใหม่</p>'
        . '<a href="index.php">กลับหน้าแรก</a></body>';
});

/** escape ข้อความก่อนแสดงใน HTML ป้องกัน XSS */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** แสดงวันที่แบบไทย เช่น 18 ต.ค. 2569 */
function thai_date(DateTimeInterface $date): string
{
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . ((int) $date->format('Y') + 543);
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}
