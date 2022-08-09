<?php

namespace YG\Mariadbdump\Builder;

final class Snippet
{
    static public function fullCode(array $values): string
    {
        $createdAt = date('Y-m-d H:i:s');
        $code = $values['code'];
        $version = $values['version'];
        $dbname = $values['dbname'];
        $serverSoftware = $values['serverSoftware'];

        return <<<EOD
-- Üretim zamanı: $createdAt
-- Veritabanı Sunucu sürümü: $version
-- Sunucu: $serverSoftware

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Veritabanı: `$dbname`
--
$code
COMMIT;
EOD;
    }

    static public function createTableTemplate(string $tableName, array $columns, string $engine, string $charset,
                                               string $collate): string
    {
        $template = <<<EOD
--
-- %s
-- 
CREATE TABLE `%s` (
    %s
) ENGINE=%s DEFAULT CHARSET=%s COLLATE=%s;
EOD;

        return PHP_EOL . sprintf(
                $template,
                $tableName,
                $tableName,
                join(',' . PHP_EOL . '    ', $columns),
                $engine,
                $charset,
                $collate) . PHP_EOL;
    }

    /**
     * @param string   $tableName
     * @param string[] $columns
     * @param string[] $data
     *
     * @return string
     */
    static public function insertIntoTemplate(string $tableName, array $columns, array $data): string
    {
        $columns = array_map(function($name) {
            return '`' . $name . '`';
        }, $columns);

        $template = <<<EOD
INSERT INTO `%s` (%s) VALUES
%s;
EOD;

        return PHP_EOL . sprintf(
                $template,
                $tableName,
                join(', ', $columns),
                join(', ' . PHP_EOL, $data)) . PHP_EOL;
    }

    static public function modifyAutoIncrementTemplate(string $tableName, string $column, int $autoIncrement): string
    {
        $template = <<<EOD
--
-- Tablo için AUTO_INCREMENT değeri `%s`
--
ALTER TABLE `%s`
    MODIFY %s, AUTO_INCREMENT=%d;
EOD;

        return PHP_EOL . sprintf($template, $tableName, $tableName, $column, $autoIncrement) . PHP_EOL;
    }

    static public function addIndexTemplate(string $tableName, array $indexes): string
    {
        $template = <<<EOD
--
-- Tablo için indeksler `%s`
--
ALTER TABLE `%s`
    %s;
EOD;

        return PHP_EOL . sprintf($template, $tableName, $tableName, join(',' . PHP_EOL . '    ', $indexes)) . PHP_EOL;
    }

    static public function addTableConstraintTemplate(string $tableName, array $constraints): string
    {
        $template = <<<EOD
--
-- Tablo için kısıtlamalar `%s`
--
ALTER TABLE `%s` 
    %s;
EOD;

        return PHP_EOL
            . sprintf($template, $tableName, $tableName, join(',' . PHP_EOL . '    ', $constraints))
            . PHP_EOL;
    }


    static public function addForeignKeyTemplate(string $constraintName, string $columns, string $referenceTable,
                                                 string $referenceColumns, string $updateRule,
                                                 string $deleteRule): string
    {
        $template = <<<EOD
ADD CONSTRAINT `%s` FOREIGN KEY (%s) REFERENCES `%s` (%s) ON DELETE %s ON UPDATE %s
EOD;

        return sprintf(
            $template,
            $constraintName,
            $columns,
            $referenceTable,
            $referenceColumns,
            $updateRule,
            $deleteRule);
    }

    static public function addConstraintTamplate(string $constraintName, string $clause): string
    {
        $template = <<<EOD
ADD CONSTRAINT `%s` CHECK (%s)
EOD;

        return sprintf(
            $template,
            $constraintName,
            $clause);
    }

    static public function viewsTemplate(string $viewCode): string
    {
        $template = <<<EOD
--
-- Görünümler
--
%s
EOD;

        return PHP_EOL . sprintf($template, $viewCode) . PHP_EOL;
    }

    static public function createProcedureTemplate(string $procedureName, string $parameters,
                                                   string $definition): string
    {
        $template = 'CREATE PROCEDURE `%s` (%s) %s$$';

        return PHP_EOL . sprintf($template, $procedureName, $parameters, $definition) . PHP_EOL;
    }

    static public function proceduresTemplate(string $procedureCode): string
    {
        $template = <<<EOD
DELIMITER $$
--
-- Yordamlar
--
%s
DELIMITER ;
EOD;

        return PHP_EOL . sprintf($template, $procedureCode) . PHP_EOL;
    }
}