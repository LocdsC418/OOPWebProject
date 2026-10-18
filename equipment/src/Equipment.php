<?php
declare(strict_types=1);

class Equipment extends Model
{
    public function __construct(
        int $id,
        private string $code,
        private string $name,
        private int $categoryId,
        private string $categoryName,
        private int $totalQty,
        private int $availableQty
    ) {
        parent::__construct($id);
    }

    protected static function table(): string
    {
        return 'equipment';
    }

    protected static function fromRow(array $row): static
    {
        return new static(
            (int) $row['id'],
            $row['code'],
            $row['name'],
            (int) $row['category_id'],
            $row['category_name'] ?? '',
            (int) $row['total_qty'],
            (int) $row['available_qty']
        );
    }

    public function getCode(): string        { return $this->code; }
    public function getName(): string        { return $this->name; }
    public function getCategoryId(): int     { return $this->categoryId; }
    public function getCategoryName(): string { return $this->categoryName; }
    public function getTotalQty(): int       { return $this->totalQty; }
    public function getAvailableQty(): int   { return $this->availableQty; }

    public function getBorrowedQty(): int
    {
        return $this->totalQty - $this->availableQty;
    }

    public function isAvailable(): bool
    {
        return $this->availableQty > 0;
    }

    /**
     * ค้นหาอุปกรณ์ตามคำค้น (ชื่อ/รหัส) และหมวดหมู่
     * @return Equipment[]
     */
    public static function all(string $search = '', ?int $categoryId = null): array
    {
        $sql = 'SELECT e.*, c.name AS category_name
                FROM equipment e
                JOIN categories c ON c.id = e.category_id
                WHERE 1 = 1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (e.name LIKE ? OR e.code LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($categoryId !== null) {
            $sql .= ' AND e.category_id = ?';
            $params[] = $categoryId;
        }
        $sql .= ' ORDER BY e.code';

        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return array_map(fn(array $row) => static::fromRow($row), $stmt->fetchAll());
    }

    public static function add(string $code, string $name, int $categoryId, int $qty): void
    {
        $code = strtoupper(trim($code));
        $name = trim($name);

        if ($code === '' || $name === '' || !mb_check_encoding($name, 'UTF-8')) {
            throw new LoanException('กรุณากรอกรหัสและชื่ออุปกรณ์');
        }
        if (!preg_match('/^[A-Z0-9-]{1,20}$/', $code)) {
            throw new LoanException('รหัสอุปกรณ์ใช้ได้เฉพาะ A-Z, 0-9 และ - ไม่เกิน 20 ตัว');
        }
        if ($qty < 1 || $qty > 999) {
            throw new LoanException('จำนวนต้องอยู่ระหว่าง 1 ถึง 999');
        }
        if (Category::find($categoryId) === null) {
            throw new LoanException('ไม่พบหมวดหมู่ที่เลือก');
        }

        $exists = static::db()->prepare('SELECT 1 FROM equipment WHERE code = ?');
        $exists->execute([$code]);
        if ($exists->fetchColumn()) {
            throw new LoanException("รหัส $code มีอยู่ในระบบแล้ว");
        }

        $stmt = static::db()->prepare(
            'INSERT INTO equipment (code, name, category_id, total_qty, available_qty) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$code, $name, $categoryId, $qty, $qty]);
    }

    /** จำนวนชิ้นที่ถูกยืมอยู่ทั้งหมดในระบบ */
    public static function totalBorrowed(): int
    {
        return (int) static::db()->query('SELECT COALESCE(SUM(total_qty - available_qty), 0) FROM equipment')->fetchColumn();
    }
}
