<?php

namespace YG\Mariadbdump\Models;

class Table
{
    public string $name;

    public string $engine;

    public ?int $autoIncrement = null;

    public string $charset;

    public string $collation;

    protected ?Column $autoIncrementColumn = null;

    /** @var Column[] */
    protected array $columns = [];

    /** @var Index[] */
    protected array $indexes = [];

    /** @var ForeignKey[] */
    protected array $foreignKeys = [];

    protected array $rows = [];

    /**
     * @var Constraint[]
     */
    protected array $constraints = [];

    public function __construct(string $name, string $engine, ?int $autoIncrement, string $collation)
    {
        $this->name = $name;
        $this->engine = $engine;
        $this->autoIncrement = $autoIncrement;
        $this->collation = $collation;

        $arr = explode('_', $collation);
        $this->charset = $arr[0];
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return Index[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * @return ForeignKey[]|Constraint[]
     */
    public function getConstraints(): array
    {
        return array_merge_recursive($this->foreignKeys, $this->constraints);
    }

    public function getAutoIncrementColumn(): ?Column
    {
        return $this->autoIncrementColumn;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * @param Column[] $columns
     */
    public function addColumns(array $columns): void
    {
        foreach ($columns as $column)
            if ($column->isAutoIncrement())
                $this->autoIncrementColumn = $column;

        array_push($this->columns, ...$columns);
    }

    /**
     * @param ForeignKey[] $foreignKeys
     */
    public function addForeignKeys(array $foreignKeys)
    {
        array_push($this->foreignKeys, ...$foreignKeys);
    }

    /**
     * @param Index[] $indexes
     */
    public function addIndexes(array $indexes): void
    {
        array_push($this->indexes, ...$indexes);
    }

    /**
     * @param Constraint[] $constraints
     */
    public function addConstraints(array $constraints): void
    {
        array_push($this->constraints, ...$constraints);
    }

    public function setRows(array $rows): void
    {
        $this->rows = $rows;
    }
}