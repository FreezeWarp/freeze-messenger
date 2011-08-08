<?php
/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

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
/* This file is the MySQL-version (and the only one currently existing) of a generic database layer created for FreezeMessenger. What purpose could it possibly server? Why not go the PDO-route? Mainly, it offers a few distinct advantages: full control, easier to modify by plugins (specifically, in that most data is stored in a tree structure), and perhaps more importantly it allows things than PDO, which is fundamentally an SQL extension, doesn't. There is no shortage of database foundations bearing almost no semblance to SQL: IndexedDB (which has become popular by-way of web-browser implementation), Node.JS (which I would absolutely love to work with but currently can't because of the MySQL requirement), and others come to mind.
 * As with everything else, this is GPL-based, but if anyone decides they like it and may wish to use it for less restricted purposes, contact me. I have considered going LGPL/MIT/BSD with it, but not yet :P */

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
  }


  private function setLanguage($language) {
    $this->language = $language;

    switch ($this->language) {
      case 'mysql':
      case 'mysqli':
      $this->languageSubset = 'sql';
      $this->tableQuotes = '`';
      $this->tableAliasQuotes = '`';
      $this->columnQuotes = '`';
      $this->columnAliasQuotes = '`';
      $this->sortOrderAsc = 'ASC';
      $this->sortOrderDesc = 'DESC';
      break;

      case 'postgresql':
      $this->languageSubset = 'sql';
      $this->tableQuotes = '"';
      $this->tableAliasQuotes = '"';
      $this->columnQuotes = '"';
      $this->columnAliasQuotes = '"';
      $this->sortOrderAsc = 'ASC';
      $this->sortOrderDesc = 'DESC';
      break;
    }
  }


  /**
   * Calls a database function, such as mysql_connect or mysql_query, using lookup tables
   *
   * Native Argument Ref:
   * connect - host user password
   * selectdb - database
   * error - void
   *
   * Driver Argument Ref
   * mysql_connect - host user passwod
   * mysql_select_db - database
   * mysql_error - void
   */
  public function functionMap($operation) {
    $args = func_get_args();

    switch ($this->language) {
      case 'mysql':
      switch ($operation) {
        case 'connect': return mysql_connect("$args[1]:$args[2]", $args[3], $args[4]); break;
        case 'selectdb': return mysql_select_db($args[1], $this->dbLink); break;
        case 'error': return mysql_error($this->dbLink); break;
        case 'close': return mysql_close($this->dbLink); break;
        case 'escape': return mysql_real_escape_string($args[1], $this->dbLink); break;
        case 'query': return mysql_query($args[1], $this->dbLink); break;
        case 'insertId': return mysql_insert_id($this->dbLink); break;
        default: throw new Exception('Unrecognized Operation: ' . $operation); break;
      }
      break;

      case 'mysqli':
      switch ($operation) {
        case 'connect': return mysqli_connect($args[1], $args[3], $args[4], $args[5], $args[2]); break;
        case 'selectdb': return mysqli_select_db($this->dbLink, $args[1]); break;
        case 'error': return mysqli_error($this->dbLink); break;
        case 'close': return mysqli_close($this->dbLink); break;
        case 'escape': return mysqli_real_escape_string($this->dbLink, $args[1]); break;
        case 'query': return mysqli_query($this->dbLink, $args[1]); break;
        case 'insertId': return mysqli_insert_id($this->dbLink); break;
        default: throw new Exception('Unrecognized Operation: ' . $operation); break;
      }
      break;

      case 'postgresql':
      switch ($operation) {
        case 'connect': return pg_connect("host=$args[1] port=$args[2] username=$args[3] password=$args[4] dbname=$args[5]"); break;
        case 'error': return pg_last_error($this->dbLink); break;
        case 'close': return pg_close($this->dbLink); break;
        case 'escape': return pg_escape_string($this->dbLink, $args[1]); break;
        case 'query': return pg_query($this->dbLink, $args[1]); break;
        //case 'insertId': return mysqli_insert_id($this->dbLink); break;
        default: throw new Exception('Unrecognized Operation: ' . $operation); break;
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
   * @param string $database - The database to connect to.
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


    if (!$this->functionMap('selectdb', $database)) { // Select the database.
      $this->error = 'Could not select database: ' . $database;

      return false;
    }


    if ($this->language == 'mysql' || $this->language == 'mysqli') {
      if (!$this->rawQuery('SET NAMES "utf8"')) { // Sets the database encoding to utf8 (unicode).
        $this->error = 'Could not run SET NAMES query.';

        return false;
      }
    }


    return true;
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
   * @return string
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function escape($string) {
    return $this->functionMap('escape', $string); // Retrun the escaped string.
  }


  /**
   * Retrieves data from the active database connection.
   *
   * @return object
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function select($columns, $conditionArray = false, $sort = false, $group = false, $limit = false) {
    if ($group) {
      throw new Exception('Deprecated: group');
    }

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
          if (strlen($tableName) > 0) {
            if (strstr($tableName,' ') !== false) { // A space can be used to create a table alias, which is sometimes required for different queries.
              $tableParts = explode(' ', $tableName);

              $finalQuery['tables'][] = "`$tableParts[0]` AS `$tableParts[1]`";

              $tableName = $tableParts[1];
            }
            else {
              $finalQuery['tables'][] = "`$tableName`";
            }

            if (is_array($tableCols)) {
              foreach($tableCols AS $colName => $colAlias) {
                if (strlen($colName) > 0) {
                  if (strstr($colName,' ') !== false) { // A space can be used to create identical columns in different contexts, which is sometimes required for different queries.
                    $colParts = explode(' ', $colName);
                    $colName = $colParts[0];
                  }

                  if (is_array($colAlias)) { // Used for advance structures and function calls.
                    if (isset($colAlias['context'])) {
                      throw new Exception('Deprecated context.');
                    }

                    $finalQuery['columns'][] = "$colName AS `$colAlias[name]`";
                    $reverseAlias[$colAlias['name']] = $colName;
                  }

                  else {
                    $finalQuery['columns'][] = "`$tableName`.`$colName` AS `$colAlias`";
                    $reverseAlias[$colAlias] = "`$tableName`.`$colName`";
                  }
                }
                else {
                  throw new Exception('Invalid select array: column name empty'); // Throw an exception.
                }
              }
            }
            elseif (is_string($tableCols)) {
              $columnParts = explode(',',$tableCols); // Split the list into an array, delimited by commas

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

                $finalQuery['columns'][] = "`$tableName`.`$columnPartName` AS `$columnPartAlias`";
                $reverseAlias[$columnPartAlias] = "`$tableName`.`$columnPartName`";
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
                case 'asc':
                $directionSym = $this->sortOrderAsc;
                break;
                case 'desc':
                $directionSym = $this->sortOrderDesc;
                break;
                default:
                $directionSym = $this->sortOrderAsc;
                break;
              }
              $finalQuery['sort'][] = $reverseAlias[$sortCol] . " $directionSym";
            }
            else {
              throw new Exception('Unrecognized sort column: ' . $sortCol);
            }
          }

          $finalQuery['sort'] = implode(', ', $finalQuery['sort']);
        }
      }
      elseif (is_string($sort)) {
        $sortParts = explode(',',$sort); // Split the list into an array, delimited by commas

        foreach ($sortParts AS $sortPart) { // Run through each list item
          $sortPart = trim($sortPart); // Remove outside whitespace from the item

          if (strpos($sortPart,' ') !== false) { // If a space is within the part, then the part is formatted as "columnName direction"
            $sortPartParts = explode(' ',$sortPart); // Divide the piece

            $sortCol = $sortPartParts[0]; // Set the name equal to the first part of the piece

            switch (strtolower($sortPartParts[0])) {
              case 'asc':
              $directionSym = $this->sortOrderAsc;
              break;
              case 'desc':
              $directionSym = $this->sortOrderDesc;
              break;
              default:
              $directionSym = $this->sortOrderAsc;
              break;
            }
          }
          else { // Otherwise, we assume asscending
            $sortCol = $sortPart; // Set the name equal to the sort part.
            $directionSym = $this->sortOrderAsc; // Set the alias equal to the default, ascending.
          }

          $finalQuery['sort'][] = $reverseAlias[$sortCol] . " $directionSym";
        }
      }
    }


    /* Process Grouping (Must be Array)
     *
     * Technical/future note:
     * Group by will be simulated on database systems that do not implement it.
     * Allowed aggregate functions (see "context"): Join (group_concat), Sum (sum),
     * Product (simulated in MySQL), Average (avg), Minimum (min), Maximum (max), Count (count) */
    if ($group !== false) {
      $finalQuery['group'] = $reverseAlias[$group];
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

          if ($recKey === 'both' || $recKey === 'either') {
            $sideTextFull[$i] = $this->recurseBothEither(array($recKey => $data), $reverseAlias, $d+1);
          }
          else {
            /* Get the Proper Comparison Operator */
            $comparisonTypes = array(
              'e' => '=',
              'ne' => '!=',
              '!e' => '!=', // Alias of "ne"
              'lt' => '<',
              '!gte' => '>', // Alias of "lt"
              'gt' => '>',
              '!lte' => '>', // Alias of "gt"
              'lte' => '<=',
              '!gt' => '>=', // Alias of "lte"
              'gte' => '>=',
              '!lt' => '>=', // Alias of "gte"

              'and' => '&',
              '!xor' => '&', // Alias of "and"
              'xor' => '^',
              '!and' => '^', // Alias of "xorg"

              'bitwise' => '&', // DEPRECATED
              '!bitwise' => '^', // DEPRECATED

              'in' => 'IN',
              '!notin' => 'IN', // Alias of "in"
              'notin' => 'NOT IN',
              '!in' => 'NOT IN', // Alias of "notin"

              'regexp' => 'REGEXP', // Applies extended POSIX regular expression to index. It is natively implemented in MySQL, PostGreSQL, and Oracle SQL databases. It is absent in MSSQL, and the status in VoltDB and SQLite is unknown.
              'regex' => 'REGEXP', // Alias of "regexp"

              'glob' => 'LIKE',
              'like' => 'LIKE', // Alias of "glob"
            );

            if (isset($comparisonTypes[$data['type']])) {
              $symbol = $comparisonTypes[$data['type']];
            }
            else {
              throw new Exception('Unrecognized type operator "' . $data['type'] . '". Data: ' . print_r($data,true));
            }



            /* Define Sides Array */
            $sideText = array('left','right');
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

                  case 'column':
                  if (isset($data[$side]['context'])) {
                    switch ($data[$side]['context']) {
                      case 'time':
                      $sideText[$side] = 'UNIX_TIMESTAMP(' . $reverseAlias[$data[$side]['value']] . ')';
                      break;

                      default:
                      throw new Exception('Unrecognized column context'); // Throw an exception.
                      break;
                    }
                  }
                  else {
                    if (isset($reverseAlias[$data[$side]['value']])) {
                      if ($symbol == 'IN' && $data['left']['type'] == 'int' && $data['right']['type'] == 'column') { // This is just a quick hack. It will be rewritten in the future.
                        $sideText[$side] = $reverseAlias[$data[$side]['value']];

                        $hackz[($side == 'left' ? 'right' : 'left')] = '(' . $data[$side]['value'] . ',|' . $data[$side]['value'] . ')$';
                        $hackz['symbol'] = 'REGEXP';
                      }
                      else {
                        $sideText[$side] = $reverseAlias[$data[$side]['value']];
                      }
                    }
                    else {
                      throw new Exception('Unrecognized column: ' . $data[$side]['value']);
                    }
                  }
                  break;
                }
              }
              elseif (is_string($data[$side])) {
                // TODO, or something
              }
            }

            if (isset($hackz['left'])) {
              $sideText['left'] = $hackz['left'];
            }
            if (isset($hackz['right'])) {
              $sideText['right'] = $hackz['right'];
            }
            if (isset($hackz['symbol'])) {
              $symbol = $hackz['symbol'];
            }


            /* Generate Comparison Part */
            if ((strlen($sideText['left']) > 0) && (strlen($sideText['right']) > 0)) {
              $sideTextFull[$i] = "{$sideText['left']} {$symbol} {$sideText['right']}";
            }
            else {
              $sideTextFull[$i] = "FALSE"; // Instead of throwing an exception, which should be handled above, instead simply cancel the query in the cleanest way possible. Here, it's specifying "FALSE" in the where clause to prevent any results from being returned.
              trigger_error('Query nullified; backtrace: ' . print_r(debug_backtrace(), true), E_USER_WARNING);
            }
          }
        }


        $concatTypes = array(
          'both' => ' AND ',
          'either' => ' OR ',
        );

        if (isset($concatTypes[$type])) {
          $condSymbol = $concatTypes[$type];
        }
        else {
          throw new Exception('Unrecognized concatenation operator: ' . $type . '; d: ' . $d . '; ' . print_r($data,true));
        }


        $whereText[$h] = implode($condSymbol, $sideTextFull);
      }
    }


    // Combine the query array if multiple entries exist, or just get the first entry.
    if (count($whereText) === 0) {
      return false;
    }
    elseif (count($whereText) === 1) {
      $whereText = $whereText[0]; // Get the query string from the first (and only) index.
    }
    else {
      $whereText = implode(' AND ', $whereText);
    }


    return "($whereText)"; // Return condition string. We wrap parens around to support multiple levels of conditions/recursion.
  }


  public function insert($dataArray, $table, $updateArray = false) {
    list($columns, $values) = $this->splitArray($dataArray);

    $columns = implode(',', $columns); // Convert the column array into to a string.
    $values = implode(',', $values); // Convert the data array into a string.

    $query = "INSERT INTO $table ($columns) VALUES ($values)";

    if ($updateArray) { // This is used for an ON DUPLICATE KEY request.
      list($columns, $values) = $this->splitArray($updateArray);

      for ($i = 0; $i < count($columns); $i++) {
        $update[] = $columns[$i] . '=' . $values[$i];
      }

      $update = implode($update,',');

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


  public function update($dataArray, $table, $conditionArray = false) {
    list($columns, $values) = $this->splitArray($dataArray);

    for ($i = 0; $i < count($columns); $i++) {
      $update[] = $columns[$i] . ' = ' . $values[$i];
    }

    $update = implode($update,', ');

    $query = "UPDATE {$table} SET {$update}";

    if ($conditionArray) {
      list($columns, $values, $conditions) = $this->splitArray($conditionArray);

      for ($i = 0; $i < count($columns); $i++) {
        switch ($conditions[$i]) {
          case 'e':
          $csym = '=';
          break;

          case 'lt':
          $csym = '<';
          break;

          case 'gt':
          $csym = '>';
          break;

          case 'lte':
          $csym = '<=';
          break;

          case 'gte':
          $csym = '>=';
          break;

          default:
          $csym = '=';
          break;
        }

        $cond[] = $columns[$i] . $csym . $values[$i];
      }

      $query .= ' WHERE ' . implode($cond,' AND ');
    }


    return $this->rawQuery($query);

  }


  public function delete($table, $conditionArray = false) {
    list($columns, $values, $conditions) = $this->splitArray($conditionArray);

    for ($i = 0; $i < count($columns); $i++) {
      switch ($conditions[$i]) {
        case 'e':
        $csym = '=';
        break;

        case 'lt':
        $csym = '<';
        break;

        case 'gt':
        $csym = '>';
        break;

        case 'lte':
        $csym = '<=';
        break;

        case 'gte':
        $csym = '>=';
        break;

        default:
        $csym = '=';
        break;
      }

      $delete[] = $columns[$i] . $csym . $values[$i];
    }

    $delete = implode($delete,' AND ');

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
//    $startTime = microtime(true); // Get time in milliseconds (as a float) to determine if the query took too long.


    if ($queryData = $this->functionMap('query', $query)) {
//      $endTime = microtime(true); // Get time in milliseconds (as a float) to determine if the query took too long.

//      if (($endTime - $startTime) > 2) {
//        file_put_contents('query_log.txt',"Spent " . ($endTime - $startTime) . " on: $queryData",FILE_APPEND); // Log the query if it took over two seconds.
//      }

      $this->queryCounter++;

      return new databaseResult($queryData, $query, $this->language); // Return link resource.
    }
    else {
      trigger_error("Database Error;\n\nQuery: $query;\n\nError: " . $this->functionMap('error'),E_USER_ERROR); // The query could not complete.

      return false;
    }
  }


  /**
   * Divides a multidimensional array into three seperate two-dimensional arrays, and performs some additional processing as defined in the passed array.
   *
   * @param string $array - The source array
   * @return array - An array containing three seperate arrays.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  private function splitArray($array) {
    $columns = array(); // Initialize arrays
    $values = array(); // Initialize arrays

    foreach($array AS $column => $data) { // Run through each element of the $dataArray, adding escaped columns and values to the above arrays.

      if (is_int($data)) { // Safe integer - leave it as-is.
        $columns[] = $this->escape($column);
        $context[] = 'e'; // Equals

        $values[] = $data;
      }

      elseif (is_bool($data)) { // In MySQL, true evals to  1, false evals to 0.
        $columns[] = $this->escape($column);
        $context[] = 'e'; // Equals

        if ($data === true) {
          $values[] = 1;
        }
        elseif ($data === false) {
          $values[] = 0;
        }
      }

      elseif (is_null($data)) { // Null data, simply make it empty.
        $columns[] = $this->escape($column);
        $context[] = 'e';

        $values[] = '""';
      }

      elseif (is_array($data)) { // This allows for some more advanced datastructures; specifically, we use it here to define metadata that prevents $this->escape.
        if (isset($data['context'])) {
          switch($data['context']) {
            case 'time':
            $columns[] = 'UNIX_TIMESTAMP(' . $this->escape($column) . ')';
            break;

            default:
            $columns[] = $this->escape($column); // Maybe throw an exception instead?
            break;
          }
        }
        else {
          $columns[] = $this->escape($column);
        }


        if (!isset($data['type'])) {
          $data['type'] = 'string';
        }


        switch($data['type']) {
          case 'raw':
          throw new exception('Raw data type.');
          break;

          case 'equation':
          $equation = preg_replace('/\$([a-zA-Z\_]+)/', '\\1', $data['value']);

          $values[] = $equation;
          break;

          case 'int':
          $values[] = (int) $data['value'];
          break;

          case 'time':
          $values[] = "NOW()";
          break;

          case 'string':
          default:
          $values[] = '"' . $this->escape($data['value']) . '"';
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
        $columns[] = $this->escape($column);
        $context[] = 'e'; // Equals

        $values[] = '"' . $this->escape($data) . '"';
      }
    }

    return array($columns, $values, $context);
  }

  public function createTable($tableName, $storeType, $columns, $indexes) {

  }

  public function renameTable($oldName, $newName) {
    $query = "RENAME TABLE `$oldName` TO `$newName`";

    return $this->rawQuery($query);
  }

  public function deleteTable($tableName) {
    $query = "DELETE TABLE `$tableName`";

    return $this->rawQuery($query);
  }

  public function now() {
    return time();
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
  public function getAsArray($index = true) {
    $data = array();
    $indexV = 0;

    if ($this->queryData !== false) {
      if ($index) { // An index is specified, generate & return a multidimensional array. (index => [key => value], index being the value of the index for the row, key being the column name, and value being the corrosponding value).
        while ($row = $this->functionMap('fetchAsArray', $this->queryData)) {
          if ($row === null || $row === false) {
            break;
          }

          if ($index === true) { // If the index is boolean "true", we simply create numbered rows to use. (1,2,3,4,5)
            $indexV++;
          }
          else {
            $indexV = $row[$index]; // If the index is not boolean "true", we instead get the column value of the index/column name.
          }

          $data[$indexV] = $row; // Append the data.
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