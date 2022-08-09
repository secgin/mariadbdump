<?php

namespace YG\Mariadbdump\Builder;

use YG\Mariadbdump\Models\Procedure;

class ProcedureBuilder extends BuilderAbstract
{
    private Procedure $procedure;

    public function __construct(Procedure $procedure)
    {
        $this->procedure = $procedure;
    }

    public function build(): array
    {
        return [
            'procedure' => $this->getProcedureCode(),
        ];
    }

    private function getProcedureCode(): string
    {
        return Snippet::createProcedureTemplate($this->procedure->name, $this->getParameters(), $this->procedure->definition);
    }

    private function getParameters(): string
    {
        $parameters = array_map(function ($parameter) {
            return $parameter->mode . ' `' . $parameter->name . '` ' . $parameter->dataType;
        }, $this->procedure->getParameters());

        return join(', ', $parameters);
    }
}