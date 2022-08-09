<?php

namespace YG\Mariadbdump;

use YG\Mariadbdump\Models\Column;
use YG\Mariadbdump\Models\Constraint;
use YG\Mariadbdump\Models\ForeignKey;
use YG\Mariadbdump\Models\Index;
use YG\Mariadbdump\Models\Procedure;
use YG\Mariadbdump\Models\ProcedureParameter;
use YG\Mariadbdump\Models\Table;
use YG\Mariadbdump\Models\View;

class DbSchema extends InjectableAbstract
{
    public function getMariaDbVersion(): ?string
    {
        $result = $this->db->fetch('SELECT version() as version');

        if (!$result)
            return '';

        return $result['version'];
    }

    #region Table
    public function getTables(): array
    {
        $sql = <<<EOD
        SELECT
            TABLE_NAME,
            ENGINE,
            AUTO_INCREMENT,
            TABLE_COLLATION
        FROM
            INFORMATION_SCHEMA.TABLES
        WHERE
            TABLE_SCHEMA='{$this->db->getDbname()}' AND
            TABLE_TYPE='BASE TABLE'
        ORDER BY
            TABLE_NAME
        EOD;
        $data = $this->db->fetchAll($sql);

        $tables = [];
        foreach ($data as $row)
        {
            $table = new Table($row['TABLE_NAME'], $row['ENGINE'], $row['AUTO_INCREMENT'], $row['TABLE_COLLATION']);

            $table->addColumns($this->getColumns($table->name));
            $table->addIndexes($this->getIndexes($table->name));
            $table->addForeignKeys($this->getForeignKeys($table->name));
            $table->addConstraints($this->getTableCheckConstraints($table->name));
            $table->setRows($this->getTableData($table->name));

            $tables[] = $table;
        }

        return $tables;
    }

    /**
     * @param string $tableName
     *
     * @return Column[]
     */
    private function getColumns(string $tableName): array
    {
        $sql = <<<EOD
        SELECT
            COLUMN_NAME,
            COLUMN_TYPE,
            COLLATION_NAME,
            CHARACTER_SET_NAME,
            IS_NULLABLE,
            COLUMN_KEY,
            COLUMN_DEFAULT,
            COLUMN_COMMENT,
            EXTRA,
            DATA_TYPE
        FROM
            INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_SCHEMA='{$this->db->getDbname()}' AND
            TABLE_NAME='$tableName'
        EOD;

        $rows = $this->db->fetchAll($sql);

        $columns = [];
        foreach ($rows as $row)
        {
            $column = new Column(
                $row['COLUMN_NAME'],
                $row['COLUMN_TYPE'],
                $row['COLLATION_NAME'],
                $row['CHARACTER_SET_NAME'],
                $row['IS_NULLABLE'] == 'YES',
                $row['COLUMN_KEY'],
                $row['COLUMN_DEFAULT'],
                $row['COLUMN_COMMENT'],
                $row['EXTRA'],
                $row['DATA_TYPE']);

            $constraint = $this->getTableColumnCheckConstraints($tableName, $column->name);
            if ($constraint)
                $column->setCheckClause($constraint->clause);

            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * @param string $tableName
     *
     * @return ForeignKey[]
     */
    private function getForeignKeys(string $tableName): array
    {
        $sql = <<<EOD
        SELECT
            kcu.CONSTRAINT_NAME,
            kcu.TABLE_NAME,
            kcu.COLUMN_NAME,
            kcu.REFERENCED_TABLE_NAME,
            kcu.REFERENCED_COLUMN_NAME,
            rc.UPDATE_RULE,
            rc.DELETE_RULE
        FROM
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS kcu INNER JOIN
            INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS rc ON rc.CONSTRAINT_SCHEMA=kcu.TABLE_SCHEMA AND rc.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME
        WHERE
            kcu.TABLE_SCHEMA='{$this->db->getDbname()}' AND
            kcu.TABLE_NAME='$tableName'
        EOD;
        $rows = $this->db->fetchAll($sql);

        $groupedForeignKeys = [];
        foreach ($rows as $row)
            $groupedForeignKeys[$row['CONSTRAINT_NAME']][] = $row;

        $foreignKeys = [];
        foreach ($groupedForeignKeys as $constraintName => $foreignKeyRows)
        {
            $columnNames = [];
            foreach ($foreignKeyRows as $row)
                $columnNames[] = $row['COLUMN_NAME'];

            $referenceTableColumnNames = [];
            foreach ($foreignKeyRows as $row)
                $referenceTableColumnNames[] = $row['REFERENCED_COLUMN_NAME'];

            $foreignKey = new ForeignKey(
                $constraintName,
                $foreignKeyRows[0]['TABLE_NAME'],
                $columnNames,
                $foreignKeyRows[0]['REFERENCED_TABLE_NAME'],
                $referenceTableColumnNames,
                $foreignKeyRows[0]['UPDATE_RULE'],
                $foreignKeyRows[0]['DELETE_RULE']
            );

            $foreignKeys[] = $foreignKey;
        }

        return $foreignKeys;
    }

    /**
     * @param string $tableName
     *
     * @return Index[]
     */
    private function getIndexes(string $tableName): array
    {
        $sqlIndexes = 'SHOW INDEX FROM ' . $tableName . ' FROM ' . $this->db->getDbname();
        $rows = $this->db->fetchAll($sqlIndexes);

        $groupedIndexes = [];
        foreach ($rows as $row)
            $groupedIndexes[$row['Key_name']][] = $row;

        $indexes = [];
        foreach ($groupedIndexes as $keyName => $rows)
        {
            $columnNames = [];
            foreach ($rows as $row)
                $columnNames[$row['Seq_in_index']] = $row['Column_name'];

            $index = new Index(
                $keyName,
                $columnNames,
                $rows[0]['Index_type'],
                $rows[0]['Comment'],
                $rows[0]['Index_comment'],
                $this->getIndexConstraintType($tableName, $keyName));

            $indexes[] = $index;
        }

        return $indexes;
    }

    private function getIndexConstraintType(string $tableName, string $indexName): ?string
    {
        $sql = <<<EOD
        SELECT
            CONSTRAINT_TYPE
        FROM
            INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE
            TABLE_SCHEMA='{$this->db->getDbname()}' AND
            TABLE_NAME='$tableName' AND
            CONSTRAINT_NAME='$indexName'
        EOD;
        $tableConstraint = $this->db->fetch($sql);

        return $tableConstraint['CONSTRAINT_TYPE'] ?? null;
    }

    /**
     * @param string $tableName
     *
     * @return Constraint[]
     */
    private function getTableCheckConstraints(string $tableName): array
    {
        $sql = <<<EOD
        SELECT
            CONSTRAINT_NAME,
            LEVEL,
            CHECK_CLAUSE
        FROM
            INFORMATION_SCHEMA.CHECK_CONSTRAINTS
        WHERE
            CONSTRAINT_SCHEMA='{$this->db->getDbname()}' AND
            LEVEL='Table' AND
            TABLE_NAME LIKE '$tableName'
        EOD;
        $rows = $this->db->fetchAll($sql);

        $constraints = [];
        foreach ($rows as $row)
        {
            $constraints[] = new Constraint(
                $row['CONSTRAINT_NAME'],
                $row['LEVEL'],
                $row['CHECK_CLAUSE']);
        }
        return $constraints;
    }

    private function getTableColumnCheckConstraints(string $tableName, string $columnName): ?Constraint
    {
        $sql = <<<EOD
        SELECT
            CONSTRAINT_NAME,
            LEVEL,
            CHECK_CLAUSE
        FROM
            INFORMATION_SCHEMA.CHECK_CONSTRAINTS
        WHERE
            CONSTRAINT_SCHEMA='{$this->db->getDbname()}' AND
            LEVEL='Column' AND
            TABLE_NAME LIKE '$tableName' AND
            CONSTRAINT_NAME='$columnName'
        EOD;
        $row = $this->db->fetch($sql);

        if ($row)
            return new Constraint(
                $row['CONSTRAINT_NAME'],
                $row['LEVEL'],
                $row['CHECK_CLAUSE']);

        return null;
    }

    private function getTableData(string $tableName): array
    {
        $rows = $this->db->fetchAll('SELECT * FROM ' . $tableName);
        if (!$rows)
            return [];

        return $rows;
    }
    #endregion

    /**
     * @return View[]
     */
    public function getViews(): array
    {
        $sql = <<<EOD
        SELECT
            TABLE_NAME,
            VIEW_DEFINITION
        FROM
            INFORMATION_SCHEMA.VIEWS
        WHERE
            TABLE_SCHEMA='{$this->db->getDbname()}'
        EOD;
        $rows = $this->db->fetchAll($sql);

        $views = [];
        foreach ($rows as $row)
        {
            $views[] = new View(
                $row['TABLE_NAME'],
                $row['VIEW_DEFINITION']);
        }

        return $views;
    }

    #region Procedures
    public function getProcedures(): array
    {
        $sql = <<<EOD
        SELECT
            ROUTINE_NAME,
            ROUTINE_DEFINITION
        FROM
            INFORMATION_SCHEMA.ROUTINES
        WHERE
            ROUTINE_SCHEMA='{$this->db->getDbname()}' AND
            ROUTINE_TYPE='PROCEDURE'
        EOD;
        $rows = $this->db->fetchAll($sql);

        $procedures = [];
        foreach ($rows as $row)
        {
            $procedure = new Procedure(
                $row['ROUTINE_NAME'],
                $row['ROUTINE_DEFINITION']);

            $procedure->addParameters($this->getProcedureParameters($row['ROUTINE_NAME']));

            $procedures[] = $procedure;
        }

        return $procedures;
    }

    /**
     * @param string $procedureName
     *
     * @return ProcedureParameter[]
     */
    private function getProcedureParameters(string $procedureName): array
    {
        $sql = <<<EOD
        SELECT
            PARAMETER_NAME,
            PARAMETER_MODE,
            DATA_TYPE,
            ORDINAL_POSITION
        FROM 
            INFORMATION_SCHEMA.PARAMETERS
        WHERE
            SPECIFIC_SCHEMA='{$this->db->getDbname()}' AND
            SPECIFIC_NAME='$procedureName'
        EOD;
        $rows = $this->db->fetchAll($sql);

        $parameters = [];
        foreach ($rows as $row)
        {
            $parameters[] = new ProcedureParameter(
                $row['PARAMETER_NAME'],
                $row['PARAMETER_MODE'],
                $row['DATA_TYPE'],
                $row['ORDINAL_POSITION']);
        }

        return $parameters;
    }
    #endregion
}