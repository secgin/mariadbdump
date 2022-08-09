<?php

namespace YG\Mariadbdump\Builder;

use YG\Mariadbdump\Models\Column;
use YG\Mariadbdump\Models\Constraint;
use YG\Mariadbdump\Models\ForeignKey;
use YG\Mariadbdump\Models\Index;
use YG\Mariadbdump\Models\Table;

class TableBuilder extends BuilderAbstract
{
    private Table $table;

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function build(): array
    {
        return [
            'table' => $this->getTableCode() . $this->getData(),
            'autoIncrement' => $this->getAutoIncrementCode(),
            'index' => $this->getIndexCode(),
            'constraint' => $this->getConstraintCode(),
        ];
    }

    private function getTableCode(): string
    {
        return Snippet::createTableTemplate(
            $this->table->name,
            $this->getColumns(),
            $this->table->engine,
            $this->table->charset,
            $this->table->collation);
    }

    private function getAutoIncrementCode(): string
    {
        $autoIncrementColumn = $this->table->getAutoIncrementColumn();
        if ($autoIncrementColumn === null)
            return '';

        $column = $this->getColumn($autoIncrementColumn) . ' AUTO_INCREMENT';

        return Snippet::modifyAutoIncrementTemplate($this->table->name, $column, $this->table->autoIncrement);
    }

    private function getIndexCode(): string
    {
        $indexes = $this->table->getIndexes();
        if (count($indexes) == 0)
            return '';

        $results = [];
        foreach ($indexes as $index)
        {
            $arr = [
                'ADD',
                $this->getIndexType($index)
            ];

            if ($index->constraintType != Index::PRIMARY)
                $arr[] = '`' . $index->name . '`';

            $arr[] = '(' . $this->getColumnNamesToString($index->columnNames) . ')';

            if ($index->indexComment)
                $arr[] = 'COMMENT ' . $this->quote($index->indexComment);

            $results[] = join(' ', $arr);
        }

        return Snippet::addIndexTemplate($this->table->name, $results);
    }

    private function getConstraintCode(): string
    {
        $constraints = $this->table->getConstraints();
        if (count($constraints) == 0)
            return '';

        $results = [];
        foreach ($constraints as $constraint)
        {
            if ($constraint instanceof ForeignKey)
            {
                $results[] = Snippet::addForeignKeyTemplate(
                    $constraint->name,
                    $this->getColumnNamesToString($constraint->columnNames),
                    $constraint->referencedTableName,
                    $this->getColumnNamesToString($constraint->referencedColumnNames),
                    $constraint->updateRule,
                    $constraint->deleteRule);
            }
            elseif ($constraint instanceof Constraint)
            {
                $results[] = Snippet::addConstraintTamplate(
                    $constraint->name,
                    $constraint->clause);
            }
        }

        return Snippet::addTableConstraintTemplate($this->table->name, $results);
    }

    private function getData(): string
    {
        $rows = $this->table->getRows();
        if (count($rows) == 0)
            return '';

        $columns = $this->table->getColumns();
        $data = [];
        foreach ($rows as $row)
        {
            $dataRow = [];
            foreach ($columns as $column)
            {
                $value = $row[$column->name];

                if ($value === null)
                    if ($column->isNullable)
                        $dataRow[] = 'NULL';
                    else
                        $dataRow[] = $this->quote('');
                else
                {
                    if ($column->dataType == 'varbinary')
                        $dataRow[] = '0x' . strtoupper(bin2hex($value));
                    elseif ($column->isString() or $column->isDate())
                        $dataRow[] = $this->quote($value);
                    else
                        $dataRow[] = $value;

                }
            }

            $data[] = '(' . join(', ', $dataRow) . ')';
        }

        return Snippet::insertIntoTemplate(
            $this->table->name,
            $this->getColumnNames(),
            $data);
    }

    /**
     * @return string[]
     */
    private function getColumns(): array
    {
        return array_map(function(Column $column) {
            return $this->getColumn($column);
        }, $this->table->getColumns());
    }

    private function getColumn(Column $column): string
    {
        $arr = [
            '`' . $column->name . '`',
            $column->type,
        ];

        if ($column->characterSetName != '' and $column->characterSetName != $this->table->charset)
            $arr[] = 'CHARACTER SET ' . $column->characterSetName;

        if ($column->collationName != '')
            $arr[] = 'COLLATE ' . $column->collationName;

        $arr[] = ($column->isNullable ? '' : 'NOT ') . 'NULL';

        if ($column->default != null)
            $arr[] = 'DEFAULT ' . $column->default;

        if ($column->extra != '' and $column->extra != 'auto_increment')
            $arr[] = $column->extra;

        if ($column->comment != '')
            $arr[] = 'COMMENT ' . $this->quote($column->comment);

        if ($column->checkClause != '')
            $arr[] = 'CHECK (' . $column->checkClause . ')';

        return implode(' ', $arr);
    }

    private function getIndexType(Index $index): string
    {
        switch ($index->getType())
        {
            case Index::PRIMARY:
                return 'PRIMARY KEY';
            case Index::UNIQUE:
                return 'UNIQUE KEY';
            case Index::FULLTEXT:
                return 'FULLTEXT KEY';
            default:
                return 'KEY';
        }
    }

    /**
     * @return string[]
     */
    private function getColumnNames(): array
    {
        return array_map(function(Column $column) {
            return $column->name;
        }, $this->table->getColumns());
    }

    /**
     * @param string[] $columnNames
     *
     * @return string
     */
    private function getColumnNamesToString(array $columnNames): string
    {
        $columnNames = array_map(function(string $columnName) {
            return '`' . $columnName . '`';
        }, $columnNames);

        return implode(', ', $columnNames);
    }
}