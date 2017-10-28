<?php
namespace Database\SQL;

use Database\DatabaseResultInterface;
use Database\DatabaseEngine;
use Database\DatabaseTypeType;

class DatabaseSQLSqlsrv extends DatabaseSQLStandard {

    /**
     * @var resource
     */
    public $connection;

    /**
     * @var array
     */
    public $preparedParams = [];

    public $storeTypes = array(DatabaseEngine::general);

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
            '4294967295' => 'VARCHAR'
        ),

        'columnStringTempLimits' => array( // In MySQL, TEXT is not allowed in memory tables.
            255 => 'NCHAR',
            65535 => 'NVARCHAR'
        ),


        'columnBlobPermLimits' => array(
            2147483647 => 'VARBINARY',
        ),

        'columnBlobTempLimits' => array(
            65535 => 'VARBINARY'
        ),

        'columnNoLength' => [],

        'columnBitLimits' => array(
            8  => 'TINYINT',
            16 => 'SMALLINT',
            24 => 'MEDIUMINT',
            32 => 'INTEGER',
            64 => 'BIGINT',
            'default' => 'INTEGER',
        ),

        DatabaseTypeType::float => 'REAL',
        DatabaseTypeType::bool => 'BIT',
        DatabaseTypeType::timestamp => 'INTEGER',
        DatabaseTypeType::blob => 'VARBINARY(MAX)',
    );

    /**
     * @var bool While Postgres supports a native bitfield type, it has very strange cast rules for it. Thus, it does not exhibit the expected behaviour.
     */
    public $enumMode = 'useCheck';
    public $indexMode = 'useCreateIndex';
    public $serialMode = 'identity';
    // TODO (currently broken)
    // public $foreignKeyMode = 'useAlterTableAddForeignKey';

    public function connect($host, $port, $username, $password, $database = false) {
        return $this->connection = sqlsrv_connect($host, [
            "Database" => $database,
            "UID" => $username,
            "PWD" => $password
        ]);
    }

    public function selectDatabase($database)
    {
        return $this->query("USE " . $database);
    }

    public function getVersion() {
        return sqlsrv_server_info($this->connection)['SQLServerVersion'];
    }

    public function getLastError() {
        return print_r(sqlsrv_errors(), true);
    }

    public function close() {
        if ($this->connection) {
            $function = @sqlsrv_close($this->connection);
            unset($this->connection);

            return $function;
        }
        else {
            return true;
        }
    }

    public function escape($text, $context) {
        return $text; // SUUUUUUPPER TODO
        switch ($context) {
            case DatabaseTypeType::blob:
            case DatabaseTypeType::string:
                $unpacked = unpack('H*hex', $text);
                return '0x' . $unpacked['hex'];
                break;

            case DatabaseTypeType::search:
                $unpacked = unpack('H*hex', "%$text%");
                return '0x' . $unpacked['hex'];
                break;

            default:
                return $text;
        }
    }

    public function query($rawQuery) {
        $query = sqlsrv_query($this->connection, $rawQuery, $this->preparedParams, [ "Scrollable" => SQLSRV_CURSOR_KEYSET ]);
        $this->preparedParams = [];
        return $query;
    }

    public function queryReturningResult($rawQuery) : DatabaseResultInterface {
        return $this->getResult($this->query($rawQuery));
    }

    public function getLastInsertId() {
        return pg_fetch_array($this->query('SELECT SCOPE_IDENTITY() AS lastval'))['lastval'];
    }

    public function startTransaction() {
        sqlsrv_begin_transaction($this->connection);
    }

    public function endTransaction() {
        sqlsrv_commit($this->connection);
    }

    public function rollbackTransaction() {
        sqlsrv_rollback($this->connection);
    }

    protected function getResult($source) {
        return new class($source) implements DatabaseResultInterface {
            public $source;

            public function __construct($source) {
                $this->source = $source;
            }

            public function fetchAsArray() {
                return sqlsrv_fetch_array($this->source, SQLSRV_FETCH_ASSOC);
            }

            public function getCount() {
                return sqlsrv_num_rows($this->source);
            }
        };
    }

    public function getTablesAsArray(DatabaseSQL $database) {
        return $database->rawQueryReturningResult('SELECT * FROM '
            . $database->formatValue(DatabaseSQL::FORMAT_VALUE_DATABASE_TABLE, 'INFORMATION_SCHEMA', 'TABLES')
            . ' WHERE TABLE_TYPE = \'BASE TABLE\' AND TABLE_CATALOG = '
            . $database->formatValue(DatabaseTypeType::string, $database->activeDatabase)
        )->getColumnValues('TABLE_NAME');
    }

    public function getTableColumnsAsArray(DatabaseSQL $database) {
        throw new \Exception('Unimplemented.');
    }

    public function getTableConstraintsAsArray(DatabaseSQL $database) {
        throw new \Exception('Unimplemented.');
    }

    public function getLanguage() {
        return 'sqlsrv';
    }
}