<?php

namespace YG\Mariadbdump\Models;

class View
{
    public string $name;

    public string $definition;

    public function __construct(string $name, string $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }
}