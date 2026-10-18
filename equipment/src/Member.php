<?php
declare(strict_types=1);

class Member extends Model
{
    public function __construct(
        int $id,
        private string $studentId,
        private string $name,
        private ?string $phone,
        private int $activeLoans = 0,
        private int $overdueLoans = 0
    ) {
        parent::__construct($id);
    }

    protected static function table(): string
    {
        return 'members';
    }

    protected static function fromRow(array $row): static
    {
        return new static(
            (int) $row['id'],
            $row['student_id'],
            $row['name'],
            $row['phone'],
            (int) ($row['active_loans'] ?? 0),
            (int) ($row['overdue_loans'] ?? 0)
        );
    }

    public function getStudentId(): string { return $this->studentId; }
    public function getName(): string      { return $this->name; }
    public function getPhone(): ?string    { return $this->phone; }
    public function getActiveLoans(): int  { return $this->activeLoans; }
    public function getOverdueLoans(): int { return $this->overdueLoans; }

    /** ยืมได้เมื่อไม่มีของค้างเกินกำหนดและยังยืมไม่ครบโควตา */
    public function canBorrow(): bool
    {
        return $this->overdueLoans === 0 && $this->activeLoans < Loan::MAX_ACTIVE_PER_MEMBER;
    }

    /**
     * รายชื่อสมาชิกพร้อมจำนวนที่กำลังยืมและที่เกินกำหนด
     * @return Member[]
     */
    public static function all(): array
    {
        $rows = static::db()->query(
            'SELECT m.*,
                    COUNT(l.id) AS active_loans,
                    COALESCE(SUM(l.due_date < CURDATE()), 0) AS overdue_loans
             FROM members m
             LEFT JOIN loans l ON l.member_id = m.id AND l.returned_at IS NULL
             GROUP BY m.id
             ORDER BY m.student_id'
        )->fetchAll();
        return array_map(fn(array $row) => static::fromRow($row), $rows);
    }

    public static function add(string $studentId, string $name, string $phone): void
    {
        $studentId = trim($studentId);
        $name = trim($name);
        $phone = trim($phone);

        if (!preg_match('/^\d{13}$/', $studentId)) {
            throw new LoanException('รหัสนักศึกษาต้องเป็นตัวเลข 13 หลัก');
        }
        if ($name === '' || !mb_check_encoding($name, 'UTF-8') || mb_strlen($name) > 100) {
            throw new LoanException('กรุณากรอกชื่อ-นามสกุล (ไม่เกิน 100 ตัวอักษร)');
        }
        if ($phone !== '' && !preg_match('/^[0-9-]{9,20}$/', $phone)) {
            throw new LoanException('เบอร์โทรไม่ถูกต้อง');
        }

        $exists = static::db()->prepare('SELECT 1 FROM members WHERE student_id = ?');
        $exists->execute([$studentId]);
        if ($exists->fetchColumn()) {
            throw new LoanException("รหัสนักศึกษา $studentId ลงทะเบียนแล้ว");
        }

        $stmt = static::db()->prepare('INSERT INTO members (student_id, name, phone) VALUES (?, ?, ?)');
        $stmt->execute([$studentId, $name, $phone === '' ? null : $phone]);
    }
}
