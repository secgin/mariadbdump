<?php

namespace YG\Mariadbdump\Models;

class Procedure
{
    public string $name;

    public string $definition;

    /** @var ProcedureParameter[]  */
    protected array $parameters = [];

    public function __construct(string $name, string $definition)
    {
        $this->name = $name;
        $this->definition = $definition;
    }

    /**
     * @return ProcedureParameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param ProcedureParameter[] $parameters
     */
    public function addParameters(array $parameters): void
    {
        array_push($this->parameters, ...$parameters);
    }
}