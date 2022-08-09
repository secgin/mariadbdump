<?php

namespace YG\Mariadbdump\Builder;

use YG\Mariadbdump\InjectableAbstract;

abstract class BuilderAbstract extends InjectableAbstract
{
    abstract public function build(): array;

    public function quote($string)
    {
        return $this->db->quote($string);
    }
}