<?php
/* FreezeMessenger Copyright © 2017 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */
namespace Database\SQL;

use Exception;

use Database\Database;
use Database\DatabaseResult;
use Database\DatabaseResultInterface;
use Database\DatabaseIndexType;
use Database\DatabaseEngine;
use Database\DatabaseType;
use Database\DatabaseTypeType;
use Database\DatabaseTypeComparison;

/**** BRIEF INTRODUCTION ****/
/* This file is the MySQL-version (and the only one currently existing) of a generic database layer created for FreezeMessenger. What purpose could it possibly serve? Why not go the PDO-route? Mainly, it offers a few distinct advantages: full control, easier to modify by plugins (specifically, in that most data is stored in a tree structure), and perhaps more importantly it allows things that PDO, which is fundamentally an SQL extension, doesn't. There is no shortage of database foundations bearing almost no semblance to SQL: IndexedDB (which has become popular by-way of web-browser implementation), Node.JS (which I would absolutely love to work with but currently can't because of the MySQL requirement), and others come to mind.
 * As with everything else, this is GPL-based, but if anyone decides they like it and may wish to use it for less restricted purposes, contact me. I have considered going LGPL/MIT/BSD with it, but not yet :P */

/**** EXTENSIBILITY NOTES ****/
/* databaseSQL, unlike database, does not support extended languages. The class attempts to handle all variations of SQL (though, at the moment, obviously doesn't -- there will certainly need to be driver-specific code added). As a result, nearly all query strings are abstracted in some way or another with the hope that different SQL variations can be better accomodated with the least amount of code. That said, if a variation is to be added, it needs to be added to databaseSQL. */

/**** SUPPORT NOTES ****/
/* The following is a basic changelog of key MySQL features since 4.1, without common upgrade conversions, slave changes, and logging changes included:
 * 4.1.0: Database, table, and column names are now stored UTF-8 (previously ASCII).
 * 4.1.0: Binary values are treated as strings instead of numbers by default now. (use CAST())
 * 4.1.0: DELETE statements no longer require that named tables be used instead of aliases (e.g. "DELETE t1 FROM test AS t1, test2 WHERE ..." previously had to be "DELETE test FROM test AS t1, test2 WHERE ...").
 * 4.1.0: LIMIT can no longer be negative.
 * 4.1.1: User-defined functions must contain xxx_clear().
 * 4.1.2: When comparing strings, the shorter string will now be right-padded with spaces. Previously, spaces were truncated entirely. Indexes should be rebuilt as a result.
 * 4.1.2: Float previously allowed higher values than standard. While previously FLOAT(3,1) could be 100.0, it now must not exceed 99.9.
 * 4.1.2: When using "SHOW TABLE STATUS", the old column "type" is now "engine".
 * 4.1.3: InnoDB indexes using latin1_swedish_ci from 4.1.2 and earlier should be rebuilt (using OPTIMIZE).
 * 4.1.4: Tables with TIMESTAMP columns created between 4.1.0 and 4.1.3 must be rebuilt.
 * 5.0.0: ISAM removed. Do not use. (To update, run "ALTER TABLE tbl_name ENGINE = MyISAM;")
 * 5.0.0: RAID features in MySIAM removed.
 * 5.0.0: User variables are not case sensitive in 5.0, but were prior.
 * 5.0.2: "SHOW STATUS" behavior is global before 5.0.2 and sessional afterwards. 'SHOW /*!50002 GLOBAL *\/ STATUS;' can be used to trigger global in both.
 * 5.0.2: NOT Parsing. Prior to 5.0.2, "NOT a BETWEEN b AND c" was parsed as "(NOT a) BETWEEN b AND ". Beginning in 5.0.2, it is parsed as "NOT (a BETWEEN b AND c)". The SQL mode "HIGH_NOT_PRECEDENCE" can be used to trigger the old mode. (http://dev.mysql.com/doc/refman/5.0/en/server-sql-mode.html#sqlmode_high_not_precedence).
 * 5.0.3: User defined functions must contain aux. symbols in order to run.
 * 5.0.3: The BIT Type. Prior to 5.0.3, the BIT type is a synonym for TINYINT(1). Beginning with 5.0.3, the BIT type accepts an additional argument (e.g. BIT(2)) that species the number of bits to use. (http://dev.mysql.com/doc/refman/5.0/en/bit-type.html)
 ** Due to performance, BIT is far better than TINYINT(1). Both, however, are supported in this class.
 * 5.0.3: Trailing spaces are not removed from VARCHAR and VARBINARY columns, but they were prior
 * 5.0.3: Decimal handling was changed (tables created prior to the change will maintain the old behaviour): (http://dev.mysql.com/doc/refman/5.0/en/precision-math-decimal-changes.html)
 ** Decimals are handled as binary; prior they were handled as strings.
 ** When handled as strings, the "-" sign could be replaced with any number, extending the range of DECIMAL(5,2) from the current (and standard) [-999.99,999.99] to [-999.99,9999.99], while preceeding zeros and +/- signs were maintained when stored.
 ** Additionally, prior to 5.0.3, the maximum number of digits is 264 (precise to ~15 depending on the host machine), from 5.0.3 to 5.0.5 it is 64 (precise to 64), and from 5.0.6 the maximum number of digits is 65 (precise to 65).
 ** Finally, while prior to this change both exact- and approximate-value literals were handled as double-precision floating point, now exact-value literals will be handled as decimal.
 * 5.0.6: Tables with DECIMAL columns created between 5.0.3 and 5.0.5 must be rebuilt.
 * 5.0.8: "DATETIME+0" yields YYYYMMDDHHMMSS.000000, but previously yielded YYYYMMDDHHMMSS.
 * 5.0.12: NOW() and SYSDATE() are no longer identical, with the latter be the time at script execution and the former at statement execution time (approximately).
 * 5.0.13: The GREATEST() and LEAST() functions return NULL when a passed parameter is NULL. Prior, they ignored NULL values.
 * 5.0.13: Substraction from an unsigned integer varies. Prior to 5.0.13, the bits of the subtracted value is used for the result (e.g. i-1, where i is TINYINT and 0, is the same as 0-2^64). In 5.0.13, it retains the bits of the original (e.g. it now would be 0-2^8). If comparing
 * 5.0.15: The pad value for BINARY has changed from a space to \0, as has the handling of these. Using a BINARY(3) type with a value of 'a ' to illustrate: in the original, SELECT, DISTINCT, and ORDER BY operations remove all trailing spaces ('a'), while in the new version SELECT, DISTINCT, and ORDER BY maintain all additional null bytes ('a \0'). InnoDB still uses trailing spaces ('a  '), and did not remove the trailing spaces until 5.0.19 ('a').
 * 5.0.15: CHAR() returns a binary string instead of a character set. A "USING" may be used instead to specify a character set. For instance, SELECT CHAR() returns a VARBINARY but previously would have returned VARCHAR (similarly, CHAR(ORD('A')) is equvilent to 'a' prior to this change, but now would only be so if a latin character set is specified.).
 * 5.0.25: lc_time_names will affect the display of DATE_FORMAT(), DAYNAME(), and MONTHNAME().
 * 5.0.42: When DATE and DATETIME interact, DATE is now converted to DATETIME with 00:00:00. Prior to 5.0.42, DATETIME would instead lose its time portion. CAST() can be used to mimic the old behavior.
 * 5.0.50: Statesments containing "/*" without "*\/" are no longer accepted.
 * 5.1.0: table_cache -> table_open_cache
 * 5.1.0: "-", "*", "/", POW(), and EXP() now return NULL if an error is occured during floating-point operations. Previously, they may return "+INF", "-INF", or NaN.
 * 5.1.23: In stored routines, a cursor may no longer be used in SHOW and DESCRIBE statements.
 * 5.1.15: READ_ONLY

 * Other incompatibilities that may be encountered:
 * Reserved Words Added in 5.0: http://dev.mysql.com/doc/mysqld-version-reference/en/mysqld-version-reference-reservedwords-5-0.html.
  ** This class puts everything in quotes to avoid this and related issues.
  ** Some upgrades may require rebuilding indexes. We are not concerned with these, but a script that automatically rebuilds indexes as part of databaseSQL.php would have its merits. It could then also detect version upgrades.
  ** Previously, TIMESTAMP(N) could specify a width of "N". It was ignored in 4.1, deprecated in 5.1, and removed in 5.5. Don't use it.
  ** UDFs should use a database qualifier to avoid issues with defined functions.
  ** The JOIN syntax was changed in MySQL 5.0.12. The new syntax will work with old versions, however (just not the other way around).
  ** Avoid equals comparison with floating point values.
  ** Timestamps are seriously weird in MySQL. Honestly, avoid them.
  *** 4.1 especially contains oddities: (http://dev.mysql.com/doc/refman/4.1/en/timestamp.html)

 * Further Reading:
 ** http://dev.mysql.com/doc/refman/5.0/en/upgrading-from-previous-series.html */


/* PostGreSQL (better list @ http://www.postgresql.org/about/featurematrix/)
 * 8.0: savepoints, ability to alter column type, table spaces
 * 8.1: Two-phase commit, new permissions system,
 * 8.2: RETURNING, nulls in arrays,
 * 8.3: Full text search, XML, ENUM data types, UUID type,
 * 8.4: Column permissions, per-database locale,
 * 9.0: 64-bit WIN support, better LISTEN/NOTIFY perfrmance, per-column triggers
 * 9.1: Sync. replication, foreign tables,
 * 9.2: Index-only scans, cascading replication, range data types, JSON data type,
 */

class DatabaseSQL extends Database
{
    public $classVersion = 3;
    public $classProduct = 'fim';

    /**
     * @var string The full version of the DBMS we are connected to.
     */
    public $versionString = '0.0.0';

    /**
     * @var int|string The primary version (e.g. 4 in 4.2.1) of the DBMS we are connected to.
     */
    public $versionPrimary = 0;

    /**
     * @var int|string The secondary version (e.g. 2 in 4.2.1) of the DBMS we are connected to.
     */
    public $versionSecondary = 0;

    /**
     * @var int|string The tertiary version (e.g. 1 in 4.2.1) of the DBMS we are connected to.
     */
    public $versionTertiary = 0;

    /**
     * @var string The database mode. This will always be SQL for us.
     */
    public $mode = 'SQL';

    /**
     * @var string The driver currently in use. One of "mysql", "mysqli", "pdo-mysql", "pgsql", "pdo-pgsql"
     */
    public $driver;

    /**
     * @var string The language currently in used. One of "mysql", "pgsql"
     */
    public $language;

    /**
     * @var DatabaseSQLStandard
     */
    public $sqlInterface;

    /**
     * @var array Maps between drivers and languages.
     *            TODO: remove
     */
    private $driverMap = array(
        'mysql' => 'mysql',
        'mysqli' => 'mysql',
        'pdoMysql' => 'mysql',
        'pgsql' => 'pgsql',
    );

    /**
     * @var array All queries will be stored here during execution.
     */
    public $queryLog = array();

    /**
     * @var bool|string If set to a file string, queries will be logged to this file.
     */
    public $queryLogToFile = false;

    /**
     * @var bool If rawQuery should return its query instead of executing it. Ideal for simulation and testing.
     */
    public $returnQueryString = false;

    /**
     * @var bool If enabled, triggers will be placed in {@link DatabaseSQL::triggerQueue}, and won't be run.
     */
    protected $holdTriggers = false;

    /**
     * @var array A queue for triggers when {@link DatabaseSQL::holdTriggers} is enabled.
     */
    protected $triggerQueue = [];



    /*********************************************************
     ************************ START **************************
     ******************* General Functions *******************
     *********************************************************/

    public function __destruct() {
        $this->rollbackTransaction();
        $this->close();

        if ($this->queryLogToFile) {
            file_put_contents($this->queryLogToFile, '*****' . $_SERVER['SCRIPT_FILENAME'] . '***** (Max Memory: ' . memory_get_peak_usage() . ') ' . PHP_EOL . print_r($this->queryLog, true) . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }


    /**
     * Autodetect how to format.
     * In most cases, this will format either an an integer or a string. Refer to {@link database::auto()} for more information.
     */
    const FORMAT_VALUE_DETECT = 'detect';


    /**
     * Format for fuzzy searching.
     */
    const FORMAT_VALUE_SEARCH = 'search';

    /**
     * Format as a column alias.
     * When used, the first variable argument is the column name and the second variable argument is the alias name.
     */
    const FORMAT_VALUE_COLUMN_ALIAS = 'columnAlias';

    /**
     * Format as a table name.
     */
    const FORMAT_VALUE_TABLE = 'table';

    /**
     * Format as a column name with a table name.
     * When used, the first variable argument is the table name and the second variable argument is the column name.
     */
    const FORMAT_VALUE_TABLE_COLUMN = 'tableColumn';

    /**
     * Format as a table alias.
     * When used, the first variable argument is the table name and the second variable argument is the table alias.
     */
    const FORMAT_VALUE_TABLE_ALIAS = 'tableAlias';

    /**
     * Format as a column name with a table name.
     * When used, the first variable argument is the table name, the second variable argument is the column name, and the third variable argument is the column alias.
     */
    const FORMAT_VALUE_TABLE_COLUMN_NAME_ALIAS = 'tableColumnNameAlias';

    /**
     * Format as a database name.
     */
    const FORMAT_VALUE_DATABASE = 'database';

    /**
     * Format as a table name with an attached database name.
     * When used, the first variable argument is the database name and the second variable argument is the table name.
     */
    const FORMAT_VALUE_DATABASE_TABLE = 'databaseTable';

    /**
     * Format as a table index name.
     */
    const FORMAT_VALUE_INDEX = 'index';

    /**
     * Format as an array of values used in an ENUM.
     */
    const FORMAT_VALUE_ENUM_ARRAY = 'enumArray';

    /**
     * Format as a table alias with an attached table name.
     * When used, the first variable argument is the table name and the second variable argument is the alias name.
     */
    const FORMAT_VALUE_TABLE_NAME_ALIAS = 'tableNameAlias';

    /**
     * Format as an array of update clauses.
     */
    const FORMAT_VALUE_UPDATE_ARRAY = 'updateArray';

    /**
     * Format as an array of columns.
     */
    const FORMAT_VALUE_COLUMN_ARRAY = 'columnArray';

    /**
     * Format as an array of table columns and corresponding values.
     * When used, the first variable argument is the table name, the second variable argument is a list of columns, and the third variable argument is a list of values.
     */
    const FORMAT_VALUE_TABLE_COLUMN_VALUES = 'tableColumnValues';

    /**
     * Format as an array of table columns and corresponding values.
     * When used, the first variable argument is the table name and the second variable argument is a an associative array of columns-value pairs, indexed by column name.
     */
    const FORMAT_VALUE_TABLE_UPDATE_ARRAY = 'tableUpdateArray';

    /**
     * Format a value to represent the specified type in an SQL query.
     *
     * @param DatabaseTypeType|FORMAT_VALUE_* type The type to format the value(s) as. All DatabaseTypeType constants can be used (and will format as expected). The other types are all databaseSQL constants named "FORMAT_VALUE_*"; refer to their documentation seperately.
     * @param mixed $values,... The values to be formatted. Instances of DatabaseTypeType typically only take one value. For FORMAT_VALUE_* types, refer to their own documentation.
     *
     * @return mixed Value, formatted as specified.
     *
     * @throws Exception
     */
    public function formatValue($type)
    {
        $values = func_get_args();

        switch ($type) {
            case DatabaseSQL::FORMAT_VALUE_DETECT:
                $item = $this->auto($values[1]);

                return $this->formatValue($item->type, $item->value);
                break;

            case DatabaseTypeType::null:
                return 'NULL';
                break;

            case DatabaseSQL::FORMAT_VALUE_SEARCH:
                return $this->sqlInterface->stringQuoteStart
                    . $this->sqlInterface->stringFuzzy
                    . $this->escape(addcslashes($values[1], '%_\\'), $type) // TODO?
                    . $this->sqlInterface->stringFuzzy
                    . $this->sqlInterface->stringQuoteEnd;
                break;

            case DatabaseTypeType::string:
                return $this->sqlInterface->stringQuoteStart
                    . $this->escape($values[1], $type)
                    . $this->sqlInterface->stringQuoteEnd;
                break;

            case DatabaseTypeType::bool:
                return $this->sqlInterface->boolValues[$values[1]];
                break;

            case DatabaseTypeType::blob:
                return $this->sqlInterface->binaryQuoteStart
                    . $this->escape($values[1], $type)
                    . $this->sqlInterface->binaryQuoteEnd;
                break;

            case DatabaseTypeType::bitfield:
                if ($this->sqlInterface->nativeBitfield)
                    return 'B\'' . decbin((int) $values[1]) . '\'';
                else
                    return $this->formatValue(DatabaseTypeType::integer, $values[1]);
            break;

            case DatabaseTypeType::integer:
                return $this->sqlInterface->intQuoteStart
                    . $this->escape((int)$values[1], $type)
                    . $this->sqlInterface->intQuoteEnd;
                break;

            case DatabaseTypeType::float:
                return $this->sqlInterface->floatQuoteStart
                    . (float) $this->escape($values[1], $type)
                    . $this->sqlInterface->floatQuoteEnd;
            break;

            case DatabaseTypeType::timestamp:
                return $this->sqlInterface->timestampQuoteStart
                    . $this->escape((int) $values[1], $type)
                    . $this->sqlInterface->timestampQuoteEnd;
                break;

            case DatabaseTypeType::column:
                return $this->sqlInterface->columnQuoteStart
                    . $this->escape($values[1], $type)
                    . $this->sqlInterface->columnQuoteEnd;
                break;

            case DatabaseTypeType::equation:  // Only partially implemented, because equations are stupid. Don't use them if possible.
                return preg_replace_callback('/\$(([a-zA-Z_]+)\.|)([a-zA-Z]+)/', function ($matches) {
                    if ($matches[1])
                        return $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_COLUMN, $matches[2], $matches[3]);
                    else
                        return $this->formatValue(DatabaseTypeType::column, $matches[3]);
                }, $values[1]);
            break;

            case DatabaseTypeType::arraylist:
                foreach ($values[1] AS &$item) {
                    $item = $this->auto($item);
                    $item = $this->formatValue($item->type, $item->value);
                }

                return $this->sqlInterface->arrayQuoteStart
                    . implode($this->sqlInterface->arraySeperator, $values[1])
                    . $this->sqlInterface->arrayQuoteEnd;
            break;

            case DatabaseSQL::FORMAT_VALUE_COLUMN_ALIAS:
                return $this->sqlInterface->columnAliasQuoteStart
                    . $this->escape($values[1], $type)
                    . $this->sqlInterface->columnAliasQuoteEnd;
                break;

            case DatabaseSQL::FORMAT_VALUE_TABLE:
                return $this->sqlInterface->tableQuoteStart
                    . $this->escape($values[1], $type)
                    . $this->sqlInterface->tableQuoteEnd;
                break;

            case DatabaseSQL::FORMAT_VALUE_TABLE_ALIAS:
                return $this->sqlInterface->tableAliasQuoteStart
                    . $this->escape($values[1], $type)
                    . $this->sqlInterface->tableAliasQuoteEnd;
                break;

            case DatabaseSQL::FORMAT_VALUE_DATABASE:
                return $this->sqlInterface->databaseQuoteStart
                    . $this->escape($values[1], $type)
                    . $this->sqlInterface->databaseQuoteEnd;
                break;

            case DatabaseSQL::FORMAT_VALUE_INDEX:
                return $this->sqlInterface->indexQuoteStart
                    . $this->escape($values[1], $type)
                    . $this->sqlInterface->indexQuoteEnd;
                break;

            case DatabaseSQL::FORMAT_VALUE_ENUM_ARRAY:
                foreach ($values[1] AS &$item) {
                    $item = $this->str($item);
                }

                return $this->formatValue(DatabaseTypeType::arraylist, $values[1]);
            break;

            case DatabaseSQL::FORMAT_VALUE_COLUMN_ARRAY:
                foreach ($values[1] AS &$item)
                    $item = $this->formatValue(DatabaseTypeType::column, $item);

                return $this->sqlInterface->arrayQuoteStart
                    . implode($this->sqlInterface->arraySeperator, $values[1])
                    . $this->sqlInterface->arrayQuoteEnd;
                break;

            case DatabaseSQL::FORMAT_VALUE_UPDATE_ARRAY:
                $update = array();

                foreach ($values[1] AS $column => $value) {
                    $update[] = $this->formatValue(DatabaseTypeType::column, $column)
                        . $this->sqlInterface->comparisonTypes[DatabaseTypeComparison::assignment]
                        . $this->formatValue(DatabaseSQL::FORMAT_VALUE_DETECT, $value);
                }

                return implode($update, $this->sqlInterface->statementSeperator);
                break;

            case DatabaseSQL::FORMAT_VALUE_TABLE_COLUMN:
                return $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $values[1])
                    . $this->sqlInterface->tableColumnDivider
                    . $this->formatValue(DatabaseTypeType::column, $values[2]);
                break;

            case DatabaseSQL::FORMAT_VALUE_DATABASE_TABLE:
                return $this->formatValue(DatabaseSQL::FORMAT_VALUE_DATABASE, $values[1])
                    . $this->sqlInterface->databaseTableDivider
                    . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $values[2]);
                break;

            case DatabaseSQL::FORMAT_VALUE_TABLE_COLUMN_NAME_ALIAS:
                return $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $values[1])
                    . $this->sqlInterface->tableColumnDivider
                    . $this->formatValue(DatabaseTypeType::column, $values[2])
                    . $this->sqlInterface->columnAliasDivider
                    . $this->formatValue(DatabaseSQL::FORMAT_VALUE_COLUMN_ALIAS, $values[3]);
                break;

            case DatabaseSQL::FORMAT_VALUE_TABLE_NAME_ALIAS:
                return $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $values[1])
                    . $this->sqlInterface->tableAliasDivider
                    . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_ALIAS, $values[2]);
                break;

            case DatabaseSQL::FORMAT_VALUE_TABLE_COLUMN_VALUES:
                $tableName = $values[1];

                /* Copy & transform values
                 * Some columns get inserted as-is, but a transformed copy is also then added. When we are modifying such a column, we create the copy here.
                 * If the copy is modified independently, it will not be altered here -- but it should also not be modified independently.
                 * TODO: items stored as DatabaseType will not be detected properly
                 */
                if (isset($this->encodeCopy[$tableName])) { // Do we have copy & transform values for the table we are inserting into?
                    foreach ($this->encodeCopy[$tableName] AS $startColumn => $endResult) { // For each copy & transform value in our table...
                        list($endFunction, $typeOverride, $endColumn) = $endResult;

                        if (($key = array_search($startColumn, $values[2])) !== false) { // Check to see if we are, in-fact, inserting the column
                            $values[2][] = $endColumn;
                            $values[3][] = $this->applyTransformFunction($endFunction, $values[3][$key], $typeOverride); // And if we are, add the new copy column to the list of insert columns
                        }
                    }
                }

                // Columns
                foreach ($values[2] AS $key => &$column) {
                    if (isset($this->encode[$tableName]) && isset($this->encode[$tableName][$column])) {
                        list($function, $typeOverride) = $this->encode[$tableName][$column];

                        $values[3][$key] = $this->applyTransformFunction($function, $values[3][$key], $typeOverride);
                    }

                    $column = $this->formatValue(DatabaseTypeType::column, $column);
                }

                // Values
                foreach ($values[3] AS &$item) {
                    if (!$this->isTypeObject($item)) {
                        $item = $this->auto($item);
                    }

                    $item = $this->formatValue($item->type, $item->value);
                }

                // Combine as list.
                return $this->sqlInterface->arrayQuoteStart
                    . implode($this->sqlInterface->arraySeperator, $values[2])
                    . $this->sqlInterface->arrayQuoteEnd
                    . ' VALUES '
                    . $this->sqlInterface->arrayQuoteStart
                    . implode($this->sqlInterface->arraySeperator, $values[3])
                    . $this->sqlInterface->arrayQuoteEnd;
            break;

            case DatabaseSQL::FORMAT_VALUE_TABLE_UPDATE_ARRAY:
                $tableName = $values[1];
                $update = array();

                /* Copy & transform values
                 * Some columns get inserted as-is, but a transformed copy is also then added. When we are modifying such a column, we create the copy here.
                 * If the copy is modified independently, it will not be altered here -- but it should also not be modified independently. *
                 */
                if (isset($this->encodeCopy[$tableName])) { // Do we have copy & transform values for the table we are updating?
                    foreach ($this->encodeCopy[$tableName] AS $startColumn => $endResult) { // For each copy & transform value in our table...
                        list($endFunction, $typeOverride, $endColumn) = $endResult;

                        if (isset($values[2][$startColumn])) // Check to see if we are, in-fact, updating the column
                            $values[2][$endColumn] = $this->applyTransformFunction($endFunction, $values[2][$startColumn], $typeOverride); // And if we are, add the new copy column to the list of update columns
                    }
                }

                /* Process each column and value pair */
                foreach ($values[2] AS $column => $value) {
                    /* Transform values
                     * Some columns get transformed prior to being sent to the database: we handle those here. */
                    if (isset($this->encode[$tableName]) && isset($this->encode[$tableName][$column])) {
                        list($function, $typeOverride) = $this->encode[$tableName][$column];

                        $value = $this->applyTransformFunction($function, $value, $typeOverride);
                    }

                    /* Format and add the column/value pair to our list */
                    $update[] = $this->formatValue(DatabaseTypeType::column, $column)
                        . $this->sqlInterface->comparisonTypes[DatabaseTypeComparison::assignment]
                        . $this->formatValue(DatabaseSQL::FORMAT_VALUE_DETECT, $value);
                }

                /* Return our list of paired values as an string */
                return implode($update, $this->sqlInterface->statementSeperator);
            break;

            default:
                throw new Exception("databaseSQL->formatValue does not recognise type '$type'");
                break;
        }
    }



    /** Formats two columns or table names such that one is an alias.
     *
     * @param string value - The value (column name/table name).
     *
     * @internal Needless to say, this is quite the simple function. However, I feel that the syntax merits it, as there are certainly other ways an "AS" could be structure. (Most wouldn't comply with SQL, but strictly speaking I would like this class to work with slight modifications of SQL as well, if any exist.)
     *
     * @param string alias - The alias.
     * @return string - The formatted SQL string.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    /*  private function formatAlias($value, $alias, $type) {
        switch ($type) {
          case 'column': case 'table': return "$value AS $alias"; break;
        }
      }*/


    private function setDatabaseVersion($versionString)
    {
        $versionString = (string)$versionString;
        $this->versionString = $versionString;
        $strippedVersion = '';

        // Get the version without any extra crap (e.g. "5.0.0.0~ubuntuyaypartytimeohohohoh").
        for ($i = 0; $i < strlen($versionString); $i++) {
            if (in_array($versionString[$i], array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'), true)) $strippedVersion .= $versionString[$i];
            else break;
        }

        // Divide the decimal versions into an array (e.g. 5.0.1 becomes [0] => 5, [1] => 0, [2] => 1) and set the first three as properties.
        $strippedVersionParts = explode('.', $strippedVersion);

        $this->versionPrimary = $strippedVersionParts[0];
        $this->versionSecondary = $strippedVersionParts[1];
        $this->versionTertiary = $strippedVersionParts[2];


        // Compatibility check. We're really not sure how true any of this, and we have no reason to support older versions, but meh.
        // todo: move
        switch ($this->driver) {
            case 'mysql':
            case 'mysqli':
                if ($strippedVersionParts[0] <= 4) { // MySQL 4 is a no-go.
                    throw new Exception('You have attempted to connect to a MySQL version 4 database, which is unsupported.');
                }
                elseif ($strippedVersionParts[0] == 5 && $strippedVersionParts[1] == 0 && $strippedVersionParts[2] <= 4) { // MySQL 5.0.0-5.0.4 is also a no-go (we require the BIT type, even though in theory we could work without it)
                    $this->sqlInterface->nativeBitfield = false;
                }
                break;
        }
    }


    public function connect($host, $port, $user, $password, $database, $driver, $tablePrefix = '')
    {
        $this->sqlPrefix = $tablePrefix;


        /* Detect Incompatible MySQLi */
        if ($driver === 'mysqli' && PHP_VERSION_ID < 50209) { // PHP_VERSION_ID isn't defined with versions < 5.2.7, but this obviously isn't a problem here (it will eval to 0, which is indeed less than 50209).
            $driver = 'mysql';
        }


        /* Load DatabaseSQLInterface Driver from File */
        $className = '\Database\SQL\DatabaseSQL' . ucfirst($driver);

        if (!class_exists($className)) {
            throw new Exception('The specified DatabaseSQL driver is not installed.');
        }
        else {
            $this->sqlInterface = new $className();
        }


        /* Perform Connection */
        if (!$this->sqlInterface->connect($host, $port, $user, $password, $database)) { // Make the connection.
            $this->triggerError('Could Not Connect: ' . $this->sqlInterface->getLastError(), array( // Note: we do not include "password" in the error data.
                'host' => $host,
                'port' => $port,
                'user' => $user,
                'database' => $database
            ), 'connection');

            return false;
        }


        /* Select Database, If Needed (TODO: catch errors) */
        if (!$this->activeDatabase && $database) { // Some drivers will require this.
            if (!$this->selectDatabase($database)) { // Error will be issued in selectDatabase.
                return false;
            }
        }

        return true;
    }


    /**
     * Fetches {@link databaseSQL::versionPrimary}, {@link databaseSQL::versionSecondary}, and {@link databaseSQL::versionTertiary} from the current database connection.
     */
    public function loadVersion()
    {
        if ($this->versionPrimary > 0) // Don't reload information.
            return true;

        $this->setDatabaseVersion($this->sqlInterface->getVersion());

        return true;
    }


    public function close()
    {
        return $this->sqlInterface->close();
    }


    /**
     * Returns a properly escaped string for raw queries.
     *
     * @param string int|string - Value to escape.
     * @param string context - The value type, in-case the escape method varies based on it.
     * @return string
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */

    protected function escape($string, $context = DatabaseTypeType::string)
    {
        return $this->sqlInterface->escape($string, $context); // Return the escaped string.
    }


    /**
     * Sends a raw, unmodified query string to the database server.
     * The query may be logged if it takes a certain amount of time to execute successfully.
     *
     * @param string $query - The raw query to execute.
     * @return bool - True on success, false on failure.
     */
    public function rawQuery($query)
    {
        if ($this->returnQueryString) {
            $this->returnQueryString = false;

            return $query;
        }

        else {
            $start = microtime(true);

            if ($queryData = $this->sqlInterface->query($query)) {
                $this->newQuery($query, microtime(true) - $start);

                return true;
            }

            else {
                $this->newQuery($query);
                $this->triggerError($this->sqlInterface->getLastError(), $query);

                return false;
            }
        }
    }


    /**
     * Sends a raw, unmodified query string to the database server, expecting a resultset to be returned.
     * The query may be logged if it takes a certain amount of time to execute successfully.
     *
     * @param string $query - The raw query to execute.
     * @return DatabaseResult The database result returned by the query, or false on failure.
     */
    public function rawQueryReturningResult($query, $reverseAlias = false, int $paginate = 0) {

        $start = microtime(true);

        // todo: probably rewrite this, iunno
        $queryData = $this->sqlInterface->queryReturningResult($query);
        if ($queryData->source) {
            $this->newQuery($query, microtime(true) - $start);

            return $this->databaseResultPipe($queryData, $reverseAlias, $query, $this, $paginate);
        }

        else {
            $this->newQuery($query);
            $this->triggerError($this->sqlInterface->getLastError(), $query);

            return false;
        }

    }


    /**
     * Configures the Database abstraction layer to return the query string instead of executing it for the next query.
     * It will be automatically turned off as soon as a query is executed.
     * Can be chained.
     * Obviously, this should not be used in conjunction with functions that execute multiple queries simultaneously (though these are rare).
     *
     * @return $this
     */
    public function returnQueryString() {
        $this->returnQueryString = true;

        return $this;
    }


    /**
     * @see Database::databaseResultPipe()
     */
    protected function databaseResultPipe($queryData, $reverseAlias, string $sourceQuery, Database $database, int $paginated = 0)
    {
        return new DatabaseResult($queryData, $reverseAlias, $sourceQuery, $database, $paginated);
    }


    /**
     * Add the text of a query to the log. This should normally only be called by rawQuery(), but is left protected since other purposes could exist by design.
     *
     * @return string - The query text of the last query executed.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */

    protected function newQuery($queryText, $microtime = false)
    {
        $this->queryCounter++;
        $this->queryLog[] = [$queryText/*, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)*/, $microtime];
    }


    /**
     * Get the text of the last query executed.
     *
     * @return string - The query text of the last query executed.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function getLastQuery()
    {
        return end($this->queryLog)[0];
    }


    /**
     * Clears the query log.
     *
     * @return string - The query text of the last query executed.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function clearQueries()
    {
        $this->queryLog = array();
    }

    /*********************************************************
     ************************* END ***************************
     ******************* General Functions *******************
     *********************************************************/


    /*********************************************************
     ************************ START **************************
     ********************* Transactions **********************
     *********************************************************/


    /* Basic Usage:
     * Transactions are effectively automatic. Scripts should call start and end transaction. A rollback will occur as part of a database error, and the database connection will automatically be closed.
     * In other words, these transactions are super duper basic. This has benefits -- it means writing less code, which, honestly, is something I'm happy with. */


    public function startTransaction()
    {
        $this->transaction = true;

        $this->sqlInterface->startTransaction();
    }


    public function rollbackTransaction()
    {
        if ($this->transaction) {
            $this->transaction = false;

            $this->sqlInterface->rollbackTransaction();
        }
    }


    public function endTransaction()
    {
        $this->transaction = false;

        $this->sqlInterface->endTransaction();
    }

    public function holdTriggers($holdTriggers)
    {
        if (!$holdTriggers && $this->holdTriggers) {
            foreach ($this->triggerQueue AS $trigger) {
                $this->rawQuery($trigger);
            }

            $this->triggerQueue = [];
        }

        $this->holdTriggers = $holdTriggers;
    }



    /*********************************************************
     ************************* END ***************************
     ********************* Transactions **********************
     *********************************************************/


    /*********************************************************
     ************************ START **************************
     ****************** Database Functions *******************
     *********************************************************/

    public function selectDatabase($database)
    {
        $error = false;

        if ($this->sqlInterface->selectDatabase($database)) { // Select the database.
            if ($this->sqlInterface->getLanguage() == 'mysql' || $this->sqlInterface->getLanguage() == 'mysqli') {
                if (!$this->rawQuery('SET NAMES "utf8"')) { // Sets the database encoding to utf8 (unicode).
                    $error = 'SET NAMES Query Failed';
                }
            }
        } else {
            $error = 'Failed to Select Database';
        }

        if ($error) {
            $this->triggerError($error);
            return false;
        } else {
            $this->activeDatabase = $database;
            return true;
        }
    }


    public function createDatabase($database)
    {
        if ($this->sqlInterface->useCreateIfNotExist) {
            return $this->rawQuery('CREATE DATABASE IF NOT EXISTS ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_DATABASE, $database));
        }
        else {
            try {
                return @$this->rawQuery('CREATE DATABASE ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_DATABASE, $database));
            } catch (Exception $ex) {
                return true;
            }
        }
    }

    /*********************************************************
     ************************* END ***************************
     ****************** Database Functions *******************
     *********************************************************/


    /*********************************************************
     ************************ START **************************
     ******************* Table Functions *********************
     *********************************************************/


    /**
     * Parses an array of column names, along with a table name and engine identifier,
     *
     * @param $tableName    string The name of the table whose columns are being parsed.
     * @param $tableIndexes array  The current known table indexes that apply to the columns.
     * @param $tableColumns array {
     *     An array of table column properties indexed by the table column name. Valid parameters:
     *
     *     'restrict'      array            An array of values to restrict the column to.
     *     'maxlen'        int              The maximum size of the data that can be put into the column.
     *     'autoincrement' bool             If the column should be a "serial" column that increments for each row.
     *     'default'       mixed            The default value for the column.
     *     'comment'       string           Information about the column for documentation purposes.
     *     'type'          DatabaseTypeType The column's type.
     * }
     * @param $engine DatabaseEngine The engine the table is using.
     *
     * @return array An array of four things: (1.) an array of SQL column statements, (2.) an array of triggers to run after creating columns (which will typically maintain default values), (3.) the array of indexes, which may have been modified, and (4.) additional SQL parameters to append to the CREATE TABLE statement, for instance "AUTO_INCREMENT=".
     * @throws Exception If enums are not supported and need to be used.
     */
    private function parseTableColumns($tableName, $tableColumns, $tableIndexes = [], $engine = DatabaseEngine::general) {
        /**
         * Additional table parameters to be appended at the end of the CREATE TABLE statement. For instance, "AUTO_INCREMENT=".
         */
        $tableProperties = '';

        /**
         * A list of SQL statements that contain the column components to be used with an SQL query.
         */
        $columns = [];

        /**
         * A list of SQL statements that contain triggers and should be run when creating a column.
         */
        $triggers = [];

        /* Process Each Column */
        foreach ($tableColumns AS $columnName => $column) {
            /**
             * Our column parameters. Defaults set, but checking is not performed.
             */
            $column = array_merge([
                'restrict' => false,
                'maxlen' => 10,
                'autoincrement' => false,
                'default' => null,
                'comment' => '',
            ], $column);

            /**
             * The SQL type identifier, e.g. "INT"
             */
            $typePiece = '';


            /* Process Column Types */
            switch ($column['type']) {
                /* The column is integral. */
                case DatabaseTypeType::integer:
                    // If we have limits of "serial" (sequential) datatypes, and we are a serial type (that is, we're autoincrementing), using the serial limits.
                    if (isset($this->sqlInterface->dataTypes['columnSerialLimits']) && $column['autoincrement'])
                        $intLimits = $this->sqlInterface->dataTypes['columnSerialLimits'];

                    // If we don't have "serial" datatype limits, or we aren't using a serial datatype (aren't autoincrementing), use normal integer limits.
                    else
                        $intLimits = $this->sqlInterface->dataTypes['columnIntLimits'];

                    // Go through our integer limits (keyed by increasing order)
                    foreach ($intLimits AS $length => $type) {
                        if ($column['maxlen'] <= $length) {
                            $typePiece = $intLimits[$length];
                            break;
                        }
                    }

                    // If we haven't found a valid type identifer, use the default.
                    if (!strlen($typePiece)) $typePiece = $intLimits['default'];

                    // If we don't have serial limits and are autoincrementing, use the AUTO_INCREMENT orthogonal type identifier.
                    if (!isset($this->sqlInterface->dataTypes['columnSerialLimits']) && $column['autoincrement']) {
                        switch ($this->sqlInterface->serialMode) {
                            case 'autoIncrement':
                                $typePiece .= ' AUTO_INCREMENT'; // On the type itself.
                                $tableProperties .= ' AUTO_INCREMENT = ' . (int)$column['autoincrement']; // And also on the table definition.
                                break;

                            case 'identity':
                                $typePiece .= ' IDENTITY(1,1)'; // On the type itself.
                                break;
                        }

                        // And also create an index for it, if we don't already have one.
                        if (!isset($tableIndexes[$columnName])) {
                            $tableIndexes[$columnName] = [
                                'type' => 'index',
                            ];
                        }
                    }
                break;


                /* The column is an integral that encodes bitwise information. */
                case DatabaseTypeType::bitfield:
                    // If our SQL engine support a BIT type, use it.
                    if ($this->sqlInterface->nativeBitfield) {
                        $typePiece = 'BIT(' . $column['bits'] . ')';
                    }

                    // Otherwise, use a predefined type identifier.
                    else {
                        if ($column['bits']) { // Do we have a bit size definition?
                            foreach ($this->sqlInterface->dataTypes['columnBitLimits'] AS $bits => $type) { // Search through our bit limit array, which should be in ascending order of bits.
                                if ($column['bits'] <= $bits) { // We have a definition that fits our constraint.
                                    $typePiece = $type;
                                    break;
                                }
                            }
                        }

                        if (!strlen($typePiece)) { // If no type identifier was found...
                            $typePiece = $this->sqlInterface->dataTypes['columnBitLimits']['default']; // Use the default.
                        }
                    }
                break;


                /* The column encodes time information, most often using an integral and unix timestamp. */
                case DatabaseTypeType::timestamp:
                    $typePiece = $this->sqlInterface->dataTypes[DatabaseTypeType::timestamp];
                break;


                /* The column encodes a boolean, most often using a BIT(1) or other small integral. */
                case DatabaseTypeType::bool:
                    $typePiece = $this->sqlInterface->dataTypes[DatabaseTypeType::bool];
                break;


                /* The column encodes a floating point, with unspecified precision. */
                case DatabaseTypeType::float:
                    $typePiece = $this->sqlInterface->dataTypes[DatabaseTypeType::float];
                break;


                /* The column is a textual string or a binary string. */
                case DatabaseTypeType::string:
                case DatabaseTypeType::blob:
                case DatabaseTypeType::json:

                    if ($column['type'] === DatabaseTypeType::json && $this->sqlInterface->dataTypes[DatabaseTypeType::json]) {
                        $typePiece = $this->sqlInterface->dataTypes[DatabaseTypeType::json];
                    }
                    else {
                        // Limits may differ depending on table type and column type. Get the correct array encoding limits.
                        $stringLimits = $this->sqlInterface->dataTypes['column' . ($column['type'] === DatabaseTypeType::blob ? 'Blob' : 'String') . ($engine === DatabaseEngine::memory ? 'Temp' : 'Perm') . 'Limits'];

                        // Search through the array encoding limits. This array should be keyed in increasing size.
                        foreach ($stringLimits AS $length => $type) {
                            if (!is_int($length)) continue; // allow default key

                            if ($column['maxlen'] <= $length) { // If we have found a valid type definition for our column's size...
                                if (in_array($type, $this->sqlInterface->dataTypes['columnNoLength']))
                                    $typePiece = $type; // If the particular datatype doesn't encode size information, omit it.
                                else
                                    $typePiece = $type . '(' . $column['maxlen'] . ')'; // Otherwise, use the type identifier with our size information.

                                break;
                            }
                        }

                        if (!strlen($typePiece)) { // If no type identifier was found...
                            $typePiece = $stringLimits['default']; // Use the default.
                        }
                    }
                break;


                /* The column is an enumeration of values. */
                case DatabaseTypeType::enum:
                    // There are many different ways ENUMs may be supported in SQL DBMSs. Select our supported one.
                    switch ($this->sqlInterface->enumMode) {
                        // Here, we create a special type to use as an enum. PostGreSQL does this.
                        case 'useCreateType':
                            $typePiece = $this->formatValue(DatabaseSQL::FORMAT_VALUE_INDEX, $tableName . '_' . $columnName);
                            $this->rawQuery('DROP TYPE IF EXISTS ' . $typePiece . ' CASCADE');
                            $this->rawQuery('CREATE TYPE ' . $typePiece . ' AS ENUM' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_ENUM_ARRAY, $column['restrict']));
                        break;

                        // Here, we use the built-in SQL ENUM. MySQL does this.
                        case 'useEnum':
                            $typePiece = 'ENUM' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_ENUM_ARRAY, $column['restrict']);
                        break;

                        // And here we use the CHECK() clause at the end of the type. MSSQL does this.
                        case 'useCheck':
                            $lengths = array_map('strlen', $column['restrict']);
                            $typePiece = 'VARCHAR('
                                    . max($lengths)
                                . ') CHECK ('
                                    . $this->formatValue(DatabaseTypeType::column, $columnName)
                                    . ' IN'
                                    . $this->formatValue(DatabaseTypeType::arraylist, $column['restrict'])
                                . ')';
                        break;

                        // We don't support ENUMs in the current database mode.
                        default: throw new Exception('Enums are unsupported in the active database driver.'); break;
                    }
                break;


                /* The column type value is invalid. */
                default:
                    $this->triggerError("Unrecognised Column Type", array(
                        'tableName' => $tableName,
                        'columnName' => $columnName,
                        'columnType' => $column['type'],
                    ), 'validation');
                break;
            }


            /* Process Defaults (only if column default is specified) */
            if ($column['default'] !== null) {
                // We use triggers here when the SQL implementation is otherwise stubborn, but FreezeMessenger is designed to only do this when it would otherwise be tedious. Manual setting of values is preferred in most cases. Right now, only __TIME__ supports them.
                // TODO: language trigger support check
                if ($column['default'] === '__TIME__') {
                    $triggerString = "{TABLENAME}_{$columnName}__TIME__";
                    $triggerName = $this->formatValue(DatabaseSQL::FORMAT_VALUE_INDEX, $triggerString);

                    switch ($this->sqlInterface->getLanguage()) {
                        case 'sqlsrv':
                            $triggers[] = 'ALTER TABLE '
                                . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName)
                                . " ADD CONSTRAINT {$triggerName} DEFAULT DATEDIFF(s, '1970-01-01 00:00:00', GETUTCDATE()) FOR "
                                . $this->formatValue(DatabaseTypeType::column, $columnName);
                            break;

                        case 'mysql': // This one is kinda just for testing. We should replace it with DEFAULT UNIX_TIMESTAMP.
                            $triggers[] = "DROP TRIGGER IF EXISTS {$triggerName}";
                            $triggers[] = "CREATE TRIGGER {$triggerName} BEFORE INSERT ON {TABLENAME} FOR EACH ROW SET NEW.{$columnName} = IF(NEW.{$columnName}, NEW.{$columnName}, UNIX_TIMESTAMP(NOW()))";
                            break;

                        case 'pgsql':
                            $triggers[] = "DROP TRIGGER IF EXISTS {$triggerName} ON " . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, '{TABLENAME}');
                            $triggers[] = "CREATE OR REPLACE FUNCTION {$triggerString}_function()
                            RETURNS TRIGGER AS $$
                            BEGIN
                                IF NEW.\"{$columnName}\" IS NULL THEN
                                    NEW.\"{$columnName}\" := FLOOR(EXTRACT(EPOCH FROM NOW()));
                                END IF;
                                RETURN NEW;
                            END;
                            $$ language 'plpgsql';";

                            $triggers[] = "CREATE TRIGGER {$triggerName} BEFORE INSERT ON "
                                . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, '{TABLENAME}')
                                . " FOR EACH ROW EXECUTE PROCEDURE {$triggerString}_function()";
                            break;
                    }
                }


                // If we have a valid identifier for the default, use it. (For instance, __TIME__ could be CURRENT_TIMESTAMP.)
                elseif (isset($this->sqlInterface->defaultPhrases[$column['default']])) {
                    $typePiece .= ' DEFAULT ' . $this->sqlInterface->defaultPhrases[$column['default']];
                }


                // Finally, just normal default constants.
                else {
                    // If we have transformation parameters set for the column, transform our default value first.
                    if (@isset($this->encode[$tableName][$columnName])) {
                        list($function, $typeOverride) = $this->encode[$tableName][$columnName];

                        $column['default'] = $this->applyTransformFunction($function, $column['default'], $typeOverride);
                    }
                    else {
                        $column['default'] = new DatabaseType($column['type'], $column['default']);
                    }

                    $typePiece .= ' DEFAULT '
                        . $this->formatValue($column['default']->type === DatabaseTypeType::enum
                            ? DatabaseTypeType::string
                            : $column['default']->type
                        , $column['default']->value);
                }
            }


            /* Generate COMMENT ON Statements, If Needed */
            switch ($this->sqlInterface->commentMode) {
                case 'useCommentOn':
                    $triggers[] = $this->returnQueryString()->createTableColumnComment($tableName, $columnName, $column['comment']);
                    break;

                case 'useAttributes':
                    $typePiece .= ' COMMENT ' . $this->formatValue(DatabaseTypeType::string, $column['comment']);
                    break;
            }


            /* Generate Foreign Key Restrictions */
            if ($this->isTypeObject($column['restrict'])) {
                if ($column['restrict']->type === DatabaseTypeType::tableColumn) {
                    if ($this->sqlInterface->foreignKeyMode) {
                        if ($returnedQuery = $this->returnQueryString()->deleteForeignKeyConstraintFromColumnName($tableName, $columnName))
                            $triggers[] = $returnedQuery;

                        $triggers[] = $this->returnQueryString()->createForeignKeyConstraint($tableName, $columnName, $column['restrict']->value[0], $column['restrict']->value[1]);
                    }
                }
                else {
                    throw new Exception('$column[\'restrict\'] must be an instance of DatabaseType(DatabaseTypeType::tableColumn).');
                }
            }


            /* Put it All Together As an SQL Statement Piece */
            $columns[] = $this->formatValue(DatabaseTypeType::column, $columnName)
                . ' ' . $typePiece;
        }

        return [$columns, $triggers, $tableIndexes, $tableProperties];
    }


    private function parseSelectColumns($tableCols) {
        if (is_array($tableCols))
            return $tableCols;

        elseif (is_string($tableCols)) { // Table columns have been defined with a string list, e.g. "a,b,c"
            $columnArray = [];

            $colParts = explode(',', $tableCols); // Split the list into an array, delimited by commas

            foreach ($colParts AS $colPart) { // Run through each list item
                $colPart = trim($colPart); // Remove outside whitespace from the item

                if (strpos($colPart, ' ') !== false) { // If a space is within the part, then the part is formatted as "columnName columnAlias"
                    $colPartParts = explode(' ', $colPart); // Divide the piece

                    $colPartName = $colPartParts[0]; // Set the name equal to the first part of the piece
                    $colPartAlias = $colPartParts[1]; // Set the alias equal to the second part of the piece
                }
                else { // Otherwise, the column name and alias are one in the same.
                    $colPartName = $colPart; // Set the name and alias equal to the piece
                    $colPartAlias = $colPart;
                }

                $columnArray[$colPartName] = $colPartAlias;
            }

            return $columnArray;
        }

        else
            throw new Exception('Unrecognised table column format.');
    }


    /**
     * Creates a new table with given properties.
     *
     * @param $tableName       string The table to alter.
     * @param $tableComment    string A new comment for the table.
     * @param $engine          string A new engine for the table.
     * @param $tableColumns    array The table's columns. See {@link DatabaseSQL::parseTableColumns}} for formatting.
     * @param $tableIndexes    array A new engine for the table. See {@link DatabaseSQL::createTableIndexes}} for formatting.
     * @param $partitionColumn string|bool A new partition column for the table.
     * @param hardPartitionCount int The number of manual table instances that should be created, using the DBAL's hard partitioning scheme.
     * @param $renameOrDeleteExisting bool If true, any existing table with this table name will be renamed or (if rename fails) deleted.
     *
     * @return bool True on success, false on failure.
     * @throws Exception
     */
    public function createTable($tableName,
                                $tableComment,
                                $engine,
                                $tableColumns,
                                $tableIndexes = [],
                                $partitionColumn = false,
                                $hardPartitionCount = 1,
                                $renameOrDeleteExisting = false)
    {
        /* Perform CREATEs */
        $this->startTransaction();

        $return = true;
        for ($i = 0; $i < $hardPartitionCount; $i++) {
            $tableNameI = $tableName . ($hardPartitionCount > 1 ? "__part$i" : '');


            /* Rename/Delete Existing
             * If we are overwriting an existing table, try renaming, then deleting it. */
            if ($renameOrDeleteExisting && in_array(strtolower($tableNameI), $this->getTablesAsArray())) {
                if (!$this->renameTable($tableNameI, $tableNameI . '~' . time())) {
                    if (!$this->deleteTable($tableNameI)) {
                        throw new Exception("Could Not Rename or Delete Table '$tableNameI'");
                    }
                }
            }

            /* If we don't want to overwrite an existing table, and we can't rely on IF NOT EXISTS in the CREATE TABLE statement.
             * then check to see if an existing table exists and skip if it does. */
            else if (!$this->sqlInterface->useCreateIfNotExist && in_array(strtolower($tableName), $this->getTablesAsArray())) {
                continue;
            }


            /* Parse Columns and Indexes */
            list($columns, $triggers, $tableIndexes, $tableProperties) = $this->parseTableColumns($tableNameI, $tableColumns, $tableIndexes, $engine);
            list($indexes, $indexTriggers) = $this->createTableIndexes($tableNameI, $tableIndexes, true);

            $triggers = array_merge($triggers, $indexTriggers);


            /* Table Comments */
            // In this mode, we add comments with separate SQL statements at the end.
            switch ($this->sqlInterface->commentMode) {
                case 'useCommentOn':
                    $triggers[] = 'COMMENT ON TABLE '
                        . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, '{TABLENAME}')
                        . ' IS '
                        . $this->formatValue(DatabaseTypeType::string, $tableComment);
                    break;

                case 'useAttributes':
                    $tableProperties .= " COMMENT=" . $this->formatValue(DatabaseTypeType::string, $tableComment);
                    break;
            }


            /* Table Engine
             * Currently, only MySQL supports different engines. */
            if ($this->sqlInterface->getLanguage() === 'mysql') {
                $tableProperties .= ' ENGINE=' . $this->formatValue(DatabaseTypeType::string, ($engine === DatabaseEngine::memory ? "MEMORY" : "InnoDB"));
            }

            /* TODO: a lot more is needed to make this work with SqlServer, but this would be the beginning.
            elseif ($this->sqlInterface->getLanguage() === 'sqlsrv' && $engine === DatabaseEngine::memory) {
                $tableProperties .= 'WITH(MEMORY_OPTIMIZED=ON, DURABILITY=SCHEMA_ONLY)';
            }
            */


            /* Table Charset
             * PgSQL specifies charset when DB is created, so that's up to the enduser. */
            if ($this->sqlInterface->getLanguage() === 'mysql') {
                $tableProperties .= ' DEFAULT CHARSET=' . $this->formatValue(DatabaseTypeType::string, 'utf8');
            }


            /* Table partitioning */
            if ($partitionColumn
                && $this->sqlInterface->usePartition) {
                $tableProperties .= ' PARTITION BY HASH(' . $this->formatValue(DatabaseTypeType::column, $partitionColumn) . ') PARTITIONS 100';
            }

            $return = $this->rawQuery(
                'CREATE '
                . ($this->sqlInterface->getLanguage() === 'pgsql' && $engine === DatabaseEngine::memory
                    ? 'UNLOGGED '
                    : '')
                . 'TABLE '
                . ($this->sqlInterface->useCreateIfNotExist
                    ? 'IF NOT EXISTS '
                    : '')
                . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableNameI)
                . ' ('
                . implode(", ", $columns)
                . (count($indexes) > 0
                    ? ',' . implode(", ", $indexes)
                    : '')
                . ')'
                . $tableProperties
            );

            $return = $return &&
                $this->executeTriggers($tableNameI, $triggers);
        }

        $this->endTransaction();

        return $return;
    }


    /**
     * Changes the name of a table, and deletes all foreign key constraints attached to it.
     *
     * @param $oldName string The current table name, to be changed.
     * @param $newName string The new table name.
     *
     * @return bool True on success, false on failure.
     */
    public function renameTable($oldName, $newName)
    {
        switch ($this->sqlInterface->tableRenameMode) {
            case 'renameTable':
                return $this->deleteForeignKeyConstraints($oldName) &&
                    $this->rawQuery('RENAME TABLE '
                        . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $oldName)
                        . ' TO '
                        . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $newName)
                    );
                break;

            case 'alterTable':
                return $this->deleteForeignKeyConstraints($oldName) &&
                    $this->rawQuery('ALTER TABLE '
                        . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $oldName)
                        . ' RENAME TO '
                        . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $newName)
                    );
                break;
        }

        return false;
    }


    /**
     * Changes the table comment, engine, or partition column of a table.
     *
     * @param $tableName string The table to alter.
     * @param $tableComment string A new comment for the table.
     * @param $engine string A new engine for the table.
     * @param $partitionColumn string|false A new partition column for the table.
     *
     * @return bool True on success, false on failure.
     */
    public function alterTable($tableName, $tableComment, $engine, $partitionColumn = false) {
        return $this->rawQuery('ALTER TABLE ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName)
            . (!is_null($engine) && $this->sqlInterface->getLanguage() === 'mysql' ? ' ENGINE=' . $this->formatValue(DatabaseTypeType::string, $this->sqlInterface->tableTypes[$engine]) : '')
            . (!is_null($tableComment) ? ' COMMENT=' . $this->formatValue(DatabaseTypeType::string, $tableComment) : '')
            . ($partitionColumn ? ' PARTITION BY HASH(' . $this->formatValue(DatabaseTypeType::column, $partitionColumn) . ') PARTITIONS 100' : ''));
    }


    /**
     * Deletes a table.
     *
     * @param $tableName string The table to delete.
     *
     * @return bool True on success, false on failure.
     */
    public function deleteTable($tableName)
    {
        return $this->deleteForeignKeyConstraints($tableName)
            && $this->rawQuery('DROP TABLE IF EXISTS '
                . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName)
            );
    }


    /**
     * Adds columns to a table.
     *
     * @param $tableName string The table to create columns on.
     * @param $tableColumns array The columns to create. See {@link DatabaseSQL::parseTableColumns} for the format to use.
     * @param $engine string The engine of the table the columns will be created in.
     *
     * @return bool True on success, false on failure.
     */
    public function createTableColumns($tableName, $tableColumns, $engine = DatabaseEngine::general) {
        list ($columns, $triggers, $tableIndexes) = $this->parseTableColumns($tableName, $tableColumns, null, $engine);

        array_walk($columns, function(&$column) { $column = 'ADD ' . $column; });

        return $this->rawQuery('ALTER TABLE '
                . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName)
                . ' '
                . implode($columns, ', ')
            )
            && $this->executeTriggers($tableName, $triggers);
    }


    /**
     * Modifies columns in a table to have new properties.
     *
     * @param $tableName string The table containing the columns.
     * @param $tableColumns array The columns to update. See {@link DatabaseSQL::parseTableColumns} for the format to use.
     * @param $engine string The engine of the table.
     *
     * @return bool True on success, false on failure.
     */
    public function alterTableColumns($tableName, $tableColumns, $engine = DatabaseEngine::general) {
        list ($columns, $triggers, $tableIndexes) = $this->parseTableColumns($tableName, $tableColumns, null, $engine);

        array_walk($columns, function(&$column) { $column = 'MODIFY ' . $column; });

        return $this->rawQuery('ALTER TABLE '
                . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName)
                . ' '
                . implode($columns, ', ')
            )
            && $this->executeTriggers($tableName, $triggers);
    }


    /**
     * Creates new indexes in a table. This has the following known limitations:
     * * On PgSQL, fulltext indexes must not reference more than one column.
     * * On SQLServer, the primary key must come before any fulltext indexes, and a primary key MUST be specified.
     *
     * @param string $tableName
     * @param array  $tableIndexes {
     *     An array of table column properties indexed by a comma-seperated list of columns the table index indexes. Valid parameters:
     *
     *     'type'          string           The type of the index, some value from DatabaseIndexType.
     * }
     * @param bool   $duringTableCreation When true, this will return index statements to be used with table created.
     *
     * @return bool|array When $duringTableCreation is false, this returns true on success, false on failure. When $duringTableCreation is true, this returns a list of index components to use with the table creation and a list of triggers to run after the table is created.
     * @throws Exception when an index has an invalid index mode.
     */
    public function createTableIndexes($tableName, $tableIndexes, $duringTableCreation = false) {

        $triggers = [];
        $indexes = [];


        // Identify primary key, for use with SqlServer
        $primaryKey = false;
        foreach ($tableIndexes AS $indexName2 => $index2) {
            if ($index2['type'] === DatabaseIndexType::primary)
                $primaryKey = $this->getIndexName($tableName, $indexName2);
        }


        // Create each index
        foreach ($tableIndexes AS $indexName => $index) {
            /* Default to normal index if type is invalid. */
            if (!isset($this->sqlInterface->keyTypeConstants[$index['type']])) {
                $this->triggerError("Unrecognised Index Type", array(
                    'tableName' => $tableName,
                    'indexName' => $indexName,
                    'indexType' => $index['type'],
                ), 'validationFallback');
                $index['type'] = 'index';
            }

            /* Generate CREATE INDEX Statements, If Needed */
            if ((!$duringTableCreation || $this->sqlInterface->indexMode === 'useCreateIndex')
                && $index['type'] !== DatabaseIndexType::primary) {

                // Delete any old index with the given name
                $this->deleteIndex($tableName, $indexName);

                // Add the CREATE INDEX statement to the triggers.
                $triggers[] = $this->returnQueryString()->createIndex($tableName, $indexName, $index['type'], $primaryKey);
            }

            // If we are in useTableAttribute index mode and this is during table creation, or the index is primary, prepare to return the index statement.
            elseif (($duringTableCreation && $this->sqlInterface->indexMode === 'useTableAttribute') || $index['type'] === 'primary') {
                $indexes[] = "CONSTRAINT "
                    . $this->formatValue(DatabaseSQL::FORMAT_VALUE_INDEX, $this->getIndexName($tableName, $indexName))
                    . " PRIMARY KEY "
                        . $this->formatValue(DatabaseTypeType::arraylist, $this->getIndexColsFromIndexName($indexName));
            }

            // Throw an exception if the index mode is unrecognised.
            else
                throw new Exception("Invalid index mode: {$this->sqlInterface->indexMode}");
        }


        if ($duringTableCreation)
            return [$indexes, $triggers];
        else
            return $this->executeTriggers($tableName, $triggers);

    }


    /**
     * Deletes an existing table index. Will do nothing if the index does not exist.
     *
     * @param $tableName string The table with a foreign key constraint.
     * @param $constraintName string The foreign key constraint's name.
     *
     * @return bool True on success, false on failure.
     */
    public function createIndex($tableName, $indexName, $indexType, $primaryKey = false) {

        // Transfrom the index name into one that is unique to the database.
        $alteredIndexName = $this->getIndexName($tableName, $indexName);

        // Get the columns referenced by an index name.
        $indexCols = $this->getIndexColsFromIndexName($indexName);


        /* CREATE x INDEX ON table */
        $trigger = "CREATE " . $this->sqlInterface->keyTypeConstants[$indexType] . " INDEX";

        if (!($indexType === DatabaseIndexType::fulltext
            && $this->sqlInterface->getLanguage() === 'sqlsrv')) {
            $trigger .= ' '
                . $this->formatValue(DatabaseSQL::FORMAT_VALUE_INDEX, $alteredIndexName);
        }

        $trigger .= " ON "
                . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName);

        /* PgSQL: GIN indexes for fulltext
         * TODO: this doesn't support multi-column fulltext indexes. */
        if ($indexType === DatabaseIndexType::fulltext
            && $this->sqlInterface->getLanguage() === 'pgsql') {
            $trigger .= ' USING GIN (to_tsvector(\'english\', ' . $this->formatValue(DatabaseTypeType::column, $indexName) . '))';
        }

        /* (columns) */
        else {
            $trigger .= ' '
                . $this->formatValue(DatabaseTypeType::arraylist, $indexCols);
        }


        /* SqlSrv: WHERE NOT NULL (allow multiple nulls in SqlSrv unique indexes) */
        if ($indexType === DatabaseIndexType::unique
            && $this->sqlInterface->getLanguage() === 'sqlsrv') {
            $indexColsConditions = [];
            foreach ($indexCols AS $col) {
                $indexColsConditions["!{$col->value}"] = $this->type(DatabaseTypeType::null);
            }

            $trigger .= ' WHERE ' . $this->recurseBothEither($indexColsConditions, $this->reverseAliasFromConditionArray($tableName, $indexColsConditions), 'both');
        }


        /* SqlSrv: KEY INDEX for full text indexes */
        if ($indexType === DatabaseIndexType::fulltext
            && $this->sqlInterface->getLanguage() === 'sqlsrv') {
            $trigger .= ' KEY INDEX ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_INDEX, $primaryKey);
        }

        return $this->rawQuery($trigger);

    }

    /**
     * Deletes an existing table index. Will do nothing if the index does not exist.
     *
     * @param $tableName string The table with a foreign key constraint.
     * @param $constraintName string The foreign key constraint's name.
     *
     * @return bool True on success, false on failure.
     */
    public function deleteIndex($tableName, $indexName) : bool {

        $alteredIndexName = $this->getIndexName($tableName, $indexName);

        if ($this->sqlInterface->useDropIndexIfExists) {
            return $this->rawQuery('DROP INDEX IF EXISTS '
                . $this->formatValue(DatabaseSQL::FORMAT_VALUE_INDEX, $alteredIndexName)
                . ($this->sqlInterface->perTableIndexes ?
                    ' ON '
                    . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName)
                    : ''
                )
            );
        }
        else {
            $tableIndexes = $this->getTableIndexesAsArray();

            if (isset($tableIndexes[strtolower($tableName)]) && in_array($alteredIndexName, $tableIndexes[strtolower($tableName)])) {
                return $this->rawQuery('DROP INDEX '
                    . $this->formatValue(DatabaseSQL::FORMAT_VALUE_INDEX, $alteredIndexName)
                    . ($this->sqlInterface->perTableIndexes ?
                        ' ON '
                        . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName)
                        : ''
                ));
            }

            return false;
        }

    }

    private function getIndexName($tableName, $indexName) : string {
        return "i_{$tableName}_{$indexName}";
    }


    /**
     * Pull out columns from index name.
     * If an index name is comma-seperated, it is using multiple columns.
     */
    private function getIndexColsFromIndexName($indexName) : array {

        if (strpos($indexName, ',') !== false) {
            $indexCols = explode(',', $indexName);

            foreach ($indexCols AS &$indexCol) {
                $indexCol = $this->col($indexCol);
            }

            return $indexCols;
        }

        else {
            return [$this->col($indexName)];
        }

    }



    /**
     * Creates a foreign key constraint on a table.
     *
     * @param $tableName string The table to have a foreign key constraint on.
     * @param $tableName string The column to have a foreign key constraint on.
     * @param $foreignTableName string The foreign table to be referenced.
     * @param $foreignColumnName string The foreign column to be referenced.
     *
     * @return bool True on success, false on failure.
     */
    public function createForeignKeyConstraint($tableName, $columnName, $foreignTableName, $foreignColumnName) {
        $constraintName = 'fk_' . $tableName . '_' . $columnName;

        if ($this->sqlInterface->foreignKeyMode) {
            return $this->rawQuery('ALTER TABLE '
                . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName)
                . ' ADD CONSTRAINT ' . $this->formatValue(DatabaseTypeType::column, $constraintName) . ' FOREIGN KEY ('
                . $this->formatValue(DatabaseTypeType::column, $columnName)
                . ') REFERENCES ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $foreignTableName) . '('
                . $this->formatValue(DatabaseTypeType::column, $foreignColumnName)
                . ')'
            );
        }
    }


    /**
     * Deletes all existing foreign key constraints on a single table.
     *
     * @param $tableName string The table whose foreign key constraints will be dropped.
     *
     * @return bool True on success, false on failure.
     */
    public function deleteForeignKeyConstraints($tableName) {
        $return = true;
        $tableConstraints = $this->getTableConstraintsAsArray();

        if (isset($tableConstraints[strtolower($tableName)])) {
            foreach ($tableConstraints[strtolower($tableName)] AS $constraintName) {
                $return = $this->deleteForeignKeyConstraint($tableName, $constraintName)
                    && $return;
            }
        }

        return $return;
    }


    /**
     * Deletes an existing foreign key constraint. Will do nothing if the constraint does not exist.
     *
     * @param $tableName string The table with a foreign key constraint.
     * @param $constraintName string The foreign key constraint's name.
     *
     * @return bool True on success, false on failure.
     */
    public function deleteForeignKeyConstraint($tableName, $constraintName) {
        $tableConstraints = $this->getTableConstraintsAsArray();

        if (!$this->sqlInterface->foreignKeyMode) {
            return true;
        }

        if (isset($tableConstraints[strtolower($tableName)]) && in_array($constraintName, $tableConstraints[strtolower($tableName)])) {
            switch ($this->sqlInterface->foreignKeyMode) {
                case 'useAlterTableForeignKey':
                    return $this->rawQuery('ALTER TABLE '
                        . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName)
                        . ' DROP FOREIGN KEY '
                        . $this->formatValue(DatabaseTypeType::column, $constraintName));

                case 'useAlterTableConstraint':
                    return $this->rawQuery('ALTER TABLE '
                        . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName)
                        . ' DROP CONSTRAINT '
                        . $this->formatValue(DatabaseTypeType::column, $constraintName)
                        . ' CASCADE ');
                    break;
            }
        }

        return false;
    }


    /**
     * Deletes an existing foreign key constraint named based on table and column name. Will do nothing if the constraint does not exist.
     *
     * @param $tableName string The table with a foreign key constraint.
     * @param $columnName string The column with a foreign key constraint.
     *
     * @return bool True on success, false on failure.
     */
    public function deleteForeignKeyConstraintFromColumnName($tableName, $columnName) {
        return $this->deleteForeignKeyConstraint($tableName, 'fk_' . $tableName . '_' . $columnName);
    }


    /**
     * Adds a comment to a table column.
     *
     * @param $tableName string The table containing the column.
     * @param $columnName string The column to add a comment to.
     *
     * @return bool True on success, false on failure.
     */
    public function createTableColumnComment($tableName, $columnName, $comment) {
        return $this->rawQuery('COMMENT ON COLUMN '
            . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_COLUMN, $tableName, $columnName)
            . ' IS '
            . $this->formatValue(DatabaseTypeType::string, $comment)
        );
    }


    /**
     * Adds a comment to a table.
     *
     * @param $tableName string The table to add a comment to.
     *
     * @return bool True on success, false on failure.
     */
    public function createTableComment($tableName, $comment) {
        return $this->rawQuery('COMMENT ON TABLE '
            . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName)
            . ' IS '
            . $this->formatValue(DatabaseTypeType::string, $comment)
        );
    }


    /**
     * Run a series of SQL statements in sequence, returning true if all run successfully.
     * {TABLENAME} will be converted to $tableName, if present in any trigger.
     * If {@link holdTriggers} is enabled, the trigger statements will instead by placed in {@link triggerQueue}
     *
     * @param $tableName string The SQL tablename.
     * @param $triggers array List of SQL statements.
     *
     * @return bool true if all queries successful, false if any fails
     */
    public function executeTriggers($tableName, $triggers) {
        $return = true;

        foreach ((array) $triggers AS $trigger) {
            $triggerText = str_replace('{TABLENAME}', $tableName, $trigger);

            if ($this->holdTriggers) {
                $this->triggerQueue[] = $triggerText;
            }
            else {
                $return = $return
                    && $this->rawQuery($triggerText); // Make $return false if any query return false.
            }
        }

        return $return;
    }

    public function getTriggerQueue() {
        return $this->triggerQueue;
    }


    public function getTablesAsArray(): array {
        return array_map('strtolower', $this->sqlInterface->getTablesAsArray($this));
    }


    public function getTableColumnsAsArray(): array {
        return array_change_key_case(
            $this->sqlInterface->getTableColumnsAsArray($this), CASE_LOWER
        );
    }


    public function getTableConstraintsAsArray(): array {
        return array_change_key_case(
            $this->sqlInterface->getTableConstraintsAsArray($this), CASE_LOWER
        );
    }


    public function getTableIndexesAsArray(): array {
        return array_change_key_case(
            $this->sqlInterface->getTableIndexesAsArray($this), CASE_LOWER
        );
    }

    /*********************************************************
     ************************* END ***************************
     ******************* Table Functions *********************
     *********************************************************/


    /*********************************************************
     ************************ START **************************
     ******************** Row Functions **********************
     *********************************************************/

    public function select($columns, $conditionArray = false, $sort = false, $limit = false, $page = 0)
    {
        /* Define Variables */
        $finalQuery = array(
            'columns' => array(),
            'tables' => array(),
            'join' => array(),
            'where' => '',
            'sort' => array(),
            'group' => '',
            'limit' => 0
        );
        $reverseAlias = array();
        $joins = array();



        /* Where()/sort()/limit() overrides */
        if ($this->conditionArray) {
            if ($conditionArray) throw new Exception('Condition array declared both in where() and select().');

            $conditionArray = $this->conditionArray; $this->conditionArray = array();

        }
        if ($this->sortArray) {
            if ($sort !== false) throw new Exception("Sort array declared both in sort() and select().");

            $sort = $this->sortArray; $this->sortArray = array();
        }

        if ($this->limit) {
            if ($limit !== false) throw new Exception("Limit declared both in limit() and select().");

            $limit = $this->limit; $this->limit = false;
        }

        if ($this->page) {
            if ($page !== 0) throw new Exception("Page declared both in page() and select().");

            $page = $this->page; $this->page = 0;
        }


        /* Process Columns */
        // If columns is a string, then it is a table name, whose columns should be guessed from the other parameters. For now, this guessing is very limited -- just taking the array_keys of $conditionArray (TODO).
        if (is_string($columns)) {
            $columns = array(
                "$columns" => array_keys($conditionArray)
            );
        }

        elseif (!is_array($columns)) {
            $this->triggerError('Invalid Select Array (Columns Not String or Array)', array(), 'validation');
        }

        elseif (!count($columns)) {
            $this->triggerError('Invalid Select Array (Columns Array Empty)', array(), 'validation');
        }


        // Process $columns
        foreach ($columns AS $tableName => $tableCols) {
            // Make sure tableName is a valid string.
            if (!is_string($tableName) || !strlen($tableName)) {
                $this->triggerError('Invalid Select Array (Invalid Table Name)', array(
                    'tableName' => $tableName,
                ), 'validation');
            }


            if (strpos($tableName, 'join ') === 0) { // If the table is identified as being part of a join.
                $tableName = substr($tableName, 5);

                /*foreach ($this->parseSelectColumns($tableCols['columns']) AS $columnAlias => $columnName) {
                    $finalQuery['columns'][] = $this->formatValue(databaseSQL::FORMAT_VALUE_TABLE_COLUMN_NAME_ALIAS, $tableName, $columnAlias, $columnName);
                }*/

                $joins[$tableName] = $tableCols['conditions'];
                $tableCols = $tableCols['columns'];
            }

            elseif (strstr($tableName, ' ') !== false) { // A space can be used to create a table alias, which is sometimes required for different queries.
                $tableParts = explode(' ', $tableName);

                $finalQuery['tables'][] = $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_NAME_ALIAS, $tableParts[0], $tableParts[1]); // Identify the table as [tableName] AS [tableAlias]; note: may be removed if the table is part of a join.

                $tableName = $tableParts[1];
            }

            else {
                if (isset($this->hardPartitions[$tableName])) { // This should be used with the above stuff too, but that would really require a partial rewrite at this point, and I'm too close to release to want to do that.
                    list($column, $partitionCount) = $this->hardPartitions[$tableName];

                    if (isset($conditionArray[$column]))
                        $found = $conditionArray[$column];
                    elseif (isset($conditionArray['both'][$column]))
                        $found = $conditionArray['both'][$column];
                    else
                        $this->triggerError("Selecting from a hard partitioned table, " . $tableName . ", without the partition column, " . $column . " at the top level is unsupported. It likely won't ever be supported, since any boolean logic is likely to require cross-partition selection, which is far too complicated a feature for this DAL. Use native RDBMS partitioning for that if you can.");

                    // I'm not a fan of this hack at all, but I'd really have to rewrite to
                    $finalQuery['tables'][] = $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_NAME_ALIAS, $tableName . "__part" . $found % $partitionCount, $tableName);
                }
                else {
                    $finalQuery['tables'][] = $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName); // Identify the table as [tableName]; note: may be removed if the table is part of a join.
                }

            }


            $tableCols = $this->parseSelectColumns($tableCols);

            foreach ($tableCols AS $colName => $colAlias) {
                if (is_int($colName)) $colName = $colAlias;

                if (strlen($colName) > 0) {
                    if (strstr($colName, ' ') !== false) { // A space can be used to create identical columns in different contexts, which is sometimes required for different queries.
                        $colParts = explode(' ', $colName);
                        $colName = $colParts[0];
                    }

                    if (is_array($colAlias)) { // Used for advance structures and function calls.
                        if (isset($colAlias['context'])) {
                            throw new Exception('Deprecated context.'); // TODO
                        }

                        $finalQuery['columns'][] = $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_COLUMN_NAME_ALIAS, $tableName, $colName, $colAlias['alias']);

                        $reverseAlias[$colAlias['alias']] = [$tableName, $colName];
                    }

                    else {
                        $finalQuery['columns'][] = $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_COLUMN_NAME_ALIAS, $tableName, $colName, $colAlias);
                        $reverseAlias[$colAlias] = [$tableName, $colName];
                    }
                }

                else {
                    $this->triggerError('Invalid Select Array (Empty Column Name)', array(
                        'tableName' => $tableName,
                        'columnName' => $colName,
                    ), 'validation');
                }
            }
        }


        /* Process Conditions (Must be Array) */
        if (is_array($conditionArray) && count($conditionArray)) {
            $finalQuery['where'] = $this->recurseBothEither($conditionArray, $reverseAlias, 'both');
        }


        /* Process Joins */
        if (count($joins) > 0) {
            foreach ($joins AS $table => $join) {
                $finalQuery['join'][] = $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $table)
                    . ' ON '
                    . $this->recurseBothEither($join, $reverseAlias);
            }
        }



        /* Process Sorting (Must be Array) */
        if ($sort !== false) {
            if (is_string($sort)) {
                $sortParts = explode(',', $sort); // Split the list into an array, delimited by commas
                $sort = [];

                foreach ($sortParts AS $sortPart) { // Run through each list item
                    $sortPart = trim($sortPart); // Remove outside whitespace from the item

                    if (strpos($sortPart,' ') !== false) { // If a space is within the part, then the part is formatted as "columnName direction"
                        $sortPartParts = explode(' ', $sortPart); // Divide the piece

                        $sortColumn = $sortPartParts[0]; // Set the name equal to the first part of the piece
                        $directionSym = $sortPartParts[1];
                    }
                    else { // Otherwise, we assume asscending
                        $sortColumn = $sortPart; // Set the name equal to the sort part.
                        $directionSym = 'asc'; // Set the alias equal to the default, ascending.
                    }

                    $sort[$sortColumn] = $directionSym;
                }
            }

            if (count($sort) > 0) {
                foreach ($sort AS $sortColumn => $direction) {
                    $sortColumn = explode(' ', $sortColumn)[0];

                    if ($direction instanceof DatabaseType) {
                        if ($direction->type == DatabaseTypeType::arraylist) {
                            if (count($direction->value) == 0) continue;

                            if ($this->sqlInterface->getLanguage() == 'mysql') {
                                $list = $direction->value;
                                rsort($list);
                                $list = array_merge([$this->col($sortColumn)], $list);

                                $finalQuery['sort'][] = 'FIELD' . $this->formatValue(DatabaseTypeType::arraylist, $list) . ' ' . $this->sqlInterface->sortOrderDesc;
                            }// todo: postgresql using case
                        }
                        else {
                            $finalQuery['sort'][] = $this->recurseBothEither([$sortColumn => $direction]) . ' ' . $this->sqlInterface->sortOrderDesc ;
                        }
                    }

                    elseif (isset($reverseAlias[$sortColumn])) {
                        switch (strtolower($direction)) {
                            case 'asc': $directionSym = $this->sqlInterface->sortOrderAsc; break;
                            case 'desc': $directionSym = $this->sqlInterface->sortOrderDesc; break;
                            default: $directionSym = $this->sqlInterface->sortOrderAsc; break;
                        }

                        $finalQuery['sort'][] = $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_COLUMN, $reverseAlias[$sortColumn][0], $reverseAlias[$sortColumn][1]) . " $directionSym";
                    }

                    else {
                        $this->triggerError('Unrecognised Sort Column', array(
                            'sortColumn' => $sortColumn,
                        ), 'validation');
                    }
                }
            }
        }

        $finalQuery['sort'] = implode(', ', $finalQuery['sort']);



        /* Process Limit (Must be Integer) */
        if ($limit !== false) {
            if (is_int($limit)) {
                $finalQuery['limit'] = (int) $limit;
            }
        }
        $finalQuery['page'] = (int) $page;
        if ($finalQuery['page'] < 0) $finalQuery['page'] = 0;


        /* Generate Final Query */
        $finalQueryText = 'SELECT '
            . implode(', ', $finalQuery['columns'])
            . ' FROM '
            . implode(', ', $finalQuery['tables'])
            . ($finalQuery['join']
                ? ' LEFT JOIN '
                    . implode("\n", $finalQuery['join'])
                : ''
            ) . ($finalQuery['where']
                ? ' WHERE '
                    . $finalQuery['where']
                : ''
            ) . ($finalQuery['sort']
                ? ' ORDER BY ' . $finalQuery['sort']
                : ''
            );

        if ($finalQuery['limit'])
            if ($this->sqlInterface->getLanguage() === 'sqlsrv') {
                $finalQueryText .= ' OFFSET ' .
                    ($finalQuery['limit'] * $finalQuery['page'])
                    . ' ROWS FETCH NEXT '
                    . ($finalQuery['limit'] > 1 ? $finalQuery['limit'] + 1 : $finalQuery['limit'])
                    . ' ROWS ONLY';
            }
            else {
                $finalQueryText .= ' LIMIT '
                    . ($finalQuery['limit'] > 1 ? $finalQuery['limit'] + 1 : $finalQuery['limit'])
                    . ' OFFSET ' . ($finalQuery['limit'] * $finalQuery['page']);
            }

        /* And Run the Query */
        return $this->rawQueryReturningResult($finalQueryText, $reverseAlias, $finalQuery['limit']);
    }


    /**
     * Used to perform subqueries.
     *
     * @param $columns
     * @param bool $conditionArray
     * @param bool $sort
     * @param bool $limit
     * @return bool|object|resource|string
     * @throws Exception
     */
    public function subSelect($columns, $conditionArray = false, $sort = false, $limit = false)
    {
        return $this->returnQueryString()->select($columns, $conditionArray, $sort, $limit, true);
    }
/*
    public function subJoin($columns, $conditionArray = false)
    {
        return $this->formatValue();
    }
*/


    /**
     * Recurses over a specified "where" array, returning a valid where clause.
     *
     * @param array|mixed $conditionArray - The conditions to transform into proper SQL.
     * @param array $reverseAlias - An array corrosponding to column aliases and their database counterparts.
     *
     * @return string
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    private function recurseBothEither($conditionArray, $reverseAlias = false, $type = 'both', $tableName = false)
    {
        $i = 0;

        if (!is_array($conditionArray))
            throw new Exception('Condition array must be an array.');
        elseif (!count($conditionArray))
            return $this->sqlInterface->boolValues[true] . ' = ' . $this->sqlInterface->boolValues[true];

        // $key is usually a column, $value is a formatted value for the select() function.
        foreach ($conditionArray AS $key => $value) {
            /* @var $value DatabaseType */

            $i++;

            if (strstr($key, ' ') !== false) list($key) = explode(' ', $key); // A space can be used to reference the same key twice in different contexts. It's basically a hack, but it's better than using further arrays.

            /* Key is Combiner */
            if ($key === 'both' || $key === 'either' || $key === 'neither') { // TODO: neither?
                $sideTextFull[$i] = $this->recurseBothEither($value, $reverseAlias, $key, $tableName);
            }

            /* Key is List Index, Hopefully */
            elseif (is_int($key)) {
                $sideTextFull[$i] = $this->recurseBothEither($value, $reverseAlias, 'both', $tableName);
            }

            /* Key is Column */
            else {
                // Defaults
                $sideTextFull[$i] = '';
                if (!$this->isTypeObject($value)) $value = $this->str($value);  // If value is not a DatabaseType, treat it as a string.

                // lvalue
                $column = ($this->startsWith($key, '!') ? substr($key, 1) : $key);

                // Detect Bad Column
                if (!isset($reverseAlias[$column]))
                    throw new Exception("Column '$column' cannot be queried; was it in the select fields?");


                /* Full Text Searching */
                if ($value->comparison === DatabaseTypeComparison::fulltextSearch
                    && $this->sqlInterface->getLanguage() === 'mysql') {
                    $sideTextFull[$i] = 'MATCH (' . $this->formatValue(DatabaseTypeType::column, $column) . ') AGAINST (' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_DETECT, $value) . ' IN NATURAL LANGUAGE MODE)';
                }

                elseif ($value->comparison === DatabaseTypeComparison::fulltextSearch
                    && $this->sqlInterface->getLanguage() === 'pgsql') {
                    $sideTextFull[$i] = 'to_tsvector (\'english\', ' . $this->formatValue(DatabaseTypeType::column, $column) . ') @@ to_tsquery(\'english\', ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_DETECT, $value) . ')';
                }

                elseif ($value->comparison === DatabaseTypeComparison::fulltextSearch
                    && $this->sqlInterface->getLanguage() === 'sqlsrv') {
                    $sideTextFull[$i] = 'CONTAINS(' . $this->formatValue(DatabaseTypeType::column, $column) . ', ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_DETECT, $value) . ')';
                }

                /* Normal Boolean Logic */
                else {
                    $sideText['left'] = ($reverseAlias ? $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_COLUMN, $reverseAlias[$column][0], $reverseAlias[$column][1]) : $column); // Get the column definition that corresponds with the named column. "!column" signifies negation.


                    // comparison operator
                    $symbol = $this->sqlInterface->comparisonTypes[$value->comparison];


                    // rvalue
                    if ($value->type === DatabaseTypeType::column)
                        $sideText['right'] = ($reverseAlias ? $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_COLUMN, $reverseAlias[$value->value][0], $reverseAlias[$value->value][1]) : $value->value); // The value is a column, and should be returned as a reverseAlias. (Note that reverseAlias should have already called formatValue)

                    elseif ($value->type === DatabaseTypeType::arraylist && count($value->value) === 0) {
                        $this->triggerError('Array nullified', false, 'validationFallback');
                        $sideTextFull[$i] = "{$this->sqlInterface->boolValues[false]} = {$this->sqlInterface->boolValues[true]}"; // Instead of throwing an exception, which should be handled above, instead simply cancel the query in the cleanest way possible. Simply specifying "false" works on most DBMS, but not SqlServer.
                        continue;
                    }

                    else {
                        // Apply transform function, if set

                        if (isset($reverseAlias[$column])) { // If we have reverse alias data for the column...
                            $transformColumnName = $reverseAlias[$column][1] ?? $column;
                            $transformTableName = $reverseAlias[$column][2] ?? $reverseAlias[$column][0]; // If the second index is set, it is storing the "original" table name, in case of a partition. Otherwise, the 0th index, containing the regular table name, is used.

                            if (isset($this->encode[$transformTableName][$transformColumnName])) { // Do we have conversion data available?
                                list($function, $typeOverride) = $this->encode[$transformTableName][$transformColumnName]; // Fetch the function used for transformation, and the type override if available.

                                $value = $this->applyTransformFunction($function, $value, $typeOverride); // Apply the transformation to the value for usage in our query.
                            }
                        }

                        // Build rvalue
                        $sideText['right'] = $this->formatValue(
                            ($value->comparison === DatabaseTypeComparison::search ? DatabaseSQL::FORMAT_VALUE_SEARCH : $value->type),  // The value is a data type, and should be processed as such.
                            $value->value
                        );
                    }


                    // Combine l and rvalues
                    // TODO: $this->null(NOT EQUALS)
                    if ($value->type === DatabaseTypeType::null) {
                        $sideTextFull[$i] = "{$sideText['left']} IS " . ($this->startsWith($key, '!') ? "NOT " : "") . "NULL";
                    }

                    elseif ((strlen($sideText['left']) > 0) && (strlen($sideText['right']) > 0)) {
                        $sideTextFull[$i] =
                            ($this->startsWith($key, '!')
                                ? $this->sqlInterface->concatTypes['not']
                                : ''
                            )
                            . "({$sideText['left']} {$symbol} {$sideText['right']}"
                            . ($value->comparison === DatabaseTypeComparison::binaryAnd
                                ? ' = ' . $this->formatValue(DatabaseTypeType::integer, $value->value)
                                : ''
                            ) // Special case: postgres binaryAnd
                            . ")";
                    }

                    else {
                        $sideTextFull[$i] = "FALSE"; // Instead of throwing an exception, which should be handled above, instead simply cancel the query in the cleanest way possible. Here, it's specifying "FALSE" in the where clause to prevent any results from being returned.

                        $this->triggerError('Query Nullified', array('Key' => $key, 'Value' => $value, 'Side Text' => $sideText, 'Reverse Alias' => $reverseAlias), 'validationFallback');
                    }
                }
            }
        }


        if (!isset($this->sqlInterface->concatTypes[$type])) {
            $this->triggerError('Unrecognised Concatenation Operator', array(
                'operator' => $type,
            ), 'validation');
        }


        return '(' . implode($this->sqlInterface->concatTypes[$type], $sideTextFull) . ')'; // Return condition string. We wrap parens around to support multiple levels of conditions/recursion.
    }


    /**
     * Get a "reverse alias" array (for use with {@link databaseSQL::recurseBothEither()}) given a tablename and condition array.
     *
     * @param string      $tableName         The table name.
     * @param array       $conditionArray    A standard condition array; see {@link database::select()} and {@link databaseSQL::recurseBothEither()}) for more.
     * @param bool|string $originalTableName $tableName is aliased, and this is the original.
     *
     * @return array
     */
    private function reverseAliasFromConditionArray($tableName, $conditionArray, $originalTableName = false, $combineReverseAlias = []) {
        $reverseAlias = $combineReverseAlias;
        foreach ($conditionArray AS $column => $value) {
            if (substr($column, 0, 1) === '!')
                $column = substr($column, 1);

            if ($column === 'either' || $column === 'both') {
                foreach ($value AS $subValue) {
                    $reverseAlias = array_merge($reverseAlias, $this->reverseAliasFromConditionArray($tableName, $subValue));
                }
            }
            $reverseAlias[$column] = [$tableName, $column];

            // We also keep track of the original table name if it's been renamed through hard partitioning, and will use it to determine triggers.
            if ($originalTableName)
                $reverseAlias[$column][] = $originalTableName;
        }
        return $reverseAlias;
    }


    /**
     * Gets a transformed table name if hard partitioning is enabled.
     *
     * @param $tableName string The source tablename.
     * @param $dataArray array The data array that contains the partition column. Currently, advanced data arrays are not supported; the partition column must be identified by string as a top-level index on the array.
     *
     * @return string
     */
    private function getTableNameTransformation($tableName, $dataArray)
    {
        if (isset($this->hardPartitions[$tableName])) {
            return $tableName . "__part" . $dataArray[$this->hardPartitions[$tableName][0]] % $this->auto($this->hardPartitions[$tableName][1])->value;
        }

        return $tableName;
    }


    public function insert($tableName, $dataArray)
    {
        /* Query Queueing */
        if ($this->autoQueue) {
            $this->queueInsert($this->getTableNameTransformation($tableName, $dataArray), $dataArray);
        }

        else {
            /* Collection Trigger */
            if (isset($this->collectionTriggers[$tableName])) {
                foreach ($this->collectionTriggers[$tableName] AS $params) {
                    list($triggerColumn, $aggregateColumn, $function) = $params;
                    if (isset($dataArray[$triggerColumn], $dataArray[$aggregateColumn])) {
                        call_user_func($function, $dataArray[$triggerColumn], ['insert' => [$dataArray[$aggregateColumn]]]);
                    }
                }
            }

            return $this->insertCore($tableName, $dataArray);
        }
    }


    public function getLastInsertId()
    {
        return $this->sqlInterface->getLastInsertId();
    }


    public function insertIdCallback($table)
    {
        /* Transform code for insert ID
         * If we are supposed to copy over an insert ID into a new, transformed column, we do it here. */
        if (isset($this->insertIdColumns[$table]) && isset($this->encodeCopy[$table][$this->insertIdColumns[$table]])) {
            $insertId = $this->getLastInsertId();

            list($function, $typeOverride, $column) = $this->encodeCopy[$table][$this->insertIdColumns[$table]];

            $this->update($table, [
                $column => $this->applyTransformFunction($function, $insertId, $typeOverride)
            ], [
                $this->insertIdColumns[$table] => $insertId
            ]);
        }
    }


    private function insertCore($tableName, $dataArray, $originalTableName = false)
    {
        /* Actual Insert */
        $columns = array_keys($dataArray);
        $values = array_values($dataArray);


        $serialDisabled = false;
        if ($this->sqlInterface->getLanguage() === 'sqlsrv'
            && isset($this->insertIdColumns[$tableName])
            && in_array($this->insertIdColumns[$tableName], $columns)) {
            $serialDisabled = true;
            $this->rawQuery('SET IDENTITY_INSERT ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName) . ' ON');
        }


        $query = 'INSERT INTO '
            . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $this->getTableNameTransformation($tableName, $dataArray))
            . ' '
            . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_COLUMN_VALUES, $tableName, $columns, $values);

        if ($queryData = $this->rawQuery($query)) {
            $this->insertIdCallback($tableName);

            if ($serialDisabled)
                $this->rawQuery('SET IDENTITY_INSERT ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName) . ' OFF');

            return $queryData;
        }
        else {
            return false;
        }
    }


    public function delete($tableName, $conditionArray = false)
    {
        $originalTableName = $tableName;

        if (isset($this->hardPartitions[$tableName])) {
            $partitionAt = array_merge($this->partitionAt, $conditionArray);

            if (!isset($partitionAt[$this->hardPartitions[$tableName][0]])) {
                $this->triggerError("The table $tableName is partitioned. To delete from it, you _must_ specify the column " . $this->hardPartitions[$tableName][0] . ". Note that you may instead use partitionAt() if you know _any_ column that would apply to the partition (for instance, if you wish to delete the last row from a table before inserting a new one, you can specify the relevant condition using partitionAt().)" . print_r($partitionAt, true));
            }

            $tableName .= "__part" . $partitionAt[$this->hardPartitions[$tableName][0]] % $this->hardPartitions[$tableName][1];
        }

        $this->partitionAt = [];

        if ($this->autoQueue)
            return $this->queueDelete($tableName, $conditionArray);

        else {
            // This table has a collection trigger.
            if (isset($this->collectionTriggers[$tableName])) {

                foreach ($this->collectionTriggers[$tableName] AS $entry => $params) {
                    list($triggerColumn, $aggregateColumn, $function) = $params;

                    // Trigger column present -- that is, we are deleting data belonging to a specific list.
                    if (isset($conditionArray[$triggerColumn])) {
                        // Aggregate column present -- we will be narrowly deleting a pair of [triggerColumn, aggregateColumn]
                        if (isset($conditionArray[$aggregateColumn])) {
                            call_user_func($function, $conditionArray[$triggerColumn], ['delete' => [$conditionArray[$aggregateColumn]]]);
                        }

                        // Aggregate column NOT present -- we will be deleting the entire collection belonging to triggerColumn. Mark it for de
                        else {
                            call_user_func($function, $conditionArray[$triggerColumn], ['delete' => '*']);
                        }
                    }

                    // Trigger column not present, but the table has a collection trigger. As this is a deletion, this is too unpredictable, and we throw an error.
                    else {
                        $this->triggerError("Cannot perform deletion on " . $tableName . ", as it has a collection trigger, and you have not specified a condition for the trigger column, " . $triggerColumn);
                    }
                }
            }

            $this->deleteCore($tableName, $conditionArray, $originalTableName);
        }
    }


    private function deleteCore($tableName, $conditionArray = false, $originalTableName = false)
    {
        return $this->rawQuery(
            'DELETE FROM ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName) .
            ($conditionArray ? ' WHERE ' . $this->recurseBothEither($conditionArray, $this->reverseAliasFromConditionArray($tableName, $conditionArray, $originalTableName), 'both', $tableName) : '')
        );
    }


    public function update($tableName, $dataArray, $conditionArray = [])
    {
        $originalTableName = $tableName;

        if (isset($this->hardPartitions[$tableName])) {
            if (!isset($conditionArray[$this->hardPartitions[$tableName][0]])) {
                $this->triggerError("The table $tableName is partitioned. To update it, you _must_ specify the column " . $this->hardPartitions[$tableName][0] . ' ' . print_r($conditionArray, true));
            }
            elseif (isset($dataArray[$this->hardPartitions[$tableName][0]])) {
                $this->triggerError("The table $tableName is partitioned by column " . $this->hardPartitions[$tableName][0] . ". As such, you may not apply updates to this column. (...Okay, yes, it would in theory be possible to add such support, but it'd be a pain to make it portable, and is outside of the scope of my usage. Feel free to contribute such functionality.)");
            }

            $tableName .= "__part" . $conditionArray[$this->hardPartitions[$tableName][0]] % $this->hardPartitions[$tableName][1];
        }


        if ($this->sqlInterface->getLanguage() === 'pgsql') {
            // Workaround for equations to use unambiguous excluded dataset.
            foreach ($dataArray AS &$dataElement) {
                if ($this->isTypeObject($dataElement) && $dataElement->type === DatabaseTypeType::equation) {
                    $dataElement->value = str_replace('$', "\${$tableName}.", $dataElement->value);
                }
            }
        }


        if ($this->autoQueue)
            return $this->queueUpdate($tableName, $dataArray, $conditionArray);
        else
            return $this->rawQuery(
                'UPDATE ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName) .
                ' SET ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_UPDATE_ARRAY, $tableName, $dataArray) .
                ' WHERE ' . $this->recurseBothEither($conditionArray, $this->reverseAliasFromConditionArray($tableName, $conditionArray, $originalTableName), 'both', $tableName)
            );
    }


    /**
     * If a row matching $conditionArray already exists, it will be updated to reflect $dataArray. If it does not exist, a row will be inserted that is a composite of $conditionArray, $dataArray, and $dataArrayOnInsert.
     * On systems that support OnDuplicateKey, this will NOT test the existence of $conditionArray, relying instead on the table's keys to do so. Thus, this function's $conditionArray should always match the table's own keys.
     *
     * @param $tableName
     * @param $conditionArray
     * @param $dataArray
     * @param $dataArrayOnInsert
     * @return bool|resource
     * @throws Exception
     */
    public function upsert($tableName, $conditionArray, $dataArray, $dataArrayOnInsert = [])
    {
        if (isset($this->hardPartitions[$tableName])) {
            if (!isset($conditionArray[$this->hardPartitions[$tableName][0]])) {
                $this->triggerError("The table $tableName is partitioned. To update it, you _must_ specify the column " . $this->hardPartitions[$tableName][0]);
            }
            elseif (isset($dataArray[$this->hardPartitions[$tableName][0]])) {
                $this->triggerError("The table $tableName is partitioned by column " . $this->hardPartitions[$tableName][0] . ". As such, you may not apply updates to this column. (...Okay, yes, it would in theory be possible to add such support, but it'd be a pain to make it portable, and is outside of the scope of my usage. Feel free to contribute such functionality.)");
            }

            $tableName .= "__part" . $conditionArray[$this->hardPartitions[$tableName][0]] % $this->hardPartitions[$tableName][1];
        }

        $allArray = array_merge($dataArray, $dataArrayOnInsert, $conditionArray);
        $allColumns = array_keys($allArray);
        $allValues = array_values($allArray);

        switch ($this->sqlInterface->upsertMode) {
            case 'onDuplicateKey':
                $query = 'INSERT INTO ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName)
                    . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_COLUMN_VALUES, $tableName, $allColumns, $allValues)
                    . ' ON DUPLICATE KEY UPDATE ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_UPDATE_ARRAY, $tableName, $dataArray);
                break;

            case 'onConflictDoUpdate':
                 // Workaround for equations to use unambiguous excluded dataset.
                foreach ($dataArray AS &$dataElement) {
                    if ($this->isTypeObject($dataElement) && $dataElement->type === DatabaseTypeType::equation) {
                        $dataElement = $this->equation(str_replace('$', 'excluded.$', $dataElement->value)); // We create a new one because we don't want to update the one pointed to in allArray.
                    }
                }

                $query = 'INSERT INTO ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE, $tableName)
                    . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_COLUMN_VALUES, $tableName, $allColumns, $allValues)
                    . ' ON CONFLICT '
                    . $this->formatValue(DatabaseSQL::FORMAT_VALUE_COLUMN_ARRAY, array_keys($conditionArray))
                    . ' DO UPDATE SET ' . $this->formatValue(DatabaseSQL::FORMAT_VALUE_TABLE_UPDATE_ARRAY, $tableName, $dataArray);
                break;

            case 'selectThenInsertOrUpdate':
                if ($this->select([$tableName => array_keys($conditionArray)], $conditionArray)->getCount() > 0) {
                    return $this->update($tableName, $dataArray, $conditionArray);
                }
                else {
                    return @$this->insert($tableName, $allArray);
                }
                break;

            case 'tryCatch':
                try {
                    return $this->insert($tableName, $allArray);
                } catch (Exception $ex) {
                    return $this->update($tableName, $dataArray, $conditionArray);
                }
                break;

            default:
                throw new \Exception('Unrecognised upsert mode: ' . $this->sqlInterface->upsertMode);
        }

        if ($queryData = $this->rawQuery($query)) {
            $this->insertIdCallback($tableName);

            return $queryData;
        }
        else return false;
    }

    /*********************************************************
     ************************* END ***************************
     ******************** Row Functions **********************
     *********************************************************/




    /*********************************************************
     ************************ START **************************
     ******************** Queue Functions ********************
     * TODO: queue functions do not currently support simultaneous partitioning and transformations.
     *********************************************************/

    public function autoQueue(bool $on) {
        $previous = $this->autoQueue;
        $this->autoQueue = $on;

        // If we just turned autoQueue off (it wasn't off before), process all the queued calls.
        if ($previous && !$on)
            $this->processQueue();
    }

    public function queueUpdate($tableName, $dataArray, $conditionArray = false) {
        $this->updateQueue[$tableName][json_encode($conditionArray)][] = $dataArray;
    }

    public function queueDelete($tableName, $dataArray) {
        $this->deleteQueue[$tableName][] = $dataArray;
    }

    public function queueInsert($tableName, $dataArray) {
        $this->insertQueue[$tableName][] = $dataArray;
    }


    public function processQueue() {
        $this->startTransaction();

        $triggerCallbacks = [];

        foreach ($this->deleteQueue AS $tableName => $deleteConditions) {
            // This table has a collection trigger.
            if (isset($this->collectionTriggers[$tableName])) {

                foreach ($this->collectionTriggers[$tableName] AS $entry => $params) {
                    list($triggerColumn, $aggregateColumn, $function) = $params;

                    foreach ($deleteConditions AS $deleteCondition) {
                        // Trigger column present -- that is, we are deleting data belonging to a specific list.
                        if (isset($deleteCondition[$triggerColumn])) {
                            // Don't try to add entries if the whole list has been marked for deletion.
                            if (@$triggerCallbacks[$tableName][$entry][$deleteCondition[$triggerColumn]]['delete'] === '*')
                                continue;

                            // Aggregate column present -- we will be narrowly deleting a pair of [triggerColumn, aggregateColumn]
                            elseif (isset($deleteCondition[$aggregateColumn])) {
                                @$triggerCallbacks[$tableName][$entry][$deleteCondition[$triggerColumn]]['delete'][] = $deleteCondition[$aggregateColumn];
                            }

                            // Aggregate column NOT present -- we will be deleting the entire collection belonging to triggerColumn. Mark it for de
                            else {
                                @$triggerCallbacks[$tableName][$entry][$deleteCondition[$triggerColumn]]['delete'] = '*';
                            }
                        }

                        // Trigger column not present, but the table has a collection trigger. As this is a deletion, this is too unpredictable, and we throw an error.
                        else {
                            $this->triggerError("Cannot perform deletion on " . $tableName . ", as it has a collection trigger, and you have not specified a condition for the trigger column, " . $triggerColumn);
                        }
                    }
                }
            }

            $deleteConditionsCombined = ['either' => $deleteConditions];
            $this->deleteCore($tableName, $deleteConditionsCombined);
        }

        foreach ($this->updateQueue AS $tableName => $update) {
            foreach ($update AS $conditionArray => $dataArrays) {
                $conditionArray = json_decode($conditionArray, true);
                $mergedDataArray = [];

                foreach ($dataArrays AS $dataArray) {
                    // The order here is important: by specifying dataArray second, later entries in the queue overwrite earlier entries in it. This is important to maintain.
                    $mergedDataArray = array_merge($mergedDataArray, $dataArray);
                }

                $this->update($tableName, $mergedDataArray, $conditionArray);
            }
        }

        foreach ($this->insertQueue AS $tableName => $dataArrays) {
            // The table has a collection trigger
            if (isset($this->collectionTriggers[$tableName])) {
                foreach ($this->collectionTriggers[$tableName] AS $entry => $params) {
                    list($triggerColumn, $aggregateColumn, $function) = $params;

                    foreach ($dataArrays AS $dataArray) {
                        // We are inserting a specific value (aggregateColumn) into the collection (triggerColumn)
                        if (isset($dataArray[$triggerColumn], $dataArray[$aggregateColumn])) {
                            @$triggerCallbacks[$tableName][$entry][$dataArray[$triggerColumn]]['insert'][] = $dataArray[$aggregateColumn];
                        }
                    }
                }
            }

            foreach ($dataArrays AS $dataArray) {
                $this->insertCore($tableName, $dataArray);
            }
        }

        foreach ($triggerCallbacks AS $table => $collectionTriggers) {
            foreach ($collectionTriggers AS $entry => $entryPair) {
                foreach ($entryPair AS $entryValue => $dataOperations) {
                    list($triggerColumn, $aggregateColumn, $function) = $this->collectionTriggers[$table][$entry];

                    call_user_func($function, $entryValue, $dataOperations);
                }
            }
        }

        $this->endTransaction();
    }

    /*********************************************************
     ************************* END ***************************
     ******************** Queue Functions ********************
     *********************************************************/

}
?>