<?php

namespace YG\Mariadbdump\Models;

class Constraint
{
    const LEVEL_COLUMN = 'Column';

    const LEVEL_TABLE = 'Table';

    public string $name;

    public string $level;

    public string $clause;

    public function __construct(string $name, string $level, string $clause)
    {
        $this->name = $name;
        $this->level = $level;
        $this->clause = $clause;
    }
}