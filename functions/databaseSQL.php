<?php
/* FreezeMessenger Copyright © 2012 Joseph Todd Parsons

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
 
 
class databaseSQL extends database {  
  protected $language = '';
  public $storeTypes = array('memory', 'general', 'innodb');

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
  private function functionMap($operation) {
    $args = func_get_args();

    switch ($this->language) {
      case 'mysql':
      switch ($operation) {
        case 'connect':
          $function = mysql_connect("$args[1]:$args[2]", $args[3], $args[4]);
          $this->version = mysql_get_server_info($function);

          return $function;
        break;

        case 'error':
          if (isset($this->dbLink)) return mysql_error($this->dbLink);
          else                      return mysql_error();
        break;

        case 'close':
          $function = mysql_close($this->dbLink);

          unset($this->dbLink);

          return $function;
        break;

        case 'selectdb': return mysql_select_db($args[1], $this->dbLink);              break;
        case 'escape':   return mysql_real_escape_string($args[1], $this->dbLink);     break;
        case 'query':    return mysql_query($args[1], $this->dbLink);                  break;
        case 'insertId': return mysql_insert_id($this->dbLink);                        break;
        default:         throw new Exception('Unrecognised Operation: ' . $operation); break;
      }
      break;


      case 'mysqli':
      switch ($operation) {
        case 'connect':
          $function = mysqli_connect($args[1], $args[3], $args[4], ($args[5] ? $args[5] : null), (int) $args[2]);
          $this->version = mysqli_get_server_info($function);

          return $function;
        break;

        case 'error':
          if (isset($this->dbLink)) return mysqli_error($this->dbLink);
          else                      return mysqli_connect_error();
        break;

        case 'selectdb': return mysqli_select_db($this->dbLink, $args[1]);             break;
        case 'close':    return mysqli_close($this->dbLink);                           break;
        case 'escape':   return mysqli_real_escape_string($this->dbLink, $args[1]);    break;
        case 'query':    return mysqli_query($this->dbLink, $args[1]);                 break;
        case 'insertId': return mysqli_insert_id($this->dbLink);                       break;
        default:         throw new Exception('Unrecognised Operation: ' . $operation); break;
      }
      break;


      case 'postgresql':
      switch ($operation) {
        case 'connect': return pg_connect("host=$args[1] port=$args[2] username=$args[3] password=$args[4] dbname=$args[5]"); break;
        case 'error':   return pg_last_error($this->dbLink);                                                                  break;
        case 'close':   return pg_close($this->dbLink);                                                                       break;
        case 'escape':  return pg_escape_string($this->dbLink, $args[1]);                                                     break;
        case 'query':   return pg_query($this->dbLink, $args[1]);                                                             break;

        //case 'insertId': return mysqli_insert_id($this->dbLink); break;

        default:        throw new Exception('Unrecognised Operation: ' . $operation);                                         break;
      }
      break;
    }
  }


  
  public function connect($host, $port, $user, $password, $database, $driver) {
    $this->setLanguage($driver);


    if (!$link = $this->functionMap('connect', $host, $port, $user, $password, $database)) { // Make the connection.
      $this->error = 'The connection was refused: ' . $this->functionMap('error');

      return false;
    }
    else {
      $this->dbLink = $link; // Set the object property "dbLink" to the database connection resource. It will be used with most other queries that can accept this parameter.
    }


    if (!$this->activeDatabase && $database) { // Some drivers will require this.
      if (!$this->selectDatabase($database)) {
        $this->error = 'Could not select database ("' . $database . '"): ' . $this->functionMap('error');

        return false;
      }
    }


    return true;
  }
  
  
  
  public function close() {
    return $this->functionMap('close');
  }
  
    
    
  /**
   * Set Language / Database Driver
   *
   * @param string language
   * @return void
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  private function setLanguage($language) {
    $this->language = $language;

    switch ($this->language) {
      case 'mysql':
      case 'mysqli':
      $this->languageSubset = 'sql';

      $this->tableQuoteStart = '`';  $this->tableQuoteEnd = '`';  $this->tableAliasQuoteStart = '`';  $this->tableAliasQuoteEnd = '`';
      $this->columnQuoteStart = '`'; $this->columnQuoteEnd = '`'; $this->columnAliasQuoteStart = '`'; $this->columnAliasQuoteEnd = '`';
      $this->stringQuoteStart = '"'; $this->stringQuoteEnd = '"'; $this->emptyString = '""';          $this->tableColumnDivider = '.';
      $this->intQuoteStart = ''; $this->intQuoteEnd = '';
      $this->stringFuzzy = '%';

      $this->sortOrderAsc = 'ASC'; $this->sortOrderDesc = 'DESC';

      $this->tableTypes = array(
        'general' => 'InnoDB',
        'memory' => 'MEMORY',
      );
      break;

      case 'postgresql':
      $this->languageSubset = 'sql';

      $this->tableQuoteStart = '"';    $this->tableQuoteEnd = '"';    $this->tableAliasQuoteStart = '"';  $this->tableAliasQuoteEnd = '"';
      $this->columnQuoteStart = '"';   $this->columnQuoteEnd = '"';   $this->columnAliasQuoteStart = '"'; $this->columnAliasQuoteEnd = '"';
      $this->stringQuoteStart = '"';   $this->stringQuoteEnd = '"';   $this->emptyString = '""';          $this->tableColumnDivider = '.';    
      $this->intQuoteStart = ''; $this->intQuoteEnd = '';
      $this->stringFuzzy = '%';


      $this->sortOrderAsc = 'ASC'; $this->sortOrderDesc = 'DESC';
      break;
    }

    switch ($this->language) {
      case 'mysql':
      case 'mysqli':
      case 'postgresql':
      $this->comparisonTypes = array(
        'e' => '=',
        'ne' => '!=',  '!e' => '!=', // same
        'lt' => '<',   '!gte' => '>', // same
        'gt' => '>',   '!lte' => '>', // same
        'lte' => '<=', '!gt' => '>=', // same
        'gte' => '>=', '!lt' => '>=', // same

        'and' => '&',  '!xor' => '&', // same
        'xor' => '^',  '!and' => '^', // same

        'in' => 'IN',        '!notin' => 'IN', // same
        'notin' => 'NOT IN', '!in' => 'NOT IN', // same

        'regexp' => 'REGEXP', // Applies extended POSIX regular expression to index. It is natively implemented in MySQL, PostGreSQL, and Oracle SQL databases. It is absent in MSSQL, and the status in VoltDB and SQLite is unknown.
        'regex' => 'REGEXP', // Alias of "regexp"

        'glob' => 'LIKE',
        'like' => 'LIKE', // Alias of "glob"
      );

      $this->concatTypes = array(
        'both' => ' AND ',
        'either' => ' OR ',
      );

      $this->keyConstants = array(
        'primary' => 'PRIMARY KEY',
        'unique' => 'UNIQUE KEY',
        'index' => 'KEY',
      );

      $this->defaultPhrases = array(
        '__TIME__' => 'CURRENT_TIMESTAMP',
      );

      $this->columnIntLimits = array(
        1 => 'TINYINT',   2 => 'TINYINT',   3 => 'SMALLINT',  4 => 'SMALLINT',
        5 => 'MEDIUMINT', 6 => 'MEDIUMINT', 7 => 'MEDIUMINT', 8 => 'INT',
        9 => 'INT',       0 => 'BIGINT',
      );

      $this->columnStringPermLimits = array(
        1 => 'CHAR',           255 => 'VARCHAR', 1000 => 'TEXT', 8191 => 'MEDIUMTEXT',
        2097151 => 'LONGTEXT',
      );

      $this->columnStringTempLimits = array(
        255 => 'CHAR',           65535 => 'VARCHAR',
      );

      $this->columnStringNoLength = array('MEDIUMTEXT', 'LONGTEXT');

      $this->columnBitLimits = array(
        0 => 'TINYINT UNSIGNED',  8 => 'TINYINT UNSIGNED', 16 => 'SMALLINT UNSIGNED', 24 => 'MEDIUMINT UNSIGNED',
        32 => 'INTEGER UNSIGNED', 64 => 'LONGINT UNSIGNED',
      );

      $this->globFindArray = array('*', '?');
      $this->globReplaceArray = array('%', '_');
      
      $this->mode = 'SQL';
      break;
    }
  }
  

  
  public function escape($string, $context = 'string') {
    return $this->functionMap('escape', $string, $context); // Retrun the escaped string.
  }


  
  /**
   * Sends a raw, unmodified query string to the database server.
   * The query may be logged if it takes a certain amount of time to execute successfully.
   *
   * @param string $query - The raw query to execute.
   * @return resource|bool - The database resource returned by the query, or false on failure.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  private function rawQuery($query, $suppressErrors = false) {
    $this->sourceQuery = $query;

    if ($queryData = $this->functionMap('query', $query)) {
      $this->queryCounter++;

      if ($queryData === true) return true; // Insert, Update, Delete, etc.
      else return new databaseResult($queryData, $query, $this->language); // Select, etc.
    }
    else {
      $this->triggerError('Database Error', array(
        'query' => $query;
        'error' => $this->functionMap('error')
      ), 'syntax', $suppressErrors); // The query could not complete.

      return false;
    }
  }
  
  /*********************************************************
  ************************* END ***************************
  ******************* General Functions *******************
  *********************************************************/

  
  
  /*********************************************************
  ************************ START **************************
  ****************** Database Functions *******************
  *********************************************************/
  
  public function selectDatabase($database) {
    $error = false;
    
    if ($this->functionMap('selectdb', $database)) { // Select the database.      
      if ($this->language == 'mysql' || $this->language == 'mysqli') {
        if (!$this->rawQuery('SET NAMES "utf8"', true)) { // Sets the database encoding to utf8 (unicode).
          $error = 'SET NAMES Query Failed';
        }
      }
    }
    else {
      $error = 'Failed to Select Database';
    }
    
    if ($error) {
      $this->triggerError($error, array(
        'database' => $database,
        'query' => $this->getLastQuery(),
        'sqlError' => $this->getLastError()
      ), 'function');
      return false;
    }
    else {
      $this->activeDatabase = $database;
      return true;
    }
  }
  
  
  
  public function createDatabase($database) {
    return $this->rawQuery('CREATE DATABASE IF NOT EXISTS ' . $this->databaseQuoteStart . $database . $this->databaseQuoteEnd);
  }
  
  /*********************************************************
  ************************* END ***************************
  ****************** Database Functions *******************
  *********************************************************/
  
  
  
  /*********************************************************
  ************************ START **************************
  ******************* Table Functions *********************
  *********************************************************/

  public function createTable($tableName, $tableComment, $engine, $tableColumns, $tableIndexes) {
    if (isset($this->tableTypes[$storeType])) {
      $engine = $this->tableTypes[$storeType];
    }
    else {
      $this->triggerError("Unrecognised table engine: '$storeType'.", 'validation');
    }

    $tableProperties = '';
    
    foreach ($tableColumns AS $column) {
      $typePiece = '';

      switch ($column['type']) {
        case 'int':
        if (isset($this->columnIntLimits[$column['maxlen']])) {
          if (in_array($type, $this->columnStringNoLength)) $typePiece = $this->columnIntLimits[$column['maxlen']];
          else $typePiece = $this->columnIntLimits[$column['maxlen']] . '(' . (int) $column['maxlen'] . ')';
        }
        else {
          $typePiece = $this->columnIntLimits[0];
        }

        if (isset($column['autoincrement']) && $column['autoincrement']) {
          $typePiece .= ' AUTO_INCREMENT'; // Ya know, that thing where it sets itself.
          $tableProperties .= ' AUTO_INCREMENT = ' . (int) $column['autoincrement'];
        }
        break;

        case 'string':
        if ($column['restrict']) {
          $restrictValues = array();

          foreach ((array) $column['restrict'] AS $value) $restrictValues[] = '"' . $this->escape($value) . '"';

          $typePiece = 'ENUM(' . implode(',',$restrictValues) . ')';
        }
        else {
          if ($storeType === 'memory') $this->columnStringLimits = $this->columnStringTempLimits;
          else                         $this->columnStringLimits = $this->columnStringPermLimits;

          $typePiece = '';

          foreach ($this->columnStringLimits AS $length => $type) {
            if ($column['maxlen'] <= $length) {
              if (in_array($type, $this->columnStringNoLength)) $typePiece = $type;
              else $typePiece = $type . '(' . $column['maxlen'] . ')';

              break;
            }
          }

          if (!$typePiece) {
            $typePiece = $this->columnStringNoLength[0];
          }
        }

        $typePiece .= ' CHARACTER SET utf8 COLLATE utf8_bin';
        break;

        case 'bitfield':
        if (!isset($column['bits'])) {
          $typePiece = 'TINYINT UNSIGNED'; // Sane default
        }
        else {
          if ($column['bits'] <= 8)      $typePiece = 'TINYINT UNSIGNED';
          elseif ($column['bits'] <= 16) $typePiece = 'SMALLINT UNSIGNED';
          elseif ($column['bits'] <= 24) $typePiece = 'MEDIUMINT UNSIGNED';
          elseif ($column['bits'] <= 32) $typePiece = 'INTEGER UNSIGNED';
          else                           $typePiece = 'LONGINT UNSIGNED';
        }
        break;

        case 'time':
        $typePiece = 'INTEGER UNSIGNED'; // Note: replace with LONGINT to avoid the Epoch issues in 2038 (...I'll do it in FIM5 or so). For now, it's more optimized. Also, since its UNSIGNED, we actually have more until 2106 or something like that.
        break;

        case 'bool':
        $typePiece = 'TINYINT(1) UNSIGNED';
        break;

        default:
        $this->triggerError("Unrecognised column type: '$column[type]'.");
        break;
      }


      if ($column['default']) {
        if (isset($this->defaultPhrases[$column['default']])) {
          $typePiece .= ' DEFAULT ' . $this->defaultPhrases[$column['default']];
        }
        else {
          $typePiece .= ' DEFAULT "' . $this->escape($column['default']) . '"';
        }
      }

      $columns[] = $this->columnQuoteStart . $this->escape($column['name']) . $this->columnQuoteEnd . " {$typePiece} NOT NULL COMMENT \"" . $this->escape($column['comment']) . '"';
    }



    foreach ($tableIndexes AS $name => $key) {
      if (isset($this->keyConstants[$key['type']])) {
        $typePiece = $this->keyConstants[$key['type']];
      }
      else {
        $this->triggerError("Unrecognised key type: '$key[type]'.");
      }


      if (strpos($name, ',') !== false) {
        $keyCols = explode(',', $name);

        foreach ($keyCols AS &$keyCol) {
          $keyCol = $this->columnQuoteStart . $keyCol . $this->columnQuoteEnd;
        }

        $name = implode(',', $keyCols);
      }
      else {
        $name = $this->columnAliasStart . $name . $this->columnAliasEnd;
      }


      $keys[] = "{$typePiece} ({$key['name']})";
    }

    return $this->rawQuery('CREATE TABLE IF NOT EXISTS ' . $this->tableQuoteStart . $this->escape($tableName) . $this->tableQuoteEnd . ' (
' . implode(",\n  ",$columns) . ',
' . implode(",\n  ",$keys) . '
) ENGINE="' . $this->escape($engine) . '" COMMENT="' . $this->escape($tableComment) . '" DEFAULT CHARSET="utf8"' . $tableProperties);
  }
  
  
  
  public function deleteTable($tableName) {
    return $this->rawQuery('DROP TABLE ' . $this->tableQuoteStart . $this->escape($tableName) . $this->tableQuoteEnd);
  }
  
  
  
  public function renameTable($oldName, $newName) {
    return $this->rawQuery('RENAME TABLE ' . $this->tableQuoteStart . $this->escape($oldName) . $this->tableQuoteEnd . ' TO ' . $this->tableQuoteStart . $this->escape($newName) . $this->tableQuoteEnd);
  }
  
  
  
  public function getTablesAsArray() {
    switch ($this->language) {
      case 'mysql':
      case 'mysqli':
      case 'postgresql':
      $tables = $this->rawQuery('SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA LIKE "' . $this->escape($this->activeDatabase) . '"');
      $tables = $tables->getAsArray('TABLE_NAME');
      $tables = array_keys($tables);
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
  
  public function select($columns, $conditionArray = false, $sort = false, $limit = false) {
      /* Define Variables */
    $finalQuery = array(
      'columns' => array(),
      'tables' => array(),
      'where' => '',
      'sort' => array(),
      'group' => '',
      'limit' => 0
    );
    $reverseAlias = array();


    /* Process Columns (Must be Array) */
    if (is_array($columns)) {
      if (count($columns) > 0) {
        foreach ($columns AS $tableName => $tableCols) {
          if (strlen($tableName) > 0) { // If the tableName is defined...
            if (strstr($tableName,' ') !== false) { // A space can be used to create a table alias, which is sometimes required for different queries.
              $tableParts = explode(' ', $tableName);

              $finalQuery['tables'][] = $this->tableQuoteStart . $tableParts[0] . $this->tableQuoteEnd . ' AS ' . $this->tableAliasQuoteStart . $tableParts[1] . $this->tableAliasQuoteEnd; // Identify the table as [tableName] AS [tableAlias]

              $tableName = $tableParts[1];
            }
            else {
              $finalQuery['tables'][] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd; // Identify the table as [tableName]
            }

            if (is_array($tableCols)) { // Table columns have been defined with an array, e.g. ["a", "b", "c"]
              foreach($tableCols AS $colName => $colAlias) {
                if (strlen($colName) > 0) {
                  if (strstr($colName,' ') !== false) { // A space can be used to create identical columns in different contexts, which is sometimes required for different queries.
                    $colParts = explode(' ', $colName);
                    $colName = $colParts[0];
                  }

                  if (is_array($colAlias)) { // Used for advance structures and function calls.
                    if (isset($colAlias['context'])) {
                      throw new Exception('Deprecated context.'); // TODO
                    }

                    $finalQuery['columns'][] = $this->columnQuoteStart . $colName . $this->columnQuoteStart . ' AS ' . $this->columnAliasQuoteEnd . $colAlias['name'] . $this->columnAliasQuoteStart; // Identify column as [columnName] AS [columnAlias]
                    $reverseAlias[$colAlias['name']] = $colName;
                  }
                  else {
                    $finalQuery['columns'][] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd . $this->tableColumnDivider . $this->columnQuoteStart . $colName . $this->columnQuoteStart . ' AS ' . $this->columnAliasQuoteEnd . $colAlias . $this->columnAliasQuoteStart;
                    $reverseAlias[$colAlias] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd . $this->tableColumnDivider . $this->columnQuoteStart . $colName . $this->columnQuoteStart;
                  }
                }
                else {
                  throw new Exception('Invalid select array: column name empty'); // Throw an exception.
                }
              }
            }
            elseif (is_string($tableCols)) { // Table columns have been defined with a string list, e.g. "a,b,c"
              $columnParts = explode(',', $tableCols); // Split the list into an array, delimited by commas

              foreach ($columnParts AS $columnPart) { // Run through each list item
                $columnPart = trim($columnPart); // Remove outside whitespace from the item

                if (strpos($columnPart,' ') !== false) { // If a space is within the part, then the part is formatted as "columnName columnAlias"
                  $columnPartParts = explode(' ',$columnPart); // Divide the piece

                  $columnPartName = $columnPartParts[0]; // Set the name equal to the first part of the piece
                  $columnPartAlias = $columnPartParts[1]; // Set the alias equal to the second part of the piece
                }
                else { // Otherwise, the column name and alias are one in the same.
                  $columnPartName = $columnPart; // Set the name and alias equal to the piece
                  $columnPartAlias = $columnPart;
                }

                $finalQuery['columns'][] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd . $this->tableColumnDivider . $this->columnQuoteStart . $columnPartName . $this->columnQuoteStart . ' AS ' . $this->columnAliasQuoteEnd . $columnPartAlias . $this->columnAliasQuoteStart;
                $reverseAlias[$columnPartAlias] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd . $this->tableColumnDivider . $this->columnQuoteStart . $columnPartName . $this->columnQuoteStart;
              }
            }
          }
          else {
            throw new Exception('Invalid select array: table name empty'); // Throw an exception.
          }
        }
      }
      else {
        throw new Exception('Invalid select array: no entries'); // Throw an exception.
      }
    }
    else {
      throw new Exception('Invalid select array'); // Throw an exception.
    }



    /* Process Conditions (Must be Array) */
    if ($conditionArray !== false) {
      if (is_array($conditionArray)) {
        if (count($conditionArray) > 0) {
          $finalQuery['where'] = $this->recurseBothEither($conditionArray, $reverseAlias);
        }
      }
    }



    /* Process Sorting (Must be Array)
     * TODO: Combine the array and string routines to be more effective. */
    if ($sort !== false) {
      if (is_array($sort)) {
        if (count($sort) > 0) {
          foreach ($sort AS $sortCol => $direction) {
            if (isset($reverseAlias[$sortCol])) {
              switch (strtolower($direction)) {
                case 'asc': $directionSym = $this->sortOrderAsc; break;
                case 'desc': $directionSym = $this->sortOrderDesc; break;
                default: $directionSym = $this->sortOrderAsc; break;
              }

              $finalQuery['sort'][] = $reverseAlias[$sortCol] . " $directionSym";
            }
            else {
              throw new Exception('Unrecognised sort column: ' . $sortCol);
            }
          }
        }
      }
      elseif (is_string($sort)) {
        $sortParts = explode(',',$sort); // Split the list into an array, delimited by commas

        foreach ($sortParts AS $sortPart) { // Run through each list item
          $sortPart = trim($sortPart); // Remove outside whitespace from the item

          if (strpos($sortPart,' ') !== false) { // If a space is within the part, then the part is formatted as "columnName direction"
            $sortPartParts = explode(' ',$sortPart); // Divide the piece

            $sortCol = $sortPartParts[0]; // Set the name equal to the first part of the piece
            switch (strtolower($sortPartParts[1])) {
              case 'asc': $directionSym = $this->sortOrderAsc; break;
              case 'desc': $directionSym = $this->sortOrderDesc; break;
              default: $directionSym = $this->sortOrderAsc; break;
            }
          }
          else { // Otherwise, we assume asscending
            $sortCol = $sortPart; // Set the name equal to the sort part.
            $directionSym = $this->sortOrderAsc; // Set the alias equal to the default, ascending.
          }

          $finalQuery['sort'][] = $reverseAlias[$sortCol] . " $directionSym";
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



    /* Generate Final Query */
    $finalQueryText = 'SELECT
  ' . implode(',
  ', $finalQuery['columns']) . '
FROM
  ' . implode(', ', $finalQuery['tables']) . ($finalQuery['where'] ? '
WHERE
  ' . $finalQuery['where'] : '') . ($finalQuery['sort'] ? '
ORDER BY
  ' . $finalQuery['sort'] : '') . ($finalQuery['limit'] ? '
LIMIT
  ' . $finalQuery['limit'] : '');


    /* And Run the Query */
    return $this->rawQuery($finalQueryText);
  }
  
  
  
  /**
   * Recurses over a specified "where" array, returning a valid where clause.
   *
   * @param array $conditionArray - The conditions to transform into proper SQL.
   * @param array $reverseAlias - An array corrosponding to column aliases and their database counterparts.
   * @param int $d - The level of recursion.
   *
   * @return string
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  private function recurseBothEither($conditionArray, $reverseAlias, $d = 0) {
    $i = 0;
    $h = 0;

    $whereText = array();

    // $type is either "both", "either", or "neither". $cond is an array of arguments.
    foreach ($conditionArray AS $type => $cond) {
      // First, make sure that $cond isn't empty. Pretty simple.
      if (is_array($cond) && count($cond) > 0) {
        // $key is usually a column, $value is a formatted value for the select() function.
        foreach ($cond AS $key => $value) {
          if ($key === 'both' || $key === 'either' || $key === 'neither') {
            // Recurse TODO
          }
          else {
            /* Value is currently stored as:
             * array(TYPE, VALUE, COMPARISON)
             *
             * Note: We do not want to include quotes/etc. in VALUE yet, because these theoretically could vary based on the comparison type. */

            $i++;
            $sideTextFull[$i] = '';

            $sideText['left'] = $reverseAlias[(startsWith($key, '!') ? $key : $key)]; // Get the column definition that corresponds with the named column. "!column" signifies negation.
            $symbol = $this->comparisonTypes[$value[2]];
            
            
              
            // Value is Array
            if (is_array($value[1])) {
              $sideText['left'] = $reverseAlias[$key];
              $sideText['right'] = implode(',', $value[1]); // TODO: format for non-INTS/escape/etc.
              $symbol = $this->comparisonTypes['in'];
            }
            else {
              switch ($value[0]) { // Switch the value type
                case 'int':
                  $sideText['right'] = $this->intQuoteStart . $value[1] . $this->intQuoteEnd;
                  
                  if ($value[2] === 'search') {
                    $sideText['right'] = $this->stringFuzzy . $sideText['right'] . $this->stringFuzzy;
                  }
                break;
                
                case  'string':
                  $sideText['right'] = $this->stringQuoteStart . $this->escape($value[1]) . $this->stringQuoteEnd;
                break;
                
                case 'column':
                  $sideText['right'] = $this->columnQuoteStart . $value[1] . $this->columnQuoteEnd;
                break;
              }
            }

            
            /* Generate Comparison Part */
            if ((strlen($sideText['left']) > 0) && (strlen($sideText['right']) > 0)) {
              $sideTextFull[$i] = "{$sideText['left']} {$symbol} {$sideText['right']}";
            }
            else {
              $sideTextFull[$i] = "FALSE"; // Instead of throwing an exception, which should be handled above, instead simply cancel the query in the cleanest way possible. Here, it's specifying "FALSE" in the where clause to prevent any results from being returned.
              throw new Exception('Query nullified.');
            }
          }
        }

        if (isset($this->concatTypes[$type])) $condSymbol = $this->concatTypes[$type];
        else throw new Exception('Unrecognised concatenation operator: ' . $type . '; ' . print_r($data, true));

        $whereText[$h] = implode($condSymbol, $sideTextFull);
      }
    }


    // Combine the query array if multiple entries exist, or just get the first entry.
    if (count($whereText) === 0) return false;
    elseif (count($whereText) === 1) $whereText = $whereText[0]; // Get the query string from the first (and only) index.
    else $whereText = implode($this->concatTypes['both'], $whereText);


    return "($whereText)"; // Return condition string. We wrap parens around to support multiple levels of conditions/recursion.
  }
  
  
  
  private function formatSearch($value) {
    return $this->stringQuoteStart . $this->stringFuzzy . $this->escape($value) . $this->stringFuzzy . $this->stringQuoteEnd;
  }
  
  
  
  private function formatString($value) {
    return $this->stringQuoteStart . $this->escape($value) . $this->stringQuoteEnd;
  }
  
  
  
  private function formatInteger($value) {
    return $this->intgQuoteStart . $this->escape($value, 'integer') . $this->intQuoteEnd;
  }
  
  
  
  /**
   * Divides a multidimensional array into three seperate two-dimensional arrays, and performs some additional processing as defined in the passed array. It is used by the insert(), update(), and delete() functions.
   *
   * @param string $array - The source array
   *
   * @return array - An array containing three seperate arrays.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  private function splitArray($array) {
    $columns = array(); // Initialize arrays
    $values = array(); // Initialize arrays

    foreach($array AS $column => $data) { // Run through each element of the $dataArray, adding escaped columns and values to the above arrays.

      if (is_int($data)) { // Safe integer - leave it as-is.
        $columns[] = $this->columnQuoteStart . $this->escape($column) . $this->columnQuoteEnd;
        $context[] = 'e'; // Equals

        $values[] = $data;
      }

      elseif (is_bool($data)) { // In MySQL, true evals to  1, false evals to 0.
        $columns[] = $this->columnQuoteStart . $this->escape($column) . $this->columnQuoteEnd;
        $context[] = 'e'; // Equals

        if ($data === true) $values[] = 1;
        elseif ($data === false) $values[] = 0;
      }

      elseif (is_null($data)) { // Null data, simply make it empty.
        $columns[] = $this->columnQuoteStart . $this->escape($column) . $this->columnQuoteEnd;
        $context[] = 'e';

        $values[] = $this->emptyString;
      }

      elseif (is_array($data)) { // This allows for some more advanced datastructures; specifically, we use it here to define metadata that prevents $this->escape.
        if (isset($data['context'])) {
          throw new Exception('Deprecated context');
        }
        else {
          $columns[] = $this->columnQuoteStart . $this->escape($column) . $this->columnQuoteEnd;
        }


        if (!isset($data['type'])) {
          $data['type'] = 'string';
        }


        switch($data['type']) {
          case 'equation':
          $equation = preg_replace('/\$([a-zA-Z\_]+)/', '\\1', $data['value']);

          $values[] = $equation;
          break;

          case 'int':
          $values[] = (int) $data['value'];
          break;

          case 'string':
          default:
          $values[] = $this->stringQuoteStart . $this->escape($data['value']) . $this->stringQuoteEnd;
          break;
        }


        if (isset($data['cond'])) {
          $context[] = $data['cond'];
        }
        else {
          $context[] = 'e';
        }
      }

      else { // String or otherwise; encode it using mysql_escape and put it in quotes
        $columns[] = $this->columnQuoteStart . $this->escape($column) . $this->columnQuoteEnd;
        $context[] = 'e'; // Equals

        $values[] = $this->stringQuoteStart . $this->escape($data) . $this->stringQuoteEnd;
      }
    }

    return array($columns, $values, $context);
  }
  
  
  
  public function insert($table, $dataArray, $updateArray = false) {
    list($columns, $values) = $this->splitArray($dataArray);
    
    $columns = implode(',', $columns); // Convert the column array into to a string.
    $values = implode(',', $values); // Convert the data array into a string.

    $query = "INSERT INTO $table ($columns) VALUES ($values)";

    if ($updateArray) { // This is used for an ON DUPLICATE KEY request.
      list($columns, $values) = $this->splitArray($updateArray);

      for ($i = 0; $i < count($columns); $i++) {
        $update[] = $columns[$i] . ' = ' . $values[$i];
      }

      $update = implode($update, ', ');

      $query = "$query ON DUPLICATE KEY UPDATE $update";
    }

    if ($queryData = $this->rawQuery($query)) {
      $this->insertId = $this->functionMap('insertId');

      return $queryData;
    }
    else {
      return false;
    }
  }
  
  
  
  public function delete($table, $conditionArray = false) {
    if ($conditionArray === false) {
      $delete = 'TRUE';
    }
    else {
      list($columns, $values, $conditions) = $this->splitArray($conditionArray);

      for ($i = 0; $i < count($columns); $i++) {
        if (!$conditions[$i]) {
          $csym = $this->comparisonTypes['e'];
        }
        elseif (isset($this->comparisonTypes[$conditions[$i]])) {
          $csym = $this->comparisonTypes[$conditions[$i]];
        }
        else {
          throw new Exception('Unrecognised comparison type: ' . $conditions[$i]);
        }

        $delete[] = $columns[$i] . $csym . $values[$i];
      }

      $delete = implode($delete, $this->concatTypes['both']);
    }

    $query = "DELETE FROM $table WHERE $delete";

    return $this->rawQuery($query);
  }
  
  
  
  public function update($table, $dataArray, $conditionArray = false) {
    list($columns, $values) = $this->splitArray($dataArray);
    
    for ($i = 0; $i < count($columns); $i++) {
      $update[] = $columns[$i] . ' = ' . $values[$i];
    }

    $update = implode($update,', ');

    $query = "UPDATE {$table} SET {$update}";

    if ($conditionArray) {
      list($columns, $values, $conditions) = $this->splitArray($conditionArray);

      for ($i = 0; $i < count($columns); $i++) {
        if (!$conditions[$i]) $csym = $this->comparisonTypes['e'];
        elseif (isset($this->comparisonTypes[$conditions[$i]])) $csym = $this->comparisonTypes[$conditions[$i]];
        else throw new Exception('Unrecognised comparison type: ' . $conditions[$i]);

        $cond[] = $columns[$i] . $csym . $values[$i];
      }

      $query .= ' WHERE ' . implode($cond, $this->concatTypes['both']);
    }


    return $this->rawQuery($query);
  }
  
  /*********************************************************
  ************************* END ***************************
  ******************** Row Functions **********************
  *********************************************************/
  
}
?>