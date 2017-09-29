<?php
require_once(__DIR__ . '/DatabaseDefinitionsMySQL.php');

class DatabaseSQLMysql extends DatabaseDefinitionsMySQL {
    /**
     * @var resource
     */
    public $connection;

    public $lastInsertId;

    public function connect($host, $port, $username, $password, $database = false) {
        $this->connection = mysql_connect("$host:$port", $username, $password);

        return $this->connection ?: false;
    }

    public function getVersion() {
        return mysql_get_server_info($this->connection);
    }

    public function getLastError() {
        return mysql_error(isset($this->connection) ? $this->connection : null);
    }

    public function close() {
        if ($this->connection) {
            $function = mysql_close($this->connection);
            unset($this->connection);

            return $function;
        }
        else {
            return true;
        }
    }

    public function selectDatabase($database) {
        return mysql_select_db($database, $this->connection);
    }

    public function escape($text, $context) {
        return mysql_real_escape_string($text, $this->connection);
    }

    public function query($rawQuery) {
        $query = mysql_query($rawQuery, $this->connection);
        $this->lastInsertId = mysql_insert_id($this->connection) ?: $this->lastInsertId;

        return $query;
    }

    public function queryReturningResult($rawQuery) : DatabaseResultInterface {
        return $this->getResult($this->query($rawQuery));
    }

    public function getLastInsertId() {
        return $this->lastInsertId;
    }

    public function startTransaction() {
        $this->query('START TRANSACTION');
    }

    public function endTransaction() {
        $this->query('COMMIT');
    }

    public function rollbackTransaction() {
        $this->query('ROLLBACK');
    }

    protected function getResult($source) : DatabaseResultInterface {
        return new class($source) implements DatabaseResultInterface {
            /**
             * @var resource The result of the query.
             */
            public $source;

            public function __construct($source) {
                $this->source = $source;
            }

            public function fetchAsArray() {
                return (($data = mysql_fetch_assoc($this->source)) === false ? false : $data);
            }

            public function getCount() {
                return mysql_num_rows($this->source);
            }
        };
    }
}