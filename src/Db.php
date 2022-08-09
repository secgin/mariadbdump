<?php

namespace YG\Mariadbdump;

use PDO;

class Db
{
    private string
        $host,
        $dbname,
        $user,
        $password;

    private ?PDO $connection = null;

    public function __construct(string $host, string $dbname, string $user, string $password)
    {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->user = $user;
        $this->password = $password;
    }

    public function getDbname(): string
    {
        return $this->dbname;
    }

    public function fetchAll($statement)
    {
        return $this->getConnection()->query($statement)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetch($statement)
    {
        return $this->getConnection()->query($statement)->fetch(PDO::FETCH_ASSOC);
    }

    public function quote($string, $type = PDO::PARAM_STR)
    {
        return $this->getConnection()->quote($string, $type);
    }

    private function getConnection(): PDO
    {
        if ($this->connection == null)
            $this->connection = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->user, $this->password);

        return $this->connection;
    }
}