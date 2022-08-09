<?php

namespace YG\Mariadbdump\Models;

class ProcedureParameter
{
    public string $name;

    public string $mode;

    public string $dataType;

    public int $ordinalPosition;

    public function __construct(string $name, string $mode, string $dataType, int $ordinalPosition)
    {
        $this->name = $name;
        $this->mode = $mode;
        $this->dataType = $dataType;
        $this->ordinalPosition = $ordinalPosition;
    }
}