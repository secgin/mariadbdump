<?php

namespace YG\Mariadbdump\Models;

class Column
{
    public string $name;

    public string $type;

    public ?string $collationName;

    public ?string $characterSetName;

    public bool $isNullable;

    public ?string $key;

    public ?string $default;

    public ?string $comment;

    public ?string $extra;

    public string $dataType;

    public ?string $checkClause;

    public function __construct(string $name, string $type, ?string $collationName, ?string $characterSetName,
                                bool   $isNullable, ?string $key, ?string $default, ?string $comment, ?string $extra,
                                string $dataType)
    {
        $this->name = $name;
        $this->type = $type;
        $this->collationName = $collationName;
        $this->characterSetName = $characterSetName;
        $this->isNullable = $isNullable;
        $this->key = $key;
        $this->default = $default;
        $this->comment = $comment;
        $this->extra = $extra;
        $this->dataType = $dataType;
        $this->checkClause = null;
    }

    public function setCheckClause(string $checkClause): void
    {
        $this->checkClause = $checkClause;
    }

    public function isAutoIncrement(): bool
    {
        return $this->extra === 'auto_increment';
    }

    public function isString(): bool
    {
        $types =[
            'char',
            'varchar',
            'tinytext',
            'text',
            'mediumtext',
            'longtext'
        ];

        return array_search(strtolower($this->dataType), $types) !== false;
    }

    public function isDate(): bool
    {
        $types = [
            'date',
            'datetime',
            'timestamp',
            'time'
        ];

        return array_search(strtolower($this->dataType), $types) !== false;
    }
}