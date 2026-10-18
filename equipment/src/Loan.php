<?php
declare(strict_types=1);

class Loan extends Model
{
    public const LOAN_DAYS = 7;              // จำนวนวันยืมเริ่มต้น
    public const MAX_DAYS = 14;              // ยืมได้นานสุด
    public const MAX_ACTIVE_PER_MEMBER = 3;  // ยืมพร้อมกันได้ไม่เกิน
    public const FINE_PER_DAY = 20;          // ค่าปรับต่อวัน (บาท)

    public function __construct(
        int $id,
        private int $equipmentId,
        private string $equipmentCode,
        private string $equipmentName,
        private int $memberId,
        private string $memberName,
        private DateTimeImmutable $borrowedAt,
        private DateTimeImmutable $dueDate,
        private ?DateTimeImmutable $returnedAt,
        private float $fine
    ) {
        parent::__construct($id);
    }

    protected static function table(): string
    {
        return 'loans';
    }

    protected static function fromRow(array $row): static
    {
        return new static(
            (int) $row['id'],
            (int) $row['equipment_id'],
            $row['equipment_code'],
            $row['equipment_name'],
            (int) $row['member_id'],
            $row['member_name'],
            new DateTimeImmutable($row['borrowed_at']),
            new DateTimeImmutable($row['due_date']),
            $row['returned_at'] !== null ? new DateTimeImmutable($row['returned_at']) : null,
            (float) $row['fine']
        );
    }

    /** SELECT พื้นฐานที่ JOIN ชื่ออุปกรณ์และชื่อสมาชิกมาด้วย */
    private static function baseQuery(): string
    {
        return 'SELECT l.*, e.code AS equipment_code, e.name AS equipment_name, m.name AS member_name
                FROM loans l
                JOIN equipment e ON e.id = l.equipment_id
                JOIN members m ON m.id = l.member_id';
    }

    // Override find() ของคลาสแม่ เพราะต้อง JOIN ตารางอื่นด้วย
    public static function find(int $id): ?static
    {
        $stmt = static::db()->prepare(self::baseQuery() . ' WHERE l.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? static::fromRow($row) : null;
    }

    public function getEquipmentId(): int             { return $this->equipmentId; }
    public function getEquipmentCode(): string        { return $this->equipmentCode; }
    public function getEquipmentName(): string        { return $this->equipmentName; }
    public function getMemberId(): int                { return $this->memberId; }
    public function getMemberName(): string           { return $this->memberName; }
    public function getBorrowedAt(): DateTimeImmutable { return $this->borrowedAt; }
    public function getDueDate(): DateTimeImmutable    { return $this->dueDate; }
    public function getReturnedAt(): ?DateTimeImmutable { return $this->returnedAt; }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    /** จำนวนวันที่เกินกำหนด นับถึงวันที่คืน หรือถึงวันนี้ถ้ายังไม่คืน */
    public function getOverdueDays(): int
    {
        return self::daysLate($this->dueDate, $this->returnedAt ?? new DateTimeImmutable());
    }

    public function isOverdue(): bool
    {
        return !$this->isReturned() && $this->getOverdueDays() > 0;
    }

    /** คืนแล้วใช้ค่าปรับที่บันทึกไว้ ยังไม่คืนคำนวณค่าปรับสะสมถึงวันนี้ */
    public function getFine(): float
    {
        return $this->isReturned() ? $this->fine : $this->getOverdueDays() * self::FINE_PER_DAY;
    }

    private static function daysLate(DateTimeImmutable $due, DateTimeImmutable $at): int
    {
        $due = $due->setTime(0, 0);
        $at = $at->setTime(0, 0);
        return $at > $due ? $due->diff($at)->days : 0;
    }

    /**
     * ยืมอุปกรณ์ 1 ชิ้น
     * ใช้ Transaction + SELECT ... FOR UPDATE ล็อกแถวสมาชิกและอุปกรณ์
     * เพื่อกันการยืมซ้อนจนจำนวนคงเหลือติดลบ
     */
    public static function borrow(int $equipmentId, int $memberId, int $days = self::LOAN_DAYS): DateTimeImmutable
    {
        if ($days < 1 || $days > self::MAX_DAYS) {
            throw new LoanException('ระยะเวลายืมต้องอยู่ระหว่าง 1 ถึง ' . self::MAX_DAYS . ' วัน');
        }

        $db = static::db();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT name FROM members WHERE id = ? FOR UPDATE');
            $stmt->execute([$memberId]);
            $memberName = $stmt->fetchColumn();
            if ($memberName === false) {
                throw new LoanException('กรุณาเลือกผู้ยืม');
            }

            $stmt = $db->prepare(
                'SELECT COUNT(*) AS active, COALESCE(SUM(due_date < CURDATE()), 0) AS overdue
                 FROM loans WHERE member_id = ? AND returned_at IS NULL'
            );
            $stmt->execute([$memberId]);
            $status = $stmt->fetch();
            if ((int) $status['overdue'] > 0) {
                throw new LoanException("$memberName มีอุปกรณ์ค้างเกินกำหนด ต้องคืนก่อนจึงจะยืมใหม่ได้");
            }
            if ((int) $status['active'] >= self::MAX_ACTIVE_PER_MEMBER) {
                throw new LoanException("$memberName ยืมครบ " . self::MAX_ACTIVE_PER_MEMBER . ' ชิ้นแล้ว');
            }

            $stmt = $db->prepare('SELECT name, available_qty FROM equipment WHERE id = ? FOR UPDATE');
            $stmt->execute([$equipmentId]);
            $equipment = $stmt->fetch();
            if ($equipment === false) {
                throw new LoanException('ไม่พบอุปกรณ์');
            }
            if ((int) $equipment['available_qty'] < 1) {
                throw new LoanException("{$equipment['name']} ไม่ว่าง ถูกยืมครบทุกชิ้นแล้ว");
            }

            $now = new DateTimeImmutable();
            $due = $now->modify("+$days days");

            $db->prepare('INSERT INTO loans (equipment_id, member_id, borrowed_at, due_date) VALUES (?, ?, ?, ?)')
               ->execute([$equipmentId, $memberId, $now->format('Y-m-d H:i:s'), $due->format('Y-m-d')]);
            $db->prepare('UPDATE equipment SET available_qty = available_qty - 1 WHERE id = ?')
               ->execute([$equipmentId]);

            $db->commit();
            return $due;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * คืนอุปกรณ์ บันทึกวันคืน คิดค่าปรับ และเพิ่มจำนวนคงเหลือกลับ
     * ตรวจ returned_at IS NULL เพื่อกันการคืนซ้ำ
     */
    public static function returnLoan(int $loanId): Loan
    {
        $db = static::db();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT equipment_id, due_date FROM loans WHERE id = ? AND returned_at IS NULL FOR UPDATE');
            $stmt->execute([$loanId]);
            $loan = $stmt->fetch();
            if ($loan === false) {
                throw new LoanException('รายการนี้ถูกคืนไปแล้วหรือไม่มีอยู่');
            }

            $now = new DateTimeImmutable();
            $fine = self::daysLate(new DateTimeImmutable($loan['due_date']), $now) * self::FINE_PER_DAY;

            $db->prepare('UPDATE loans SET returned_at = ?, fine = ? WHERE id = ?')
               ->execute([$now->format('Y-m-d H:i:s'), $fine, $loanId]);
            $db->prepare('UPDATE equipment SET available_qty = available_qty + 1 WHERE id = ?')
               ->execute([$loan['equipment_id']]);

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
        return static::find($loanId);
    }

    /** @return Loan[] รายการที่ยังไม่คืน เรียงตามกำหนดคืน */
    public static function active(): array
    {
        $rows = static::db()->query(self::baseQuery() . ' WHERE l.returned_at IS NULL ORDER BY l.due_date, l.id')->fetchAll();
        return array_map(fn(array $row) => static::fromRow($row), $rows);
    }

    /** @return Loan[] ประวัติทั้งหมด ล่าสุดก่อน */
    public static function history(int $limit = 100): array
    {
        $stmt = static::db()->prepare(self::baseQuery() . ' ORDER BY l.borrowed_at DESC, l.id DESC LIMIT ?');
        $stmt->execute([$limit]);
        return array_map(fn(array $row) => static::fromRow($row), $stmt->fetchAll());
    }

    public static function overdueCount(): int
    {
        return (int) static::db()->query(
            'SELECT COUNT(*) FROM loans WHERE returned_at IS NULL AND due_date < CURDATE()'
        )->fetchColumn();
    }

    public static function totalFines(): float
    {
        return (float) static::db()->query('SELECT COALESCE(SUM(fine), 0) FROM loans')->fetchColumn();
    }

    /** อุปกรณ์ที่ถูกยืมบ่อยที่สุด */
    public static function topEquipment(int $limit = 5): array
    {
        $stmt = static::db()->prepare(
            'SELECT e.code, e.name, COUNT(*) AS times
             FROM loans l JOIN equipment e ON e.id = l.equipment_id
             GROUP BY e.id ORDER BY times DESC, e.code LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
