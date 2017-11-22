<?php
namespace Database\SQL;

use Database\DatabaseEngine;
use Database\DatabaseTypeType;

abstract class DatabaseDefinitionsMySQL extends DatabaseSQLStandard {
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
    public $indexQuoteStart = '`';
    public $indexQuoteEnd = '`';

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

    /**
     * @var bool MySQL does support a native bit() type that acts as we expect it to.
     */
    public $nativeBitfield = true;

    /**
     * @var string We enable MySQL's unique ON DUPLICATE KEY functionality. (This notably means that upserts are not entirely portable between MySQL and other DBs, though well-written ones that correctly specify the key constraints will work across DBMSs.)
     */
    public $upsertMode = 'onDuplicateKey';

    /**
     * @var string We enable MySQL's bog-standard ENUM() type.
     */
    public $enumMode = 'useEnum';

    /**
     * @var string We enable MySQL's COMMENT= tag on tables.
     */
    public $commentMode = 'useAttributes';

    /**
     * @var string We enable MySQL's INDEX tag on columns.
     */
    public $indexMode = 'useTableAttribute';

    /**
     * @var bool MySQL (well, InnoDB, at least) only supports either foreign keys or partioning. For performance, we use partioning.
     */
    public $foreignKeyMode = false;

    /**
     * @var bool We enable MySQL's PARTITION table attribute.
     */
    public $usePartition = true;

    public $tableTypes = array(
        DatabaseEngine::general => 'InnoDB',
        DatabaseEngine::memory  => 'MEMORY',
    );

    public function getTablesAsArray(DatabaseSQL $database) {
        return $database->rawQueryReturningResult('SELECT * FROM '
            . $database->formatValue(DatabaseSQL::FORMAT_VALUE_DATABASE_TABLE, 'INFORMATION_SCHEMA', 'TABLES')
            . ' WHERE TABLE_SCHEMA = '
            . $database->formatValue(DatabaseTypeType::string, $database->activeDatabase)
        )->getColumnValues('TABLE_NAME');
    }

    public function getTableColumnsAsArray(DatabaseSQL $database) {
        return $database->rawQueryReturningResult('SELECT * FROM '
            . $database->formatValue(DatabaseSQL::FORMAT_VALUE_DATABASE_TABLE, 'INFORMATION_SCHEMA', 'COLUMNS')
            . ' WHERE TABLE_SCHEMA = '
            . $database->formatValue(DatabaseTypeType::string, $database->activeDatabase)
        )->getColumnValues(['TABLE_NAME', 'COLUMN_NAME']);
    }

    public function getTableConstraintsAsArray(DatabaseSQL $database) {
        return $database->rawQueryReturningResult('SELECT * FROM '
            . $database->formatValue(DatabaseSQL::FORMAT_VALUE_DATABASE_TABLE, 'INFORMATION_SCHEMA', 'KEY_COLUMN_USAGE')
            . ' WHERE TABLE_SCHEMA = '
            . $database->formatValue(DatabaseTypeType::string, $database->activeDatabase)
            . ' AND REFERENCED_TABLE_NAME IS NOT NULL'
        )->getColumnValues(['TABLE_NAME', 'CONSTRAINT_NAME']);
    }

    public function getTableIndexesAsArray(DatabaseSQL $database) {
        return $database->rawQueryReturningResult('SELECT * FROM '
            . $database->formatValue(DatabaseSQL::FORMAT_VALUE_DATABASE_TABLE, 'INFORMATION_SCHEMA', 'STATISTICS')
            . ' WHERE TABLE_SCHEMA = '
            . $database->formatValue(DatabaseTypeType::string, $database->activeDatabase)
        )->getColumnValues(['TABLE_NAME', 'INDEX_NAME']);
    }

    public function getLanguage() {
        return 'mysql';
    }
}