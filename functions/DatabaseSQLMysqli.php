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

    public $dataTypes = array(
        'columnIntLimits' => array(
            2 => 'TINYINT',
            4 => 'SMALLINT',
            7 => 'MEDIUMINT',
            9 => 'INT',
            'default' => 'BIGINT'
        ),

        'columnStringPermLimits' => array(
            255 => 'CHAR',
            1000 => 'VARCHAR', // In MySQL, TEXT types are stored outside of the table. For searching purposes, we only use VARCHAR for relatively small values (I decided 1000 would be reasonable).
            65535 => 'TEXT',
            16777215 => 'MEDIUMTEXT',
            '4294967295' => 'LONGTEXT'
        ),

        'columnStringTempLimits' => array( // In MySQL, TEXT is not allowed in memory tables.
            255 => 'CHAR',
            65535 => 'VARCHAR'
        ),


        'columnBlobPermLimits' => array(
            // In MySQL, BINARY values get right-padded. This is... difficult to work with, so we don't use it.
            1000 => 'VARBINARY',  // In MySQL, BLOB types are stored outside of the table. For searching purposes, we only use VARBLOB for relatively small values (I decided 1000 would be reasonable).
            65535 => 'BLOB',
            16777215 => 'MEDIUMBLOB',
            '4294967295' => 'LONGBLOB'
        ),

        'columnBlobTempLimits' => array( // In MySQL, BLOB is not allowed outside of
            65535 => 'VARBINARY'
        ),

        'columnNoLength' => array(
            'MEDIUMTEXT', 'LONGTEXT',
            'MEDIUMBLOB', 'LONGBLOB',
        ),

        'columnBitLimits' => array(
            8  => 'TINYINT UNSIGNED',
            16 => 'SMALLINT UNSIGNED',
            24 => 'MEDIUMINT UNSIGNED',
            32 => 'INTEGER UNSIGNED',
            64 => 'BIGINT UNSIGNED',
            'default' => 'INTEGER UNSIGNED',
        ),

        DatabaseTypeType::float => 'REAL',
        DatabaseTypeType::bool => 'BIT(1)',
        DatabaseTypeType::timestamp => 'INTEGER UNSIGNED',
        DatabaseTypeType::blob => 'BLOB',
    );

    public $nativeBitfield = true;
    public $enumMode = 'useEnum';
    public $commentMode = 'useAttributes';
    public $indexMode = 'useTableAttribute';

    public $tableTypes = array(
        DatabaseEngine::general => 'InnoDB',
        DatabaseEngine::memory  => 'MEMORY',
    );


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