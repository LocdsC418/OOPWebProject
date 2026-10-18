<?php
declare(strict_types=1);

class Category extends Model
{
    public function __construct(int $id, private string $name)
    {
        parent::__construct($id);
    }

    protected static function table(): string
    {
        return 'categories';
    }

    protected static function fromRow(array $row): static
    {
        return new static((int) $row['id'], $row['name']);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return Category[] */
    public static function all(): array
    {
        $rows = static::db()->query('SELECT * FROM categories ORDER BY id')->fetchAll();
        return array_map(fn(array $row) => static::fromRow($row), $rows);
    }
}
