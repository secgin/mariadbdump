<?php

namespace YG\Mariadbdump\Models;

class ForeignKey
{
    public string $name;

    public string $tableName;

    /**
     * @var string[]
     */
    public array $columnNames;

    public string $referencedTableName;

    /**
     * @var string[]
     */
    public array $referencedColumnNames;

    public string $updateRule;

    public string $deleteRule;

    /**
     * @param string   $name
     * @param string   $tableName
     * @param string[] $columnNames
     * @param string   $referencedTableName
     * @param string[] $referencedColumnNames
     * @param string   $updateRule
     * @param string   $deleteRule
     */
    public function __construct(string $name, string $tableName, array $columnNames, string $referencedTableName,
                                array  $referencedColumnNames, string $updateRule, string $deleteRule)
    {
        $this->name = $name;
        $this->tableName = $tableName;
        $this->columnNames = $columnNames;
        $this->referencedTableName = $referencedTableName;
        $this->referencedColumnNames = $referencedColumnNames;
        $this->updateRule = $updateRule;
        $this->deleteRule = $deleteRule;
    }
}