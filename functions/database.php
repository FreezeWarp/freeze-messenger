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


/**** BASIC POINTERS ****/
/* The select command is very messy. While it is now possible to specify short & sweet column and sort definitions, conditions use a horridly messy array syntax. It is not ideal, but will not be for now be rewritten. Hopefully some logic can at least be applie to the structure, however.
 * Complex joins, though originally possible in early development versions, are not and will not be possible now. This is because they are rarely neccessary, and can be considerably slower. Simple joins in which two tables are selected is possible, but some drivers do not support them (Google's language for their App Engine is a good example). These will then be interpreted by the DAL where neccessary.
 * Delete, Update, and Insert commands are fairly straight forward. They all use the same format, and shouldn't be too hard to get the hang of.
 */

class database {
  /**
   * Construct
   *
   * @return void
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function __construct() {
    $this->queryCounter = 0;
    $this->insertId = 0;
    $this->errorLevel = E_USER_ERROR;
    $this->activeDatabase = false;
    $this->dbLink = null;
  }

  public function setErrorLevel($errorLevel) {
    $this->errorLevel = $errorLevel;
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
      $this->tableColumnDivider = '.'; $this->stringQuoteStart = '"'; $this->stringQuoteEnd = '"';        $this->emptyString = '""';

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
      break;
    }
  }


  /**
   * Calls a database function, such as mysql_connect or mysql_query, using lookup tables
   *
   * @return void
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function functionMap($operation) {
    $args = func_get_args();

    switch ($this->language) {
      case 'mysql':
      switch ($operation) {
        case 'connect':
          $function = mysql_connect("$args[1]:$args[2]", $args[3], $args[4]);
          $this->version = mysql_get_server_info($function);

          return $function;
        break;

        case 'selectdb':
          $function = mysql_select_db($args[1], $this->dbLink);

          return $function;
        break;

        case 'error':
          if (isset($this->dbLink)) {
            return mysql_error($this->dbLink);
          }
          else {
            return mysql_error();
          }
        break;

        case 'close':
          $function = mysql_close($this->dbLink);

          unset($this->dbLink);

          return $function;
        break;

        case 'escape':
          return mysql_real_escape_string($args[1], $this->dbLink);
        break;

        case 'query':
          return mysql_query($args[1], $this->dbLink);
        break;

        case 'insertId':
          return mysql_insert_id($this->dbLink);
        break;

        default:
          throw new Exception('Unrecognised Operation: ' . $operation);
        break;
      }
      break;


      case 'mysqli':
      switch ($operation) {
        case 'connect':
          $function = mysqli_connect($args[1], $args[3], $args[4], ($args[5] ? $args[5] : null), (int) $args[2]);

          $this->version = mysqli_get_server_info($function);

          return $function;
        break;

        case 'selectdb':
          return mysqli_select_db($this->dbLink, $args[1]);
        break;

        case 'error':
          if (isset($this->dbLink)) {
            return mysqli_error($this->dbLink);
          }
          else {
            return mysqli_connect_error();
          }
        break;

        case 'close':
          return mysqli_close($this->dbLink);
        break;

        case 'escape':
          return mysqli_real_escape_string($this->dbLink, $args[1]);
        break;

        case 'query':
          return mysqli_query($this->dbLink, $args[1]);
        break;

        case 'insertId':
          return mysqli_insert_id($this->dbLink);
        break;

        default:
          throw new Exception('Unrecognised Operation: ' . $operation);
        break;
      }
      break;


      case 'postgresql':
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

        //case 'insertId': return mysqli_insert_id($this->dbLink); break;

        default:
          throw new Exception('Unrecognised Operation: ' . $operation);
        break;
      }
      break;
    }
  }


  /**
   * Connect to a database server.
   *
   * @param string $host - The host of the database server.
   * @param string $user - The database user
   * @param string $password - The password of the user.
   * @param string $this - The database to connect to.
   * @return bool - True if a connection was successfully established, false otherwise.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
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


  /**
   * Creates a new database on the database server. This function is not possible on all drivers (e.g. PostGreSQL).
   *
   * @param string $database - The name of the databse.
   * @return bool - True if the operation was successful, false otherwise.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function createDatabase($database) {
    switch ($this->language) {
      case 'mysql':
      case 'mysqli':
      case 'postgresql':
      return $this->rawQuery('CREATE DATABASE IF NOT EXISTS ' . $this->databaseQuoteStart . $database . $this->databaseQuoteEnd);
      break;
    }
  }


  /**
   * Alters the active database of the connection.  This function is not possible on all drivers (e.g. PostGreSQL). The connetion character set will also be set to UTF8 on certain drivers (e.g. MySQL).
   *
   * @param string $database - The name of the databse.
   * @return bool - True if the operation was successful, false otherwise.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function selectDatabase($database) {
    if (!$this->functionMap('selectdb', $database)) { // Select the database.
      $this->error = 'Could not select database: ' . $database;

      return false;
    }
    else {
      if ($this->language == 'mysql' || $this->language == 'mysqli') {
        if (!$this->rawQuery('SET NAMES "utf8"')) { // Sets the database encoding to utf8 (unicode).
          $this->error = 'Could not run SET NAMES query.';

          return false;
        }
      }

      $this->activeDatabase = $database;
      return true;
    }
  }


  /**
   * Closes a connection to a database server.
   *
   * @return void
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function close() {
    return $this->functionMap('close');
  }


  /**
   * Returns a string properly escaped for raw queries.
   *
   * @return string
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function escape($string) {
    return $this->functionMap('escape', $string); // Retrun the escaped string.
  }


  /**
   * Retrieves data from the active database connection.
   *
   * @param array columns - The columns to select.
   * @param array conditionArray - The conditions for the selection.
   * @param array|string sort - A string or array defining how to sort the return data.
   * @param int $limit - The maximum number of columns to select.
   *
   * @return object
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function select($columns, $conditionArray = false, $sort = false, $limit = false) { // Note: We will be removing group from here briefly.
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

    foreach ($conditionArray AS $type => $cond) {
      if (count($cond) > 0) {
        foreach ($cond AS $recKey => $data) {
          $i++;
          $sideTextFull[$i] = '';

         if (strpos($recKey, ' ') !== false) {
           $recKeyPieces = explode(' ', $recKey);
           $recKey = $recKeyPieces[0];
         }

          if ($recKey === 'both' || $recKey === 'either') {
            $sideTextFull[$i] = $this->recurseBothEither(array($recKey => $data), $reverseAlias, $d + 1);
          }
          elseif (array('type', 'left', 'right') === array_keys($data)) { // Should be an array containing keys 'type', 'left', and 'right', in that order for now.
            /* Get the Proper Comparison Operator */
            if (isset($this->comparisonTypes[$data['type']])) { $symbol = $this->comparisonTypes[$data['type']]; }
            else { throw new Exception('Unrecognised type operator "' . $data['type'] . '". Data: ' . print_r($conditionArray, true)); }


            /* Define Sides Array */
            $sideText = array('left', 'right');
            $hackz = array();


            /* Properly Format Left & Right Sides */
            foreach (array('left', 'right') AS $side) { // We do the same thing for the left and right indexes, so this reduces code redundancy. Not sure if it's good practice, though...
              if (is_array($data[$side])) {
                switch($data[$side]['type']) {
                  case 'string': // Strings should never be escaped before hand, otherwise values come out looking funny (and innacurate).
                  $sideText[$side] = '"' . $this->escape($data[$side]['value']) . '"';
                  break;

                  case 'int': // Best Practice Note: The value should __always__ be typed as an INTEGER (or possibly BOOL) anyway before sending it to the select method. Using "int" as the type really only means the database sees it as such, useful for databases that may in fact use such information (say, even, a spreadsheet).
                  $sideText[$side] = (int) $data[$side]['value'];
                  break;

                  case 'bool': // Best practice note: bool should only be used with values that are compared (e.g. pi = TRUE). Not that anything else is possible at the moment...
                  if (is_bool($data[$side]['value'])) { // We force bool here, since there is really no way of properly inferring what someone might have meant in a number of cases (e.g. "false", "0" - both common over HTTP).
                    $sideText[$side] = ($data[$side]['value'] ? 'TRUE' : 'FALSE');
                  }
                  else {
                    throw new Exception('Type mismatch ("bool")'); // Throw an exception.
                  }
                  break;

                  case 'array': // Used for IN clauses, mainly.
                  if (is_array($data[$side])) {
                    if (count($data[$side]['value']) > 0) {
                      foreach ($data[$side]['value'] AS &$entry) {
                        if (is_string($entry)) {
                          $entry = '\'' . $this->escape($entry) . '\'';
                        }
                      }

                      $sideText[$side] = "(" . implode(',', $data[$side]['value']) . ")";
                    }
                    else {
                      $sideText[$side] = '(NULL)';
                    }
                  }
                  else {
                    throw new Exception('Type mismatch ("array")'); // Throw an exception.
                  }
                  break;

                  case 'regexp': // Whatever Note: This will eventually be implemented server-side where not supported. That said, support is in PostgreSQL
                  $sideText[$side] = '"' . $data[$side]['value'] . '"';
                  break;

                  case 'equation': // This is a specific format useful for various conversions. More documentation needs to be created, but in a nutshell: $aaa = column aaa; ()+-*/ supported mathematical operators; use ',' for concatenation; use double quotes for strings.
                  // Valid equations: $aa + $bb; "b", $b,"c"
                  $equation = $data[$side]['value'];
                  $equation = preg_replace('/\$([a-zA-Z\_]+)/e','$reverseAlias[\'\\1\']', $equation);


                  $equationPieces = array();
                  if (strpos(',', $equation) !== false) { // If commas exist for concatenation...
                    foreach(explode(',', $equation) AS $piece) { // Run through each comma-seperated part of the equation (this is used for concatenation).
                      if (preg_match('/"([a-zA-Z0-9\*\?]+?)"/',trim($piece))) { // Yes, you can't use many things in strings. Nor should you.
                        $piece = str_replace(array('*','?'),array('%','_'),trim($piece)); // Replace glob pieces with SQL equivs
                      }
                      $equationPieces[] = trim($piece); // Spaces can get in the way, but this is likely redundant with the twim two lines up.
                    }

                    if (count($equationPieces) > 0) { // Only replace the equation if things worked.
                      $equation = 'CONCAT(' . implode(',', $equationPieces) . ')'; // Replace the equation and wrap concat for the commas (if concat used another symbol, we'd need to replace our implode accordingly).
                    }
                  }

                  $sideText[$side] = $equation;
                  break;

                  case 'glob':
                  $sideText[$side] = '"' . str_replace($this->globFindArray, $this->globReplaceArray, trim($data[$side]['value'])) . '"';
                  break;

                  case 'column':
                  if (isset($data[$side]['context'])) {
                    throw new Exception('Column context is deprecated.');
                  }
                  else {
                    if (isset($reverseAlias[$data[$side]['value']])) {
                      if ($symbol == 'IN' && $data['left']['type'] == 'int' && $data['right']['type'] == 'column') { // This is just a quick hack. It will be rewritten in the future; TODO
                        $sideText[$side] = $reverseAlias[$data[$side]['value']];

                        $hackz[($side == 'left' ? 'right' : 'left')] = '(' . $data[$side]['value'] . ',|' . $data[$side]['value'] . ')$';
                        $hackz['symbol'] = 'REGEXP';
                      }
                      else {
                        $sideText[$side] = $reverseAlias[$data[$side]['value']];
                      }
                    }
                    else {
                      throw new Exception('Unrecognised column: ' . $data[$side]['value']);
                    }
                  }
                  break;
                }
              }
              elseif (is_string($data[$side])) {
                // TODO, or something
              }
            }

            if (isset($hackz['left']))   $sideText['left'] = $hackz['left'];
            if (isset($hackz['right']))  $sideText['right'] = $hackz['right'];
            if (isset($hackz['symbol'])) $symbol = $hackz['symbol'];


            /* Generate Comparison Part */
            if ((strlen($sideText['left']) > 0) && (strlen($sideText['right']) > 0)) {
              $sideTextFull[$i] = "{$sideText['left']} {$symbol} {$sideText['right']}";
            }
            else {
              $sideTextFull[$i] = "FALSE"; // Instead of throwing an exception, which should be handled above, instead simply cancel the query in the cleanest way possible. Here, it's specifying "FALSE" in the where clause to prevent any results from being returned.
              throw new Exception('Query nullified.');
            }
          }
          else {
            throw new Exception('Malformed Query; Data: ' . print_r($data, true));
          }
        }


        if (isset($this->concatTypes[$type])) {
          $condSymbol = $this->concatTypes[$type];
        }
        else {
          throw new Exception('Unrecognised concatenation operator: ' . $type . '; ' . print_r($data, true));
        }


        $whereText[$h] = implode($condSymbol, $sideTextFull);
      }
    }


    // Combine the query array if multiple entries exist, or just get the first entry.
    if (count($whereText) === 0) return false;
    elseif (count($whereText) === 1) $whereText = $whereText[0]; // Get the query string from the first (and only) index.
    else $whereText = implode($this->concatTypes['both'], $whereText);


    return "($whereText)"; // Return condition string. We wrap parens around to support multiple levels of conditions/recursion.
  }


  /**
   * Inserts a row into a table of the database.
   *
   * @param string $table - The table to insert into.
   * @param array $dataArray - The data to insert into the database.
   * @param array $updateArray - If the row can not be inserted due to key restrictions, this defines data to update the row with instead (see MySQL's ON DUPLICATE KEY UPDATE).
   *
   * @return bool - True on success, false on failure.s
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
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


  /**
   * Inserts a row into a table of the database.
   *
   * @param string $table - The table to update.
   * @param array $dataArray - The data to update the row(s) with.
   * @param array $conditionArray - The conditions to apply to the UPDATE.
   *
   * @return bool - True on success, false on failure.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
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


  /**
   * Deletes rows from a table of the database.
   *
   * @param string $table - The table to delete from.
   * @param array $updateArray - The conditions to delete rows by.
   *
   * @return bool - True on success, false on failure.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
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


  /**
   * Sends a raw, unmodified query string to the database server.
   * The query may be logged if it takes a certain amount of time to execute successfully.
   *
   * @param string $query - The raw query to execute.
   * @return resource|bool - The database resource returned by the query, or false on failure.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  private function rawQuery($query) {
    $this->sourceQuery = $query;

    if ($queryData = $this->functionMap('query', $query)) {
      $this->queryCounter++;

      if ($queryData === true) return true;
      else return new databaseResult($queryData, $query, $this->language); // Return link resource.
    }
    else {
      $this->error = $this->functionMap('error');

      trigger_error("Database Error;\n\nQuery: $query;\n\nError: " . $this->error, $this->errorLevel); // The query could not complete.

      return false;
    }
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


  /**
   * Creates a table based on the specified data.
   *
   * @param string $tableName - The name of the table.
   * @param string $tableComment - A table comment, used for documentation and related purposes.
   * @param string $storeType - The class of database engine to use for the table, either "memory" or "general". The "memory" type should be used for tables that contain cache data, or otherwise where the table could be emptied without issue.
   * @param array $tableColumns - The columns of the table.
   * @param array $tableIndexes - The indexes of the table.
   *
   * @return bool - True on success, false on failure.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function createTable($tableName, $tableComment, $storeType, $tableColumns, $tableIndexes) {
    if (isset($this->tableTypes[$storeType])) {
      $engine = $this->tableTypes[$storeType];
    }
    else {
      throw new Exception('Unrecognised table engine: ' . $storeType);
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

        if ($column['autoincrement']) {
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
        throw new Exception('Unrecognised type.');
        break;
      }


      if ($column['default']) {
        $typePiece .= ' DEFAULT "' . $this->escape($column['default']) . '"';
      }


      $columns[] = $this->columnQuoteStart . $this->escape($column['name']) . $this->columnQuoteEnd . " {$typePiece} NOT NULL COMMENT \"" . $this->escape($column['comment']) . '"';
    }



    foreach ($tableIndexes AS $key) {
      if (isset($this->keyConstants[$key['type']])) {
        $typePiece = $this->keyConstants[$key['type']];
      }
      else {
        throw new Exception('Unrecognised key type: ' . $key['type']);
      }


      if (strpos($key['name'], ',') !== false) {
        $keyCols = explode(',', $key['name']);

        foreach ($keyCols AS &$keyCol) {
          $keyCol = $this->columnQuoteStart . $keyCol . $this->columnQuoteEnd;
        }

        $key['name'] = implode(',', $keyCols);
      }
      else {
        $key['name'] = $this->columnAliasStart . $key['name'] . $this->columnAliasEnd;
      }


      $keys[] = "{$typePiece} ({$key['name']})";
    }

    return $this->rawQuery('CREATE TABLE IF NOT EXISTS ' . $this->tableQuoteStart . $this->escape($tableName) . $this->tableQuoteEnd . ' (
' . implode(",\n  ",$columns) . ',
' . implode(",\n  ",$keys) . '
) ENGINE="' . $this->escape($engine) . '" COMMENT="' . $this->escape($tableComment) . '" DEFAULT CHARSET="utf8"' . $tableProperties);
  }


  /**
   * Renames/moves a table. It will remain in the active database.
   *
   * @param string $oldName - The current table name.
   * @param string $newName - The name the table should be renamed to.
   *
   * @return bool - True on success, false on failure.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function renameTable($oldName, $newName) {
    $query = 'RENAME TABLE ' . $this->tableQuoteStart . $this->escape($oldName) . $this->tableQuoteEnd . ' TO ' . $this->tableQuoteStart . $this->escape($newName) . $this->tableQuoteEnd;

    return $this->rawQuery($query);
  }


  /**
   * Deletes a table.
   *
   * @param string $tableName - The table to delete.
   *
   * @return bool - True on success, false on failure.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function deleteTable($tableName) {
    $query = 'DROP TABLE ' . $this->tableQuoteStart . $this->escape($tableName) . $this->tableQuoteEnd;

    return $this->rawQuery($query);
  }


  /**
   * Returns a compatible time field of the present time, as recognized by the DAL's interpretation of the database driver. In most cases, this will be a unix timestamp.
   *
   * @return mixed - The timefield corrosponding to the current time.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function now() {
    return time();
  }


  /**
   * Returns an array of the tables in a database. The method used is driver specific, but where possible the SQL-standard INFORMATION_SCHEMA database will be used.
   *
   * @return mixed - The timefield corrosponding to the current time.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
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

  public function __destruct() {
  	 if ($this->dbLink !== null) { // When close is called, the dbLink is nulled. This prevents redundancy.
      $this->close();
    }
  }
}


class databaseResult {
  /**
   * Construct
   *
   * @param object $queryData - The database object.
   * @param string $sourceQuery - The source query, which can be stored for referrence.
   * @return void
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function __construct($queryData, $sourceQuery, $language) {
    $this->queryData = $queryData;
    $this->sourceQuery = $sourceQuery;
    $this->language = $language;
  }


  /**
   * Calls a database function, such as mysql_connect or mysql_query, using lookup tables
   *
   * @return void
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function functionMap($operation) {
    $args = func_get_args();

    switch ($this->language) {
      case 'mysql':
      switch ($operation) {
        case 'fetchAsArray' : return (($data = mysql_fetch_assoc($args[1])) === false ? false : $data); break;
      }
      break;

      case 'mysqli':
      switch ($operation) {
        case 'fetchAsArray' : return (($data = mysqli_fetch_assoc($args[1])) === null ? false : $data); break;
      }
      break;
    }
  }


  /**
   * Replaces Query Data
   *
   * @param object $queryData - The database object.
   * @return void
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function setQuery($queryData) {
    $this->queryData = $queryData;
  }


  /**
   * Get Database Object as an Associative Array
   *
   * @param mixed $index
   * @return mixed
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function getAsArray($index = true, $group = false) {
    $data = array();
    $indexV = 0;

    if ($this->queryData !== false) {
      if ($index) { // An index is specified, generate & return a multidimensional array. (index => [key => value], index being the value of the index for the row, key being the column name, and value being the corrosponding value).
        while ($row = $this->functionMap('fetchAsArray', $this->queryData)) {
          if ($row === null || $row === false) break;

          if ($index === true) {
            $indexV++; // If the index is boolean "true", we simply create numbered rows to use. (1,2,3,4,5)

            $data[$indexV] = $row; // Append the data.
          }
          else { // If the index is not boolean "true", we instead get the column value of the index/column name.
            if ($group) $data[$row[$index]][] = $row; // Allow duplicate values.
            else $data[$row[$index]] = $row; // Overwrite values.
          }
        }

        return $data; // All rows fetched, return the data.
      }
      else { // No index is present, generate a two-dimensional array (key => value, key being the column name, value being the corrosponding value).
        return $this->functionMap('fetchAsArray', $this->queryData);
      }
    }

    else {
      return false; // Query data is false or null, return false.
    }
  }


  /**
   * Get the database object as a string, using the specified format/template. Each result will be passed to this template and stored in a string, which will be appended to the entire result.
   *
   * @param string $format
   * @return mixed
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function getAsTemplate($format) {
    static $data;

    if ($this->queryData !== false && $this->queryData !== null) {
      while (false !== ($row = $this->functionMap('fetchAsArray', $this->queryData))) { // Process through all rows.
        $uid++;
        $row['uid'] = $uid; // UID is a variable that can be used as the row number in the template.

        $data2 = preg_replace('/\$([a-zA-Z0-9]+)/e','$row[$1]', $format); // the "e" flag is a PHP-only extension that allows parsing of PHP code in the replacement.
        $data2 = preg_replace('/\{\{(.*?)\}\}\{\{(.*?)\}\{(.*?)\}\}/e','stripslashes(iif(\'$1\',\'$2\',\'$3\'))', $data2); // Slashes are appended automatically when using the /e flag, thus corrupting links.
        $data .= $data2;
      }

      return $data;
    }

    else {
      return false; // Query data is false or null, return false.
    }
  }
}
?>