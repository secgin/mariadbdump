<?php

namespace YG\Mariadbdump;

use Exception;
use YG\Mariadbdump\Builder\ProcedureBuilder;
use YG\Mariadbdump\Builder\Snippet;
use YG\Mariadbdump\Builder\TableBuilder;
use YG\Mariadbdump\Builder\ViewBuilder;

class Mariadbdump extends InjectableAbstract
{
    private string
        $tableCode,
        $autoIncrementCode,
        $indexCode,
        $constraintCode,
        $viewCode,
        $procedureCode,
        $fullCode;

    public function __construct(array $options)
    {
        DependencyContainer::add('db', new Db(
            $options['database']['host'],
            $options['database']['dbname'],
            $options['database']['username'],
            $options['database']['password']));
        DependencyContainer::add('dbSchema', new DbSchema());
    }

    /**
     * @throws Exception
     */
    public function dump(?string $filePath = null): string
    {
        $this->clear();

        $this->dumpTable();
        $this->dumpView();
        $this->dumpProcedure();

        $code =
            $this->tableCode .
            $this->indexCode .
            $this->autoIncrementCode .
            $this->constraintCode .
            $this->viewCode .
            $this->procedureCode;

        $this->fullCode = Snippet::fullCode([
            'serverSoftware' => $_SERVER['SERVER_SOFTWARE'],
            'version' => $this->dbSchema->getMariaDbVersion(),
            'dbname' => $this->db->getDbName(),
            'code' => $code,
        ]);

        if ($filePath)
            $this->saveToFile($filePath);

        return $this->fullCode;
    }

    private function clear(): void
    {
        $this->tableCode = '';
        $this->autoIncrementCode = '';
        $this->indexCode = '';
        $this->constraintCode = '';
        $this->viewCode = '';
        $this->procedureCode = '';
        $this->fullCode = '';
    }

    private function dumpTable(): void
    {
        $tables = $this->dbSchema->getTables();

        foreach ($tables as $table)
        {
            $tableBuilder = new TableBuilder($table);
            $code = $tableBuilder->build();

            $this->tableCode .= $code['table'];
            $this->autoIncrementCode .= $code['autoIncrement'];
            $this->indexCode .= $code['index'];
            $this->constraintCode .= $code['constraint'];
        }
    }

    private function dumpView(): void
    {
        $views = $this->dbSchema->getViews();
        foreach ($views as $view)
        {
            $viewBuilder = new ViewBuilder($view);
            $code = $viewBuilder->build();
            $this->viewCode .= $code['view'];
        }

        if ($this->viewCode != '')
            $this->viewCode = Snippet::viewsTemplate($this->viewCode);
    }

    private function dumpProcedure(): void
    {
        $procedures = $this->dbSchema->getProcedures();
        foreach ($procedures as $procedure)
        {
            $procedureBuilder = new ProcedureBuilder($procedure);
            $code = $procedureBuilder->build();
            $this->procedureCode .= $code['procedure'];
        }

        if ($this->procedureCode != '')
            $this->procedureCode = Snippet::proceduresTemplate($this->procedureCode);
    }

    /**
     * @throws Exception
     */
    private function saveToFile(string $filePath): void
    {
        $fileName = $this->db->getDbname() . '_ $' . date('YmdHis') . '.sql';
        $filePath = rtrim($filePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        $result = file_put_contents($filePath, $this->fullCode);

        if ($result === false)
            throw new SaveFileException('Can\'t save file');
    }
}