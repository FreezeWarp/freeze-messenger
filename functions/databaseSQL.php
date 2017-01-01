<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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

class databaseSQL extends database
{
    public $classVersion = 3;
    public $classProduct = 'fim';

    public $getVersion = false; // Whether or not to get the database version, adding overhead.

    public $version = 0;
    public $versionPrimary = 0;
    public $versionSeconday = 0;
    public $versionTertiary = 0;
    public $versionString = '0.0.0';
    public $supportedLanguages = array('mysql', 'mysqli', 'pdo');
    public $storeTypes = array('memory', 'general', 'innodb');
    public $queryLog = array();
    public $mode = 'SQL';
    public $language = '';

    private $driverMap = array(
        'mysql' => 'mysql',
        'mysqli' => 'mysql',
        'pdo-mysql' => 'mysql',
        'pgsql' => 'pgsql',
        'pdo-pqsql' => 'pqsql',
    );

    public $disableEnum = false;

    public $returnQueryString = false;

    protected $connection = false;
    protected $connectionResult = false;

    protected $dbLink = false;

    protected $encodeRoomId = array(
        'files' => ['roomId'],
        'messages' => ['roomId'],
        'messageIndex' => ['roomId'],
        'ping' => ['roomId'],
        'roomEvents' => ['roomId'],
        'roomPermissionsCache' => ['roomId'],
        'roomStats' => ['roomId'],
        'searchMessages' => ['roomId'],
        'searchCache' => ['roomId'],
        'unreadMessages' => ['roomId'],
        'users' => ['defaultRoomId'],
        'userFavRooms' => ['roomId'],
    );

    /*********************************************************
     ************************ START **************************
     ******************* General Functions *******************
     *********************************************************/

    /**
     * Calls a database function, such as mysql_connect or mysql_query, using lookup tables
     *
     * @return void
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    private function functionMap($operation)
    {
        $args = func_get_args();

        /* TODO: consistent responses (e.g. FALSE on failure) */
        switch ($this->driver) {
            case 'mysql':
                switch ($operation) {
                    case 'connect':
                        $this->connection = mysql_connect("$args[1]:$args[2]", $args[3], $args[4]);
                        if ($this->getVersion) $this->version = $this->setDatabaseVersion(mysql_get_server_info($this->connection));

                        return $this->connection;
                        break;

                    case 'error':
                        return mysql_error(isset($this->connection) ? $this->connection : null);
                        break;
                    case 'close':
                        if ($this->connection) {
                            $function = mysql_close($this->connection);
                            unset($this->connection);
                            return $function;
                        } else {
                            return true;
                        }
                        break;
                    case 'selectdb':
                        return mysql_select_db($args[1], $this->connection);
                        break;
                    case 'escape':
                        return mysql_real_escape_string($args[1], $this->connection);
                        break;
                    case 'query':
                        return mysql_query($args[1], $this->connection);
                        break;
                    case 'insertId':
                        return mysql_insert_id($this->connection);
                        break;
                    case 'startTrans':
                        $this->rawQuery('START TRANSACTION');
                        break;
                    case 'endTrans':
                        $this->rawQuery('COMMIT');
                        break;
                    case 'rollbackTrans':
                        $this->rawQuery('ROLLBACK');
                        break;
                    default:
                        $this->triggerError("[Function Map] Unrecognised Operation", array('operation' => $operation), 'validation');
                        break;
                }
                break;


            case 'mysqli':
                switch ($operation) {
                    case 'connect':
                        $this->connection = new mysqli($args[1], $args[3], $args[4], ($args[5] ? $args[5] : null), (int)$args[2]);

                        if ($this->connection->connect_error) {
                            $this->newError('Connect Error (' . $this->connection->connect_errno . ') '
                                . $this->connection->connect_error);
                        } else {
                            if ($this->getVersion) $this->version = $this->setDatabaseVersion($this->connection->server_info);

                            return $this->connection;
                        }
                        break;

                    case 'error':
                        if ($this->connection->connect_errno) return $this->connection->connect_errno;
                        else                                  return $this->connection->error;
                        break;

                    case 'selectdb':
                        return $this->connection->select_db($args[1]);
                        break;
                    case 'close':    /*return $this->connection->close();*/
                        break;
                    case 'escape':
                        return $this->connection->real_escape_string($args[1]);
                        break;
                    case 'query':
                        return $this->connection->query($args[1]);
                        break;
                    case 'insertId':
                        return $this->connection->insert_id;
                        break;
                    case 'startTrans':
                        $this->connection->autocommit(false);
                        break; // Use start_transaction in PHP 5.5
                    case 'endTrans':
                        $this->connection->commit();
                        $this->connection->autocommit(true);
                        break;
                    case 'rollbackTrans':
                        $this->connection->rollback();
                        $this->connection->autocommit(true);
                        break;
                    default:
                        $this->triggerError("[Function Map] Unrecognised Operation", array('operation' => $operation), 'validation');
                        break;
                }
                break;


            case 'pdo':
                switch ($operation) {
                    case 'connect':// var_dump($args); die();
                        try {
                            $this->connection = new PDO("mysql:dbname=$args[5];host=$args[1]:$args[2]", $args[3], $args[4]);
                        } catch (PDOException $e) {
                            $this->connection->errorCode = $e->getFile();
                            die($this->connection->errorCode);
                            return false;
                        }

                        $this->version = $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
                        $this->activeDatabase = $args[5];

                        return $this->connection;
                        break;

                    case 'error':
                        return $this->connection->errorCode;
                        break;

                    case 'selectdb':
                        return $this->rawQuery("USE " . $this->formatValue("database", $args[1]));
                        break; // TODO test
                    case 'close':
                        unset($this->connection);
                        return true;
                        break;
                    case 'escape':
                        switch ($args[2]) {
                            case 'string':
                            case 'search':
                                return $this->connection->quote($args[1], PDO::PARAM_STR);
                                break;
                            case 'integer':
                            case 'timestamp':
                                return $this->connection->quote($args[1], PDO::PARAM_STR);
                                break;
                            case 'column':
                            case 'columnA':
                            case 'table':
                            case 'tableA':
                            case 'database':
                                return $args[1];
                                break;
                            default:
                                $this->triggerError('Invalid context.', array('arguments' => $args), 'validation');
                                break;
                        }
                        break; // Note: long-term, we should implement this using prepared statements.
                    case 'query':
                        return $this->connection->query($args[1]);
                        break;
                    case 'insertId':
                        return $this->connection->lastInsertId();
                        break;
                    case 'startTrans':
                        $this->connection->beginTransaction();
                        break; // Use start_transaction in PHP 5.5
                    case 'endTrans':
                        $this->connection->commit();
                        break;
                    case 'rollbackTrans':
                        $this->connection->rollBack();
                        break;
                    default:
                        $this->triggerError("[Function Map] Unrecognised Operation", array('operation' => $operation), 'validation');
                        break;
                }
                break;


            case 'pgsql':
                switch ($operation) {
                    case 'connect':
                        return pg_connect("host=$args[1] port=$args[2] username=$args[3] password=$args[4] dbname=$args[5]");
                        break;
                    case 'error':
                        return pg_last_error($this->dbLink);
                        break;
                    case 'close':
                        return pg_close($this->dbLink);
                        break;
                    case 'escape':
                        return pg_escape_string($this->dbLink, $args[1]);
                        break;
                    case 'query':
                        return pg_query($this->dbLink, $args[1]);
                        break;
                    case 'insertId':
                        return $this->rawQuery('SELECT LASTVAL()')->getAsArray(false, false, 0);
                        break; // Note: Returning is by far the best solution, and should be considered in future versions. This would require defining the insertId column, which might be doable.
                    case 'notify':
                        return pg_get_notify($this->dbLink);
                        break;
                    default:
                        $this->triggerError("[Function Map] Unrecognised Operation", array('operation' => $operation), 'validation');
                        break;
                }
                break;
        }
    }


    /** Format a value to represent the specified type in an SQL query.
     *
     * @param int|string value - The value to format.
     * @param string type - The type to format as, either "search", "string", "integer", or "column".
     * @return int|string - Value, formatted as specified.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    private function formatValue($type)
    {
        $values = func_get_args();

        switch ($type) {
            case 'detect':
                if (!$this->isTypeObject($values[1])) $values[1] = $this->str($values[1]);

                return $this->formatValue($values[1]->type, $values[1]->value);
                break;

            case 'search':
                return $this->stringQuoteStart . $this->stringFuzzy . $this->escape($values[1], 'search') . $this->stringFuzzy . $this->stringQuoteEnd;
                break;
            case 'string': case DatabaseTypeType::string:
                return $this->stringQuoteStart . $this->escape($values[1], 'string') . $this->stringQuoteEnd;
                break;
            case 'bool': case DatabaseTypeType::bool:
                return $this->boolValues[$values[1]];
            case 'integer': case DatabaseTypeType::integer:
                return $this->intQuoteStart . (int)$this->escape($values[1], 'integer') . $this->intQuoteEnd;
                break;
            case 'timestamp': case DatabaseTypeType::timestamp:
                return $this->timestampQuoteStart . (int)$this->escape($values[1], 'timestamp') . $this->timestampQuoteEnd;
                break;
            case 'column': case DatabaseTypeType::column:
                return $this->columnQuoteStart . $this->escape($values[1], 'column') . $this->columnQuoteEnd;
                break;
            case 'columnA':
                return $this->columnAliasQuoteStart . $this->escape($values[1], 'columnA') . $this->columnAliasQuoteEnd;
                break;
            case 'table':
                return $this->tableQuoteStart . $this->escape($values[1], 'table') . $this->tableQuoteEnd;
                break;
            case 'tableA':
                return $this->tableAliasQuoteStart . $this->escape($values[1], 'tableA') . $this->tableAliasQuoteEnd;
                break;
            case 'database':
                return $this->databaseQuoteStart . $this->escape($values[1], 'database') . $this->databaseQuoteEnd;
                break;
            case 'index':
                return $this->indexQuoteStart . $this->escape($values[1], 'index') . $this->indexQuoteEnd;
                break;

            case 'equation': case DatabaseTypeType::equation:  // Only partially implemented, because equations are stupid. Don't use them if possible.
                return preg_replace_callback('/\$([a-zA-Z]+)/', function ($matches) {
                    return $matches[1];
                }, $values[1]);
                break;

            case 'array': case DatabaseTypeType::arraylist:
                foreach ($values[1] AS &$item) {
                    if (!$this->isTypeObject($item))
                        $item = $this->str($item);

                    $item = $this->formatValue($item->type, $item->value);
                }

                return $this->arrayQuoteStart . implode($this->arraySeperator, $values[1]) . $this->arrayQuoteEnd; // Combine as list.
                break;

            case 'columnArray':
                foreach ($values[1] AS &$item) $item = $this->formatValue('column', $item);

                return $this->arrayQuoteStart . implode($this->arraySeperator, $values[1]) . $this->arrayQuoteEnd; // Combine as list.
                break;

            case 'updateArray':
                $update = array();

                foreach ($values[1] AS $column => $value) {
                    $update[] = $this->formatValue('column', $column) . $this->comparisonTypes[DatabaseTypeComparison::assignment] . $this->formatValue('detect', $value);
                }

                return implode($update, $this->statementSeperator);
                break;

            case 'tableColumn':
                return $this->formatValue('table', $values[1]) . $this->tableColumnDivider . $this->formatValue('column', $values[2]);
                break;
            case 'databaseTable':
                return $this->formatValue('database', $values[1]) . $this->databaseTableDivider . $this->formatValue('table', $values[2]);
                break;

            case 'tableColumnAlias':
                return $this->formatValue('table', $values[1]) . $this->tableColumnDivider . $this->formatValue('column', $values[2]) . $this->columnAliasDivider . $this->formatValue('columnA', $values[3]);
                break;

            case 'tableAlias' :
                return $this->formatValue('table', $values[1]) . $this->tableAliasDivider . $this->formatValue('tableA', $values[2]);
                break;

            /* new for encoding */
        case 'tableColumnValues':
            $tableName = $values[1];

            // Columns
            foreach ($values[2] AS $key => &$column) {
                if (isset($this->encodeRoomId[$tableName]) && in_array($column, $this->encodeRoomId[$tableName])) {
                    if ($this->isTypeObject($values[3][$key])) {
                        throw new Exception('unimplemented.');
                    }
                    else {
                        $values[3][$key] = fimRoom::encodeId($values[3][$key]);
                    }
                }

                $column = $this->formatValue('column', $column);
            }

            // Values
            foreach ($values[3] AS &$item) {
                if (!$this->isTypeObject($item))
                    $item = $this->str($item);

                $item = $this->formatValue($item->type, $item->value);
            }

            // Combine as list.
            return $this->arrayQuoteStart . implode($this->arraySeperator, $values[2]) . $this->arrayQuoteEnd . ' VALUES ' . $this->arrayQuoteStart . implode($this->arraySeperator, $values[3]) . $this->arrayQuoteEnd;
        break;

        case 'tableUpdateArray':
            $tableName = $values[1];
            $update = array();

            foreach ($values[2] AS $column => $value) {
                if (isset($this->encodeRoomId[$tableName]) && in_array($column, $this->encodeRoomId[$tableName])) {
                    if ($this->isTypeObject($value)) {
                        throw new Exception('unimplemented.');
                    }
                    else {
                        $value = fimRoom::encodeId($value);
                    }
                }

                $update[] = $this->formatValue('column', $column) . $this->comparisonTypes[DatabaseTypeComparison::assignment] . $this->formatValue('detect', $value);
            }

            return implode($update, $this->statementSeperator);
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
        $this->versionSeconday = $strippedVersionParts[1];
        $this->versionTertiary = $strippedVersionParts[2];


        // Compatibility check. We're really not sure how true any of this, and we have no reason to support older versions, but meh.
        switch ($this->driver) {
            case 'mysql':
            case 'mysqli':
                if ($strippedVersionParts[0] <= 4) { // MySQL 4 is a no-go.
                    die('You have attempted to connect to a MySQL version 4 database. MySQL 5.0.5+ is required for FreezeMessenger.');
                } elseif ($strippedVersionParts[0] == 5 && $strippedVersionParts[1] == 0 && $strippedVersionParts[2] <= 4) { // MySQL 5.0.0-5.0.4 is also a no-go (we require the BIT type, even though in theory we could work without it)
                    die('You have attempted to connect to an incompatible version of a MySQL version 5 database (MySQL 5.0.0-5.0.4). MySQL 5.0.5+ is required for FreezeMessenger.');
                }
                break;
        }
    }


    public function connect($host, $port, $user, $password, $database, $driver, $tablePrefix = '')
    {
        $this->setLanguage($driver);
        $this->sqlPrefix = $tablePrefix;

        switch ($driver) {
            case 'mysqli':
                if (PHP_VERSION_ID < 50209) { // if PHP_VERSION_ID isn't defined with versions < 5.2.7, but this obviously isn't a problem here (it will eval to 0, which is indeed less than 50209).
                    throw new Exception('MySQLi not supported on versions of PHP < 5.2.9');
                }
                break;
        }

        if (!$this->functionMap('connect', $host, $port, $user, $password, $database)) { // Make the connection.
            $this->triggerError('Could Not Connect', array( // Note: we do not include "password" in the error data.
                'host' => $host,
                'port' => $port,
                'user' => $user,
                'database' => $database
            ), 'connection');

            return false;
        }

        if (!$this->activeDatabase && $database) { // Some drivers will require this.
            if (!$this->selectDatabase($database)) { // Error will be issued in selectDatabase.
                return false;
            }
        }

        return true;
    }


    public function close()
    {
        return $this->functionMap('close');
    }


    /**
     * Set Language / Database Driver
     *
     * @param string language
     * @return void
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    private function setLanguage($language)
    {
        $this->driver = $language;
        $this->language = $this->driverMap[$this->driver];

        switch ($this->driver) {
            case 'mysql':
            case 'mysqli':
                $this->tableQuoteStart = '`';
                $this->tableQuoteEnd = '`';
                $this->tableAliasQuoteStart = '`';
                $this->tableAliasQuoteEnd = '`';
                $this->columnQuoteStart = '`';
                $this->columnQuoteEnd = '`';
                $this->columnAliasQuoteStart = '`';
                $this->columnAliasQuoteEnd = '`';
                $this->databaseQuoteStart = '`';
                $this->databaseQuoteEnd = '`';
                $this->databaseAliasQuoteStart = '`';
                $this->databaseAliasQuoteEnd = '`';
                $this->stringQuoteStart = '"';
                $this->stringQuoteEnd = '"';
                $this->emptyString = '""';
                $this->stringFuzzy = '%';
                $this->arrayQuoteStart = '(';
                $this->arrayQuoteEnd = ')';
                $this->arraySeperator = ', ';
                $this->statementSeperator = ', ';
                $this->intQuoteStart = '';
                $this->intQuoteEnd = '';
                $this->tableColumnDivider = '.';
                $this->databaseTableDivider = '.';
                $this->sortOrderAsc = 'ASC';
                $this->sortOrderDesc = 'DESC';
                $this->tableAliasDivider = ' AS ';
                $this->columnAliasDivider = ' AS ';
                break;

            case 'pdo':
                $this->tableQuoteStart = '';
                $this->tableQuoteEnd = '';
                $this->tableAliasQuoteStart = '';
                $this->tableAliasQuoteEnd = '';
                $this->columnQuoteStart = '';
                $this->columnQuoteEnd = '';
                $this->columnAliasQuoteStart = '';
                $this->columnAliasQuoteEnd = '';
                $this->databaseQuoteStart = '';
                $this->databaseQuoteEnd = '';
                $this->databaseAliasQuoteStart = '';
                $this->databaseAliasQuoteEnd = '';
                $this->stringQuoteStart = '';
                $this->stringQuoteEnd = '';
                $this->emptyString = '""';
                $this->stringFuzzy = '%';
                $this->arrayQuoteStart = '(';
                $this->arrayQuoteEnd = ')';
                $this->arraySeperator = ', ';
                $this->statementSeperator = ', ';
                $this->intQuoteStart = '';
                $this->intQuoteEnd = '';
                $this->tableColumnDivider = '.';
                $this->databaseTableDivider = '.';
                $this->sortOrderAsc = 'ASC';
                $this->sortOrderDesc = 'DESC';
                $this->tableAliasDivider = ' AS ';
                $this->columnAliasDivider = ' AS ';
                break;

            case 'pgsql':
                $this->tableQuoteStart = '"';
                $this->tableQuoteEnd = '"';
                $this->tableAliasQuoteStart = '"';
                $this->tableAliasQuoteEnd = '"';
                $this->columnQuoteStart = '"';
                $this->columnQuoteEnd = '"';
                $this->columnAliasQuoteStart = '"';
                $this->columnAliasQuoteEnd = '"';
                $this->databaseQuoteStart = '"';
                $this->databaseQuoteEnd = '"';
                $this->databaseAliasQuoteStart = '"';
                $this->databaseAliasQuoteEnd = '"';
                $this->stringQuoteStart = '"';
                $this->stringQuoteEnd = '"';
                $this->emptyString = '""';
                $this->stringFuzzy = '%';
                $this->arrayQuoteStart = '(';
                $this->arrayQuoteEnd = ')';
                $this->arraySeperator = ', ';
                $this->statementSeperator = ', ';
                $this->intQuoteStart = '';
                $this->intQuoteEnd = '';
                $this->tableColumnDivider = '.';
                $this->databaseTableDivider = '.';
                $this->sortOrderAsc = 'ASC';
                $this->sortOrderDesc = 'DESC';
                $this->tableAliasDivider = ' AS ';
                $this->columnAliasDivider = ' AS ';
                break;
        }

        switch ($this->language) {
            case 'mysql':
                $this->comparisonTypes = array(
                    DatabaseTypeComparison::equals => '=',
                    DatabaseTypeComparison::assignment => '=',
                    DatabaseTypeComparison::in => 'IN',
                    DatabaseTypeComparison::notin => 'NOT IN',
                    DatabaseTypeComparison::lessThan => '<',
                    DatabaseTypeComparison::lessThanEquals=> '<=',
                    DatabaseTypeComparison::greaterThan => '>',
                    DatabaseTypeComparison::greaterThanEquals => '>=',
                    DatabaseTypeComparison::search => 'LIKE',
                    DatabaseTypeComparison::binaryAnd => '&',
                );

                $this->concatTypes = array(
                    'both' => ' AND ', 'either' => ' OR ',
                );

                $this->keyTypeConstants = array(
                    'primary' => 'PRIMARY KEY',
                    'unique' => 'UNIQUE KEY',
                    'index' => 'KEY',
                );

                $this->defaultPhrases = array(
                    '__TIME__' => 'CURRENT_TIMESTAMP',
                );

                $this->dataTypes = array(
                    'columnIntLimits' => array(
                        2 => 'TINYINT', 4 => 'SMALLINT', 7 => 'MEDIUMINT', 9 => 'INT',
                        'default' => 'BIGINT'
                    ),

                    'columnStringPermLimits' => array(
                        255 => 'CHAR', 1000 => 'VARCHAR', 65535 => 'TEXT', 16777215 => 'MEDIUMTEXT', '4294967295' => 'LONGTEXT' // In MySQL, TEXT types are stored outside of the table. For searching purposes, we only use VARCHAR for relatively small values (I decided 1000 would be reasonable).
                    ),

                    'columnStringTempLimits' => array(
                        255 => 'CHAR', 65535 => 'VARCHAR'
                    ),

                    'columnStringNoLength' => array(
                        'MEDIUMTEXT', 'LONGTEXT'
                    ),

                    'columnBitLimits' => array(
                        8 => 'TINYINT UNSIGNED', 16 => 'SMALLINT UNSIGNED', 24 => 'MEDIUMINT UNSIGNED',
                        32 => 'INTEGER UNSIGNED', 64 => 'BIGINT UNSIGNED', 'default' => 'INTEGER UNSIGNED',
                    ),

                    'bool' => 'TINYINT(1) UNSIGNED',
                    'time' => 'INTEGER UNSIGNED',
                    'binary' => 'BLOB',
                );

                $this->boolValues = array(
                    true => 1, false => 0,
                );

                $this->useCreateType = false;
                break;

            case 'pgsql':
                $this->comparisonTypes = array(
                    DatabaseTypeComparison::equals => '=',
                    DatabaseTypeComparison::assignment => '=',
                    DatabaseTypeComparison::in => 'IN',
                    DatabaseTypeComparison::notin => 'NOT IN',
                    DatabaseTypeComparison::lessThan => '<',
                    DatabaseTypeComparison::lessThanEquals=> '<=',
                    DatabaseTypeComparison::greaterThan => '>',
                    DatabaseTypeComparison::greaterThanEquals => '>=',
                    DatabaseTypeComparison::search => 'LIKE',
                    DatabaseTypeComparison::binaryAnd => '&',
                );

                $this->concatTypes = array(
                    'both' => ' AND ', 'either' => ' OR ',
                );

                $this->keyTypeConstants = array(
                    'primary' => 'PRIMARY KEY',
                    'unique' => 'UNIQUE KEY',
                    'index' => 'KEY',
                );

                $this->defaultPhrases = array(
                    '__TIME__' => 'CURRENT_TIMESTAMP',
                );

                $this->dataTypes = array(
                    'columnIntLimits' => array(
                        1 => 'SMALLINT', 2 => 'SMALLINT', 3 => 'SMALLINT', 4 => 'SMALLINT', 5 => 'INTEGER',
                        6 => 'INTEGER', 7 => 'INTEGER', 8 => 'INTEGER', 9 => 'INTEGER', 0 => 'BIGINT',
                    ),
                    'columnSerialLimits' => array(
                        1 => 'SERIAL', 2 => 'SERIAL', 3 => 'SERIAL', 4 => 'SERIAL', 5 => 'SERIAL',
                        6 => 'SERIAL', 7 => 'SERIAL', 8 => 'SERIAL', 9 => 'SERIAL', 'default' => 'BIGSERIAL',
                    ),
                    'columnStringPermLimits' => array(
                        'default' => 'VARCHAR',
                    ),
                    'columnStringNoLength' => array(
                        'TEXT', // Unused
                    ),
                    'columnBitLimits' => array(
                        15 => 'SMALLINT', 31 => 'INTEGER', 63 => 'BIGINT',
                        127 => 'NUMERIC(40,0)', // Approximately -- maybe TODO
                        'default' => 'INTEGER',
                    ),
                    'bool' => 'SMALLINT', // Note: ENUM(1,2) AS BOOLENUM better.
                    'time' => 'INTEGER',
                    'binary' => 'BYTEA',
                );

                $this->boolValues = array(
                    true => 1, false => 0,
                );

                $this->useCreateType = true;
                break;
        }

        switch ($this->language) {
            case 'mysql':
                $this->tableTypes = array(
                    'general' => 'InnoDB',
                    'memory' => 'MEMORY',
                );
                break;
        }
    }


    /**
     * Returns a properly escaped string for raw queries.
     *
     * @param string int|string - Value to escape.
     * @param string context - The value type, in-case the escape method varies based on it.
     * @return string
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */

    protected function escape($string, $context = 'string')
    {
        if ($context === 'search') {
            $string = addcslashes($string, '%_\\'); // TODO: Verify
        }

        return $this->functionMap('escape', $string, $context); // Return the escaped string.
    }


    /**
     * Sends a raw, unmodified query string to the database server.
     * The query may be logged if it takes a certain amount of time to execute successfully.
     *
     * @param string $query - The raw query to execute.
     * @return resource|bool - The database resource returned by the query, or false on failure.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    protected function rawQuery($query)
    {
        $this->newQuery($query);

        if ($queryData = $this->functionMap('query', $query)) {
            if ($queryData === true) return true; // Insert, Update, Delete, etc.
            else return $this->databaseResultPipe($queryData, $query, $this->driver); // Select, etc.
        }

        else {
            $this->errors[] = $this->functionMap('error');

            $this->triggerError("badQuery", $query);

            return false;
        }
    }


    /**
     * Creates a new database result from passed parameters.
     *
     * @param $queryData
     * @param $query
     * @param $driver
     *
     * @return databaseResult
     */
    protected function databaseResultPipe($queryData, $query, $driver)
    {
        return new databaseResult($queryData, $query, $driver);
    }


    /**
     * Add the text of a query to the log. This should normally only be called by rawQuery(), but is left protected since other purposes could exist by design.
     *
     * @return string - The query text of the last query executed.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */

    protected function newQuery($queryText)
    {
        $this->queryCounter++;
        $this->queryLog[] = $queryText;
    }


    /**
     * Get the text of the last query executed.
     *
     * @return string - The query text of the last query executed.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function getLastQuery()
    {
        return end($this->queryLog);
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

        $this->functionMap('startTrans');
    }


    public function rollbackTransaction()
    {
        $this->transaction = false;

        $this->functionMap('rollbackTrans');
    }


    public function endTransaction()
    {
        $this->transaction = false;

        $this->functionMap('endTrans');
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

        if ($this->functionMap('selectdb', $database)) { // Select the database.
            if ($this->language == 'mysql' || $this->language == 'mysqli') {
                if (!$this->rawQuery('SET NAMES "utf8"', true)) { // Sets the database encoding to utf8 (unicode).
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
        return $this->rawQuery('CREATE DATABASE IF NOT EXISTS ' . $this->formatValue('database', $database));
    }

    /*********************************************************
     ************************* END ***************************
     ****************** Database Functions *******************
     *********************************************************/


    /*********************************************************
     ************************ START **************************
     ******************* Table Functions *********************
     *********************************************************/

    public function createTable($tableName, $tableComment, $engine, $tableColumns, $tableIndexes, $partitionColumn = false)
    {
        if (isset($this->tableTypes[$engine])) {
            $engineName = $this->tableTypes[$engine];
        } else {
            $this->triggerError("Unrecognised Table Engine", array(
                'tableName' => $tableName,
                'engine' => $engine
            ), 'validation');
        }

        $tableProperties = '';
        $triggers = [];

        foreach ($tableColumns AS $columnName => $column) {
            $typePiece = '';

            switch ($column['type']) {
                case 'int':
                    if (isset($this->columnSerialLimits) && isset($column['autoincrement']) && $column['autoincrement']) $intLimits = $this->dataTypes['columnSerialLimits'];
                    else $intLimits = $this->dataTypes['columnIntLimits'];

                    foreach ($intLimits AS $length => $type) {
                        if ($column['maxlen'] <= $length) {
                            $typePiece = $intLimits[$length];
                            break;
                        }
                    }

                    if (!strlen($typePiece)) $typePiece = $intLimits['default'];

                    if (!isset($this->columnSerialLimits) && isset($column['autoincrement']) && $column['autoincrement']) {
                        $typePiece .= ' AUTO_INCREMENT'; // Ya know, that thing where it sets itself.
                        $tableProperties .= ' AUTO_INCREMENT = ' . (int)$column['autoincrement'];
                    }
                    break;

                case 'string':
                    if ($column['restrict'] && !$this->disableEnum) {
                        $restrictValues = array();
                        foreach ((array)$column['restrict'] AS $value) $restrictValues[] = $this->formatValue("string", $value);

                        if ($this->useCreateType) {
                            $this->rawQuery('CREATE TYPE ' . $columnName . ' AS ENUM(
              ' . implode(',', $restrictValues) . '
            )');

                            $typePiece = $columnName;
                        } else {
                            $typePiece = 'ENUM(' . implode(',', $restrictValues) . ')';
                        }
                    } else {
                        if ($engine === 'memory') $stringLimits = $this->dataTypes['columnStringTempLimits'];
                        else                      $stringLimits = $this->dataTypes['columnStringPermLimits'];

                        $typePiece = '';

                        foreach ($stringLimits AS $length => $type) {
                            if ($column['maxlen'] <= $length) {
                                if (in_array($type, $this->dataTypes['columnStringNoLength'])) $typePiece = $type;
                                else $typePiece = $type . '(' . $column['maxlen'] . ')';

                                break;
                            }
                        }

                        if (!strlen($typePiece)) {
                            $typePiece = $this->dataTypes['columnStringNoLength']['default'];
                        }
                    }

//        $typePiece .= ' CHARACTER SET utf8 COLLATE utf8_bin';
                    break;

                case 'bitfield':
                    if ($this->nativeBitfield) {

                    } else {
                        if ($column['bits']) {
                            foreach ($this->dataTypes['columnBitLimits'] AS $bits => $type) {
                                if ($column['bits'] <= $bits) {
                                    $typePiece = $type;
                                    break;
                                }
                            }
                        }

                        if (!strlen($typePiece)) {
                            $typePiece = $this->dataTypes['columnBitLimits']['default'];
                        }
                    }
                    break;

                case 'time':
                    $typePiece = $this->dataTypes['time']; // Note: replace with LONGINT to avoid the Epoch issues in 2038 (...I'll do it in FIM5 or so). For now, it's more optimized. Also, since its UNSIGNED, we actually have more until 2106 or something like that.
                    break;

                case 'bool':
                    $typePiece = $this->dataTypes['bool'];
                    break;

                default:
                    $this->triggerError("Unrecognised Column Type", array(
                        'tableName' => $tableName,
                        'columnName' => $columnName,
                        'columnType' => $column['type'],
                    ), 'validation');
                    break;
            }


            if ($column['default'] !== null) {
                // We use triggers here when the SQL implementation is otherwise stubborn, but FreezeMessenger is designed to only do this when it would otherwise be tedious. Manual setting of values is preferred in most cases.
                if ($column['default'] === '__TIME__') {
                    $triggers[] = "DROP TRIGGER IF EXISTS {$tableName}_{$columnName}__TIME__;";
                    $triggers[] = "CREATE TRIGGER {$tableName}_{$columnName}__TIME__ BEFORE INSERT ON $tableName FOR EACH ROW SET NEW.{$columnName} = UNIX_TIMESTAMP(NOW());";
                }
                else if (isset($this->defaultPhrases[$column['default']]))
                    $typePiece .= ' DEFAULT ' . $this->defaultPhrases[$column['default']];
                else
                    $typePiece .= ' DEFAULT ' . $this->formatValue('string', $column['default']); // TODO: non-string?
            }

            $columns[] = $this->formatValue('column', $columnName) . ' ' . $typePiece . ' COMMENT ' . $this->formatValue('string', $column['comment']);
        }


        if (count($tableIndexes)) {
            $indexes = array();

            foreach ($tableIndexes AS $indexName => $index) {
                if (isset($this->keyTypeConstants[$index['type']])) {
                    $typePiece = $this->keyTypeConstants[$index['type']];
                } else {
                    $this->triggerError("Unrecognised Index Type", array(
                        'tableName' => $tableName,
                        'indexName' => $indexName,
                        'indexType' => $index['type'],
                    ), 'validation');
                }


                if (strpos($indexName, ',') !== false) {
                    $indexCols = explode(',', $indexName);

                    foreach ($indexCols AS &$indexCol) $indexCol = $this->formatValue('column', $indexCol);

                    $indexName = implode(',', $indexCols);
                } else {
                    $this->formatValue('index', $indexName);
                }


                $indexes[] = "{$typePiece} ({$indexName})";
            }
        }

        $this->startTransaction();

        $return = $this->rawQuery('CREATE TABLE IF NOT EXISTS ' . $this->formatValue('table', $tableName) . ' (
' . implode(",\n  ", $columns) . (isset($indexes) ? ',
' . implode(",\n  ", $indexes) : '') . '
)'
            . ($this->language === 'mysql' ? ' ENGINE=' . $this->formatValue('string', $engineName) : '')
            . ' COMMENT=' . $this->formatValue('string', $tableComment)
            . ' DEFAULT CHARSET=' . $this->formatValue('string', 'utf8') . $tableProperties
            . ($partitionColumn ? ' PARTITION BY HASH(' . $this->formatValue('column', $partitionColumn) . ') PARTITIONS 100' : ''));

        foreach ($triggers AS $trigger) {
            $return = $this->rawQuery($trigger) && $return; // Make $return false if any query return false.
        }

        $this->endTransaction();

        return $return;
    }


    public function deleteTable($tableName)
    {
        return $this->rawQuery('DROP TABLE ' . $this->formatValue('table', $tableName));
    }


    public function renameTable($oldName, $newName)
    {
        return $this->rawQuery('RENAME TABLE ' . $this->formatValue('table', $oldName) . ' TO ' . $this->formatValue('table', $newName));
    }


    public function getTablesAsArray()
    {
        switch ($this->language) {
            case 'mysql':
            case 'postgresql':
                $tables = $this->rawQuery('SELECT * FROM ' . $this->formatValue('databaseTable', 'INFORMATION_SCHEMA', 'TABLES') . ' WHERE TABLE_SCHEMA = ' . $this->formatValue('string', $this->activeDatabase))->getColumnValues('TABLE_NAME');
                break;
        }

        return $tables;
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
        $subQueries = array();



        /* Where()/sort()/limit() overrides */
        if ($this->conditionArray) {
            if ($conditionArray) throw new Exception('Condition array declared both in where() and select().');

            $conditionArray = $this->conditionArray; $this->conditionArray = array();

        }
        if ($this->sortArray) {
            if ($sort !== false) throw new Exception("Sort array declared both in sort() and select().");

            $sort = $this->sortArray; $this->sortArray = array();
        }

        if ($this->limitArray) {
            if ($limit !== false) throw new Exception("Limit array declared both in sort() and select().");

            $limit = $this->limitArray; $this->limitArray = array();
        }


        /* Process Columns */
        // If columns is a string, then it is a table name, whose columns should be guessed from the other parameters. For now, this guessing is very limited -- just taking the array_keys of $conditionArray (TODO).
        if (is_string($columns)) {
            $columns = array(
                "$columns" => array_keys($conditionArray)
            );
        } elseif (!is_array($columns)) {
            $this->triggerError('Invalid Select Array (Columns Not String or Array)', array(), 'validation');
        } elseif (!count($columns)) {
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

            if (strpos($tableName, 'sub ') === 0) { // If the table is identified as being part of a subquery. (TODO)
                $subQueries[substr($tableName, 4)] = $tableCols;

                continue;
            } elseif (isset($subQueries[$tableName])) { // If the table was earlier defined as a subquery. (TODO)
                $finalQuery['tables'][] = '(' . $subQueries[$tableName] . ') AS ' . $tableName; // TODO: Format value?
            } elseif (strstr($tableName, ' ') !== false) { // A space can be used to create a table alias, which is sometimes required for different queries.
                $tableParts = explode(' ', $tableName);

                $finalQuery['tables'][] = $this->formatValue('tableAlias', $tableParts[0], $tableParts[1]); // Identify the table as [tableName] AS [tableAlias]; note: may be removed if the table is part of a join.

                $tableName = $tableParts[1];
            } else {
                $finalQuery['tables'][] = $this->formatValue('table', $tableName); // Identify the table as [tableName]; note: may be removed if the table is part of a join.
            }

            if (is_array($tableCols)) { // Table columns have been defined with an array, e.g. ["a", "b", "c"]
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

                            if ($colAlias['joinOn']) {
                                $joinTableName = array_pop($finalQuery['tables']);;

                                $finalQuery['join'][] = $joinTableName . ' ON ' . $reverseAlias[$colAlias['joinOn']] . ' = ' . $this->formatValue('tableColumn', $tableName, $colName);
                            } else {
                                $finalQuery['columns'][] = $this->formatValue('tableColumnAlias', $tableName, $colName, $colAlias['alias']);
                            }

                            $reverseAlias[$colAlias['alias']] = $this->formatValue('tableColumn', $tableName, $colName);
                        } else {
                            $finalQuery['columns'][] = $this->formatValue('tableColumnAlias', $tableName, $colName, $colAlias);
                            $reverseAlias[$colAlias] = $this->formatValue('tableColumn', $tableName, $colName);
                        }
                    } else {
                        $this->triggerError('Invalid Select Array (Empty Column Name)', array(
                            'tableName' => $tableName,
                            'columnName' => $colName,
                        ), 'validation');
                    }
                }
            } elseif (is_string($tableCols)) { // Table columns have been defined with a string list, e.g. "a,b,c"
                $colParts = explode(',', $tableCols); // Split the list into an array, delimited by commas

                foreach ($colParts AS $colPart) { // Run through each list item
                    $colPart = trim($colPart); // Remove outside whitespace from the item

                    if (strpos($colPart, ' ') !== false) { // If a space is within the part, then the part is formatted as "columnName columnAlias"
                        $colPartParts = explode(' ', $colPart); // Divide the piece

                        $colPartName = $colPartParts[0]; // Set the name equal to the first part of the piece
                        $colPartAlias = $colPartParts[1]; // Set the alias equal to the second part of the piece
                    } else { // Otherwise, the column name and alias are one in the same.
                        $colPartName = $colPart; // Set the name and alias equal to the piece
                        $colPartAlias = $colPart;
                    }

                    //$finalQuery['columns'][] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd . $this->tableColumnDivider . $this->columnQuoteStart . $columnPartName . $this->columnQuoteStart . ' AS ' . $this->columnAliasQuoteEnd . $columnPartAlias . $this->columnAliasQuoteStart;
                    // $reverseAlias[$columnPartAlias] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd . $this->tableColumnDivider . $this->columnQuoteStart . $columnPartName . $this->columnQuoteStart;

                    $finalQuery['columns'][] = $this->formatValue('tableColumnAlias', $tableName, $colPartName, $colPartAlias);
                    $reverseAlias[$colPartAlias] = $this->formatValue('tableColumn', $tableName, $colPartName);
                }
            }
        }


        /* Process Conditions (Must be Array) */
        if (is_array($conditionArray) && count($conditionArray)) {
            $finalQuery['where'] = $this->recurseBothEither($conditionArray, $reverseAlias, 'both', $tableName);
        }



        /* Process Sorting (Must be Array)
         * TODO: Combine the array and string routines to be more effective. */
        if ($sort !== false) {
            if (is_array($sort)) {
                if (count($sort) > 0) {
                    foreach ($sort AS $sortColumn => $direction) {
                        if (isset($reverseAlias[$sortColumn])) {
                            switch (strtolower($direction)) {
                                case 'asc': $directionSym = $this->sortOrderAsc; break;
                                case 'desc': $directionSym = $this->sortOrderDesc; break;
                                default: $directionSym = $this->sortOrderAsc; break;
                            }

                            $finalQuery['sort'][] = $reverseAlias[$sortColumn] . " $directionSym";
                        }
                        else {
                            $this->triggerError('Unrecognised Sort Column', array(
                                'sortColumn' => $sortColumn,
                            ), 'validation');
                        }
                    }
                }
            }
            elseif (is_string($sort)) {
                $sortParts = explode(',', $sort); // Split the list into an array, delimited by commas

                foreach ($sortParts AS $sortPart) { // Run through each list item
                    $sortPart = trim($sortPart); // Remove outside whitespace from the item

                    if (strpos($sortPart,' ') !== false) { // If a space is within the part, then the part is formatted as "columnName direction"
                        $sortPartParts = explode(' ',$sortPart); // Divide the piece

                        $sortColumn = $sortPartParts[0]; // Set the name equal to the first part of the piece
                        switch (strtolower($sortPartParts[1])) {
                            case 'asc':  $directionSym = $this->sortOrderAsc;  break;
                            case 'desc': $directionSym = $this->sortOrderDesc; break;
                            default:     $directionSym = $this->sortOrderAsc;  break;
                        }
                    }
                    else { // Otherwise, we assume asscending
                        $sortColumn = $sortPart; // Set the name equal to the sort part.
                        $directionSym = $this->sortOrderAsc; // Set the alias equal to the default, ascending.
                    }

                    $finalQuery['sort'][] = $reverseAlias[$sortColumn] . " $directionSym";
                }
            }

            $finalQuery['sort'] = implode(', ', $finalQuery['sort']);
        }



        /* Process Limit (Must be Integer) */
        if ($limit !== false) {
            if (is_int($limit)) {
                $finalQuery['limit'] = (int) $limit;
            }
        }
        $finalQuery['page'] = (int) $page;
        if ($finalQuery['page'] < 0) $finalQuery['page'] = 0;


        /* Generate Final Query */
        $finalQueryText = 'SELECT
  ' . implode(',
  ', $finalQuery['columns']) . '
FROM
  ' . implode(', ', $finalQuery['tables']) . ($finalQuery['join'] ? '
LEFT JOIN
  ' . implode("\n", $finalQuery['join']) : '') . ($finalQuery['where'] ? '
WHERE
  ' . $finalQuery['where'] : '') . ($finalQuery['sort'] ? '
ORDER BY
  ' . $finalQuery['sort'] : '') . ($finalQuery['limit'] ? '
LIMIT
 ' . $finalQuery['limit'] * $finalQuery['page'] . ',
  ' . $finalQuery['limit'] : '');

        /* And Run the Query */
        if ($this->returnQueryString) return $finalQueryText;
        else return $this->rawQuery($finalQueryText);
    }


    public function subSelect($columns, $conditionArray = false, $sort = false, $limit = false)
    {
        $this->returnQueryString = true;
        $return = $this->select($columns, $conditionArray, $sort, $limit, true);
        $this->returnQueryString = false;
        return $return;
    }


    /**
     * Recurses over a specified "where" array, returning a valid where clause.
     *
     * @param array $conditionArray - The conditions to transform into proper SQL.
     * @param array $reverseAlias - An array corrosponding to column aliases and their database counterparts.
     *
     * @return string
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    private function recurseBothEither($conditionArray, $reverseAlias = false, $type = 'both', $tableName = false)
    {
        $i = 0;

        if (!is_array($conditionArray)) throw new Exception('Condition array must be an array.');
        elseif (!count($conditionArray)) return 'true';

        // $key is usually a column, $value is a formatted value for the select() function.
        foreach ($conditionArray AS $key => $value) {
            /* @var $value DatabaseType */

            $i++;

            if (strstr($key, ' ') !== false) list($key) = explode(' ', $key); // A space can be used to reference the same key twice in different contexts. It's basically a hack, but it's better than using further arrays.

            if ($key === 'both' || $key === 'either' || $key === 'neither') { // TODO: neither?
                $sideTextFull[$i] = $this->recurseBothEither($value, $reverseAlias, $key, $tableName);
            } else {
                // Defaults
                $sideTextFull[$i] = '';
                if (!is_object($value)) $value = $this->str($value);  // If value is not a DatabaseType, treat it as a string.
                else if (get_class($value) !== 'DatabaseType') throw new Exception('Invalid class for value.');


                // Side Text Left
                $column = ($this->startsWith($key, '!') ? substr($key, 1) : $key);
                $sideText['left'] = ($reverseAlias ? $reverseAlias[$column] : $column); // Get the column definition that corresponds with the named column. "!column" signifies negation.



                // Comparison Operator
                $symbol = $this->comparisonTypes[$value->comparison];


                // Side Text Right
                if ($value->type === DatabaseTypeType::null)
                    $sideText['right'] = 'IS NULL';

                elseif ($value->type === DatabaseTypeType::column)
                    $sideText['right'] = ($reverseAlias ? $reverseAlias[$value->value] : $value->value); // The value is a column, and should be returned as a reverseAlias. (Note that reverseAlias should have already called formatValue)

                else {
                    if ($tableName && isset($this->encodeRoomId[$tableName]) && in_array($column, $this->encodeRoomId[$tableName])) {
                        $value->value = fimRoom::encodeId($value->value);
                    }

                    $sideText['right'] = $this->formatValue(($value->comparison === DatabaseTypeComparison::search ? 'search' : $value->type), $value->value); // The value is a data type, and should be processed as such.
                }


                // Side Text Full
                if ((strlen($sideText['left']) > 0) && (strlen($sideText['right']) > 0)) {
                    $sideTextFull[$i] = ($this->startsWith($key, '!') ? '!' : '') . "({$sideText['left']} {$symbol} {$sideText['right']})";
                }

                else {//var_dump($reverseAlias); echo $key;  var_dump($value); var_dump($sideText); die();
                    $sideTextFull[$i] = "FALSE"; // Instead of throwing an exception, which should be handled above, instead simply cancel the query in the cleanest way possible. Here, it's specifying "FALSE" in the where clause to prevent any results from being returned.

                    $this->triggerError('Query Nullified', array('Key' => $key, 'Value' => $value, 'Side Text' => $sideText, 'Reverse Alias' => $reverseAlias), 'validation'); // Dev, basically. TODO.
                }
            }
        }


        if (!isset($this->concatTypes[$type])) { var_dump($type);
            $this->triggerError('Unrecognised Concatenation Operator', array(
                'operator' => $type,
            ), 'validation');
        }


        return '(' . implode($this->concatTypes[$type], $sideTextFull) . ')'; // Return condition string. We wrap parens around to support multiple levels of conditions/recursion.
    }


    /**
     * Inserts structured data into a table.
     *
     * @param string $table - The table to insert into.
     * @param array $dataArray - A two-dimensional array [columnName => value]
     *
     * @return bool|resource
     * @throws exception
     */
    public function insert($table, $dataArray, $updateArray = false)
    {
        if ($updateArray) throw new exception('Removed.'); // TODO

        $columns = array_keys($dataArray);
        $values = array_values($dataArray);

        $query = 'INSERT INTO' . $this->formatValue('table', $table) . ' ' .
            $this->formatValue('tableColumnValues', $table, $columns, $values);

        if ($queryData = $this->rawQuery($query)) {
            $this->insertId = $this->functionMap('insertId');

            return $queryData;
        } else {
            return false;
        }
    }


    /**
     * Deletes data from a table.
     *
     * @param string $tableName - Table to delete from.
     * @param bool $conditionArray - The conditions for the deletion (uses the same format as the select() call).
     *
     * @return bool|resource
     */
    public function delete($tableName, $conditionArray = false)
    {
        $query = 'DELETE FROM ' . $this->formatValue('table', $tableName) . '
    WHERE ' . ($conditionArray ? $this->recurseBothEither($conditionArray, false, 'both', $tableName) : 'TRUE');

        return $this->rawQuery($query);
    }


    /**
     * @param string $tableName - Table to update.
     * @param array $dataArray - The data to replace with. This can include an equation using $this->type('equation', '$columnName + C').
     * @param bool $conditionArray - Conditions for selecting data to update (uses the same format as the select() call).
     *
     * @return bool|resource
     */
    public function update($tableName, $dataArray, $conditionArray = false)
    {
        $query = 'UPDATE ' . $this->formatValue('table', $tableName) . ' SET ' . $this->formatValue('tableUpdateArray', $tableName, $dataArray) . ' WHERE ' . $this->recurseBothEither($conditionArray, false, 'both', $tableName);

        return $this->rawQuery($query);
    }


    public function upsert($table, $conditionArray, $dataArray)
    {
        switch ($this->language) {
            case 'mysql':
                $allArray = array_merge($dataArray, $conditionArray);
                $allColumns = array_keys($allArray);
                $allValues = array_values($allArray);

                $query = 'INSERT INTO ' . $this->formatValue('table', $table) . '
        ' . $this->formatValue('tableColumnValues', $table, $allColumns, $allValues) . '
        ON DUPLICATE KEY UPDATE ' . $this->formatValue('tableUpdateArray', $table, $dataArray);

                if ($queryData = $this->rawQuery($query)) {
                    $this->insertId = $this->functionMap('insertId');

                    return $queryData;
                } else {
                    return false;
                }
                break;
        }
    }
    /*********************************************************
     ************************* END ***************************
     ******************** Row Functions **********************
     *********************************************************/

}

?>