<?php
declare(strict_types=1);

/**
 * คลาสแม่แบบ abstract ของทุกตารางในระบบ (Inheritance + Polymorphism)
 * คลาสลูกต้องบอกชื่อตารางและวิธีแปลงแถวข้อมูลเป็นออบเจ็กต์
 */
abstract class Model
{
    public function __construct(protected int $id) {}

    abstract protected static function table(): string;

    abstract protected static function fromRow(array $row): static;

    public function getId(): int
    {
        return $this->id;
    }

    protected static function db(): PDO
    {
        return Database::connect();
    }

    public static function find(int $id): ?static
    {
        $stmt = static::db()->prepare('SELECT * FROM ' . static::table() . ' WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? static::fromRow($row) : null;
    }

    public static function count(): int
    {
        return (int) static::db()->query('SELECT COUNT(*) FROM ' . static::table())->fetchColumn();
    }
}
