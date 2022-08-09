<?php

namespace YG\Mariadbdump;

/**
 * @property Db       $db
 * @property DbSchema $dbSchema
 */
abstract class InjectableAbstract
{
    public function __get($name)
    {
        if (DependencyContainer::has($name))
            return DependencyContainer::get($name);

        return null;
    }
}