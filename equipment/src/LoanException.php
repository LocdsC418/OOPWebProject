<?php
declare(strict_types=1);

/**
 * Exception เฉพาะของระบบ ใช้แจ้งข้อผิดพลาดทางธุรกิจ เช่น อุปกรณ์ไม่ว่าง
 * ข้อความในนี้แสดงให้ผู้ใช้เห็นได้โดยตรง
 */
class LoanException extends RuntimeException
{
}
