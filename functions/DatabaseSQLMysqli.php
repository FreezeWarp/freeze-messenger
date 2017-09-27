<?php
require_once(__DIR__ . '/DatabaseSQLStandard.php');

class DatabaseSQLMysqli extends DatabaseSQLStandard {
    /**
     * @var mysqli
     */
    public $connection;
    public $lastInsertId;


    public $tableQuoteStart = '`';
    public $tableQuoteEnd = '`';
    public $tableAliasQuoteStart = '`';
    public $tableAliasQuoteEnd = '`';
    public $columnQuoteStart = '`';
    public $columnQuoteEnd = '`';
    public $columnAliasQuoteStart = '`';
    public $columnAliasQuoteEnd = '`';
    public $databaseQuoteStart = '`';
    public $databaseQuoteEnd = '`';
    public $databaseAliasQuoteStart = '`';
    public $databaseAliasQuoteEnd = '`';


    public function connect($host, $port, $username, $password, $database = false) {
        $this->connection = new mysqli($host, $username, $password, $database ?: null, (int) $port);

        return $this->connection->connect_error ? false : $this->connection;
    }

    public function getVersion() {
        return $this->connection->server_info;
    }

    public function getLastError() {
        return $this->connection->connect_errno ?: $this->connection->error;
    }

    public function close() {
        if ($this->connection) {
            $function = $this->connection->close();
            unset($this->connection);
            return $function;
        }
        else {
            return true;
        }
    }

    public function selectDatabase($database) {
        return $this->connection->select_db($database);
    }

    public function escape($text, $context) {
        return $this->connection->real_escape_string($text);
    }

    public function query($rawQuery) {
        $query = $this->connection->query($rawQuery);
        $this->lastInsertId = $this->connection->insert_id ?: $this->lastInsertId;

        return $query;
    }

    public function getLastInsertId() {
        return $this->lastInsertId;
    }

    public function startTransaction() {
        $this->connection->autocommit(false); // Use start_transaction in PHP 5.5 TODO
    }

    public function endTransaction() {
        $this->connection->commit();
        $this->connection->autocommit(true);
    }

    public function rollbackTransaction() {
        $this->connection->rollback();
        $this->connection->autocommit(true);
    }
}