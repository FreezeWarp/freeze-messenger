<?php

require_once(__DIR__ . '/DatabaseSQLStandard.php');

class DatabaseSQLPgsql extends DatabaseSQLStandard {
    /**
     * @var resource
     */
    public $connection;

    public $lastInsertId;

    private $connectionUser;
    private $connectionPassword;

    public $storeTypes = array(DatabaseEngine::general);

    public $dataTypes = array(
        'columnIntLimits' => array(
            4 => 'SMALLINT',
            9 => 'INTEGER',
            'default' => 'BIGINT',
        ),
        'columnSerialLimits' => array(
            9 => 'SERIAL',
            'default' => 'BIGSERIAL',
        ),
        'columnStringPermLimits' => array(
            'default' => 'VARCHAR',
        ),
        'columnNoLength' => array(
            'TEXT', 'BYTEA'
        ),
        'columnBlobPermLimits' => array(
            'default' => 'BYTEA',
        ),

        'columnBitLimits' => array(
            15 => 'SMALLINT',
            31 => 'INTEGER',
            63 => 'BIGINT',
            'default' => 'INTEGER',
        ),
        DatabaseTypeType::float => 'REAL',
        DatabaseTypeType::bool => 'SMALLINT', // TODO: ENUM(1,2) AS BOOLENUM better.
        DatabaseTypeType::timestamp => 'INTEGER',
        DatabaseTypeType::blob => 'BYTEA',
    );

    /**
     * @var bool While Postgres supports a native bitfield type, it has very strange cast rules for it. Thus, it does not exhibit the expected behaviour.
     */
    public $nativeBitfield = false;
    public $enumMode = 'useCreateType';
    public $commentMode = 'useCommentOn';
    public $indexMode = 'useCreateIndex';
    public $useCreateIfNotExist = false;

    public function connect($host, $port, $username, $password, $database = false) {
        // keep the user and password in memory to allow for reconnects with selectdb
        $this->connectionUser = $username;
        $this->connectionPassword = $password;

        $this->connection = pg_connect("host=$host port=$port user=$username password=$password" . ($database ? " dbname=$database" : ''));

        if (!$this->connection) {
            return false;
        }
        else {
            $this->query('SET bytea_output = "escape"'); // PHP-supported binary escape format.
            return $this->connection;
        }
    }

    public function getVersion() {
        return pg_version($this->connection)['client'];
    }

    public function getLastError() {
        return pg_last_error($this->connection);
    }

    public function close() {
        if ($this->connection) {
            $function = @pg_close($this->connection);
            unset($this->connection);

            return $function;
        }
        else {
            return true;
        }
    }

    public function selectDatabase($database) {
        return $this->connect(pg_host($this->connection), pg_port($this->connection), $this->connectionUser, $this->connectionPassword, $database);
    }

    public function escape($text, $context) {
        if ($context === DatabaseTypeType::blob)
            return pg_escape_bytea($this->connection, $text);
        else
            return pg_escape_string($this->connection, $text);
    }

    public function query($rawQuery) {
        return pg_query($this->connection, $rawQuery);
    }

    public function getLastInsertId() {
        return $this->rawQuery('SELECT LASTVAL() AS lastval')->getAsArray(false)['lastval'];
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


    public function notify() {
        return pg_get_notify($this->connection);
    }
}