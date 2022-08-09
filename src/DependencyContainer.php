<?php

namespace YG\Mariadbdump;

final class DependencyContainer
{
    static private array $dependencies = [];

    static public function add(string $name, $dependency): void
    {
        self::$dependencies[$name] = $dependency;
    }

    static public function get(string $name): object
    {
        return self::$dependencies[$name];
    }

    static function has(string $name): bool
    {
        return isset(self::$dependencies[$name]);
    }
}