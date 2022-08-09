<?php

namespace YG\Mariadbdump\Models;

class Index
{
    const PRIMARY = 'PRIMARY KEY';
    const UNIQUE = 'UNIQUE';
    const PLAN = 'PLAN';
    const FULLTEXT = 'FULLTEXT';

    public string $name;

    /**
     * @var string[]
     */
    public array $columnNames;

    public string $indexType;

    public string $comment;

    public string $indexComment;

    public ?string $constraintType;

    public function __construct(string $name, array $columnNames, string $indexType, string $comment,
                                string $indexComment, ?string $constraintType)
    {
        $this->name = $name;
        $this->columnNames = $columnNames;
        $this->indexType = $indexType;
        $this->comment = $comment;
        $this->indexComment = $indexComment;
        $this->constraintType = $constraintType;
    }

    public function getType(): string
    {
        if ($this->constraintType)
        {
            if ($this->constraintType === self::PRIMARY)
                return self::PRIMARY;

            if ($this->constraintType === self::UNIQUE)
                return self::UNIQUE;
        }

        if ($this->indexType === self::FULLTEXT)
            return self::FULLTEXT;

        return self::PLAN;
    }
}