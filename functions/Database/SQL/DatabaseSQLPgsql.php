<?php
namespace Database\SQL;

use Database\DatabaseResultInterface;
use Database\DatabaseEngine;
use Database\DatabaseTypeType;
use Database\DatabaseIndexType;

class DatabaseSQLPgsql extends DatabaseSQLStandard {
    use DatabaseReconnectOnSelectDatabaseTrait;

    /**
     * @var resource
     */
    public $connection = null;

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

    public $concatTypes = array(
        'both' => ' AND ', 'either' => ' OR ', 'not' => ' NOT '
    );

    public $keyTypeConstants = array(
        DatabaseIndexType::fulltext => '',
        DatabaseIndexType::primary => 'PRIMARY',
        DatabaseIndexType::unique => 'UNIQUE',
        DatabaseIndexType::index => '',
    );

    public $enumMode = 'useCreateType';
    public $commentMode = 'useCommentOn';
    public $indexMode = 'useCreateIndex';
    public $foreignKeyMode = 'useAlterTableConstraint';
    public $tableRenameMode = 'alterTable';
    public $perTableIndexes = false;

    /**
     * @var bool While Postgres supports a native bitfield type, it has very strange cast rules for it. Thus, it does not exhibit the expected behaviour, and we disable native bitfields.
     */
    public $nativeBitfield = false;

    /**
     * @var bool Enable PgSQL's CREATE TABLE IF NOT EXISTS support.
     */
    public $useCreateIfNotExist = true;

    /**
     * @var bool Enable DROP INDEX IF NOT EXISTS support on PgSQL.
     */
    public $useDropIndexIfExists = true;

    /**
     * @var bool Enable PgSQL's ON CONFLICT DO UPDATE upsert syntax. It will switch to 'selectThenInsertOrUpdate' once connected to the PgSQL server if the detected version is <9.5
     */
    public $upsertMode = 'onConflictDoUpdate';


    public function connect($host, $port, $username, $password, $database = false) {
        // keep the user and password in memory to allow for reconnects with selectdb
        $this->connectionUser = $username;
        $this->connectionPassword = $password;

        $this->connection = pg_connect("host=$host port=$port user=$username password=$password" . ($database ? " dbname=$database" : ''));
        $this->registerConnection($host, $port, $username, $password);

        if (!$this->connection) {
            return false;
        }
        else {
            $this->query('SET bytea_output = "escape"'); // PHP-supported binary escape format.

            if (floatval($this->getVersion()) < 9.5) {
                $this->upsertMode = 'selectThenInsertOrUpdate';
            }
            return $this->connection;
        }
    }

    public function getVersion() {
        return pg_version($this->connection)['server'];
    }

    public function getLastError() {
        return pg_last_error($this->connection);
    }

    public function close() {
        if (isset($this->connection)) {
            $function = @pg_close($this->connection);
            unset($this->connection);

            return $function;
        }
        else {
            return true;
        }
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

    public function queryReturningResult($rawQuery) : DatabaseResultInterface {
        return $this->getResult($this->query($rawQuery));
    }

    public function getLastInsertId() {
        return pg_fetch_array($this->query('SELECT LASTVAL() AS lastval'))['lastval'];
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

    protected function getResult($source) {
        return new class($source) implements DatabaseResultInterface {
            /**
             * @var resource The postgres resource returned by query.
             */
            public $source;

            /**
             * @var array An array containing the field numbers corresponding to all binary columns in the current resultset.
             */
            public $binaryFields = [];

            /**
             * @var array An array containing the field numbers corresponding to all integer columns in the current resultset, used to convert NULL to 0 (instead of ""). This may not be needed on new versions of Postgres.
             */
            public $integerFields = [];

            public function __construct($source) {
                $this->source = $source;

                $num = pg_num_fields($this->source);
                for ($i = 0; $i < $num; $i++) {
                    if (pg_field_type($this->source, $i) === 'bytea') {
                        $this->binaryFields[] = pg_field_name($this->source, $i);
                    }

                    // ...not actually positive this is needed. Need more testing.
                    if (pg_field_type($this->source, $i) === 'int2' || pg_field_type($this->source, $i) === 'int4') {
                        $this->integerFields[] = pg_field_name($this->source, $i);
                    }
                }
            }

            public function fetchAsArray() {
                $data = pg_fetch_assoc($this->source);

                // Decode bytea values
                if ($data) {
                    foreach ($this->binaryFields AS $field) {
                        $data[$field] = pg_unescape_bytea($data[$field]);
                    }

                    foreach ($this->integerFields AS $field) {
                        $data[$field] = (int) $data[$field];
                    }
                }

                return $data;
            }

            public function getCount() {
                return pg_num_rows($this->source);
            }
        };
    }


    public function notify() {
        return pg_get_notify($this->connection);
    }

    public function getTablesAsArray(DatabaseSQL $database) {
        return $database->rawQueryReturningResult('SELECT * FROM information_schema.tables WHERE table_catalog = '
            . $database->formatValue(DatabaseTypeType::string, $database->activeDatabase)
            . ' AND table_type = \'BASE TABLE\' AND table_schema NOT IN (\'pg_catalog\', \'information_schema\')'
        )->getColumnValues('table_name');
    }

    public function getTableColumnsAsArray(DatabaseSQL $database) {
        return $database->rawQueryReturningResult('SELECT * FROM information_schema.columns WHERE table_catalog = '
            . $database->formatValue(DatabaseTypeType::string, $database->activeDatabase)
            . ' AND table_schema NOT IN (\'pg_catalog\', \'information_schema\')'
        )->getColumnValues(['table_name', 'column_name']);
    }

    public function getTableConstraintsAsArray(DatabaseSQL $database) {
        return $database->rawQueryReturningResult('SELECT * FROM information_schema.table_constraints WHERE table_catalog = '
            . $database->formatValue(DatabaseTypeType::string, $database->activeDatabase)
            . ' AND table_schema NOT IN (\'pg_catalog\', \'information_schema\')'
            . ' AND constraint_type = \'FOREIGN KEY\''
        )->getColumnValues(['table_name', 'constraint_name']);
    }

    /**
     * It is not possible to get the indexes of tables in PgSQL.
     *
     * @return array an empty array
     */
    public function getTableIndexesAsArray(DatabaseSQL $database) {
        return [];
    }

    public function getLanguage() {
        return 'pgsql';
    }
}