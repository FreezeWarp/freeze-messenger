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
   * @author Joseph Todd Parsons
  */
  public function __construct() {
    $this->queryCounter = 0;
  }


  /**
   * Connect to a database server.
   *
   * @param string $host - The host of the database server.
   * @param string $user - The database user
   * @param string $password - The password of the user.
   * @param string $database - The database to connect to.
   * @return bool - True if a connection was successfully established, false otherwise.
   * @author Joseph Todd Parsons
  */
  public function connect($host,$user,$password,$database) {
    if (!$link = mysql_connect($host,$user,$password)) { // Make the connection.
      return false;
    }
    else {
      $this->dbLink = $link; // Set the object property "dbLink" to the database connection resource. It will be used with most other queries that can accept this parameter.
    }


    if (!mysql_select_db($database,$this->dbLink)) { // Select the database.
      return false;
    }


    if (!@mysql_query('SET NAMES "utf8"',$this->dbLink)) { // Sets the database encoding to utf8 (unicode).
      return false;
    }


    return true;
  }


  /**
   * Closes a connection to a database server.
   *
   * @return void
   * @author Joseph Todd Parsons
   */
  public function close() {
    mysql_close($this->dbLink); // Close the database link.
  }


  /**
   * Returns a string properly escaped for raw queries.
   * @return string
   * @author Joseph Todd Parsons
   */
  public function escape($string) {
    return mysql_real_escape_string($string,$this->dbLink); // Retrun the escaped string.
  }


  /**
   * Retrieves data from the active database connection.
   *
   * @return object
   * @author Joseph Todd Parsons
   */
  public function select($columns,$conditionArray = false,$sort = false,$group = false,$limit = false) {
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
          if (strstr($tableName,' ') !== false) { // A space can be used to create a table alias, which is sometimes required for different queries.
            $tableParts = explode(' ',$tableName);

            $finalQuery['tables'][] = "`$tableParts[0]` AS $tableParts[1]";

            $tableName = $tableParts[1];
          }
          else {
            $finalQuery['tables'][] = "`$tableName`";
          }

          foreach($tableCols AS $colName => $colAlias) {
            if (is_array($colAlias)) { // Used for advance structures and function calls.
              if (isset($colAlias['context'])) {
                switch($colAlias['context']) {
                  case 'time':
                  $colName = "UNIX_TIMESTAMP(`$tableName`.`$colName`)";
                  break;
                  case 'join':
                  $colName = "GROUP_CONCAT(`$tableName`.`$colName` SEPARATOR $colAlias[separator])";
                  break;
                }
              }

              $finalQuery['columns'][] = "$colName AS $colAlias[name]";
              $reverseAlias[$colAlias['name']] = $colName;
            }

            else {
              $finalQuery['columns'][] = "`$tableName`.`$colName` AS $colAlias";
              $reverseAlias[$colAlias] = "`$tableName`.`$colName`";
            }
          }
        }
      }
      else {
        throw new Exception('Invalid array'); // Throw an exception.
      }
    }
    else {
      throw new Exception('Invalid array'); // Throw an exception.
    }


    /* Process Conditions (Must be Array) */
    if ($conditionArray !== false) {
      if (is_array($conditionArray)) {
        if (count($conditionArray) > 0) {
          $finalQuery['where'] = $this->recurseBothEither($conditionArray,$reverseAlias);
        }
      }
    }


    /* Process Sorting (Must be Array) */
    if ($sort !== false) {
      if (is_array($sort)) {
        if (count($sort) > 0) {
          foreach ($sort AS $sortCol => $dir) {
            switch (strtolower($dir)) {
              case 'asc':
              $dirSym = 'ASC';
              break;
              case 'desc':
              $dirSym = 'DESC';
              break;
              default:
              $dirSym = 'ASC';
              break;
            }
            $finalQuery['sort'][] = "$sortCol $dirSym";
          }

          $finalQuery['sort'] = implode(', ',$finalQuery['sort']);
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
      if (is_array($group)) {
        if (count($group) > 0) {
          $finalQuery['group'] = $reverseAlias[$group];
        }
      }
    }


    /* Process Limit (Must be Integer) */
    if ($limit !== false) {
      if (is_int($limit)) {
        $finalQuery['group'] = (int) $limit;
      }
    }


    /* Run Generated Query */
    $finalQueryText = 'SELECT
  ' . implode(', ',$finalQuery['columns']) . '
FROM
  ' . implode(', ',$finalQuery['tables']) . ($finalQuery['where'] ? '
WHERE
  ' . $finalQuery['where'] : '') . ($finalQuery['sort'] ? '
ORDER BY
  ' . $finalQuery['sort'] : '') . ($finalQuery['group'] ? '
GROUP BY
  ' . $finalQuery['group'] : '');

    return $this->rawQuery($finalQueryText);
  }

  /**
   * Recurses over a specified "where" array, returning a valid where clause.
   *
   * @return string
   * @author Joseph Todd Parsons
   */
  private function recurseBothEither($conditionArray,$reverseAlias) {
    $i = 0;
    $h = 0;

    foreach ($conditionArray AS $type => $cond) {

      foreach ($cond AS $recKey => $data) {
        $i++;
        $sideTextFull[$i] = '';

        if ($recKey === 'both' || $recKey === 'either') {
          $sideTextFull[$i] = $this->recurseBothEither($data,$reverseAlias);
        }
        else {
          /* Define Sides Array */
          $sideText = array('left','right');


          /* Properly Format Left & Right Sides */
          foreach (array('left', 'right') AS $side) { // We do the same thing for the left and right indexes, so this reduces code redundancy. Not sure if it's good practice, though...
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
                if (count($data[$side]) > 0) {
                  $sideText[$side] = "(" . implode(',',$data[$side]['value']) . ")";
                }
              }
              else {
                throw new Exception('Type mismatch ("array")'); // Throw an exception.
              }
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
                $sideText[$side] = $reverseAlias[$data[$side]['value']];
              }
              break;
            }
          }


          /* Get the Proper Comparison Operator
           * TODO: Move to array. */
          switch ($data['type']) {
            case 'e': $symbol = '='; break;
            case 'ne': $symbol = '!='; break;
            case 'lt': $symbol = '<'; break;
            case 'gt': $symbol = '>'; break;
            case 'lte': $symbol = '<='; break;
            case 'gte': $symbol = '>='; break;
            case 'in': $symbol = 'IN'; break;
            case 'bitwise': $symbol = '&'; break;
          }


          /* Generate Comparison Part */
          if ((strlen($sideText['left']) > 0) && (strlen($sideText['right']) > 0)) {
            $sideTextFull[$i] = "{$sideText['left']} {$symbol} {$sideText['right']}";
          }
          else {
            $sideTextFull[$i] = "FALSE"; // Instead of throwing an exception, which should be handled above, instead simply cancel the query in the cleanest way possible. Here, it's specifying "FALSE" in the where clause to prevent any results from being returned.
          }
        }
      }

      switch($type) {
        case 'both': $condSymbol = ' AND '; break;
        case 'either': $condSymbol = ' OR '; break;
        default: $condSymbol = ' AND '; break; // We may wish to throw an exception instead.
      }

      $whereText[$h] = implode($condSymbol,$sideTextFull);
    }


    if (count($whereText) == 1) {
      foreach ($whereText AS $data) { // Yeah, no idea what I did here either...
        $whereText = $data;

        break;
      }
    }
    else {
      $whereText = implode(' AND ',$whereText);
    }


    return "($whereText)";
  }


  public function insert($dataArray,$table,$updateArray) {
    list($columns, $values) = $this->splitArray($dataArray);

    $columns = implode(',',$columns); // Convert the column array into to a string.
    $values = implode(',',$values); // Convert the data array into a string.

    $query = "INSERT INTO $table ($columns) VALUES ($values)";

    if ($updateArray) { // This is used for an ON DUPLICATE KEY request.
      list($columns, $values) = $this->splitArray($updateArray);

      for ($i = 0; $i < count($columns); $i++) {
        $update[] = $columns[$i] . '=' . $values[$i];
      }

      $update = implode($update,',');

      $query = "$query ON DUPLICATE KEY UPDATE $update";
    }

    return $this->rawQuery($query);
  }


  public function update($dataArray,$table,$conditionArray = false) {
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


  public function delete($table,$conditionArray = false) {
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

    return dbQuery($query);
  }


  /**
   * Sends a raw, unmodified query string to the database server.
   * The query may be logged if it takes a certain amount of time to execute successfully.
   *
   * @param string $query - The raw query to execute.
   * @return resource|bool - The database resource returned by the query, or false on failure.
   * @author Joseph Todd Parsons
  */
  private function rawQuery($query) {
    $startTime = microtime(true); // Get time in milliseconds (as a float) to determine if the query took too long.


    if ($queryData = mysql_query($query,$this->dbLink)) {
      $endTime = microtime(true); // Get time in milliseconds (as a float) to determine if the query took too long.

      if (($endTime - $startTime) > 2) {
        file_put_contents('query_log.txt',"Spent " . ($endTime - $startTime) . " on: $queryData",FILE_APPEND); // Log the query if it took over two seconds.
      }

      $this->queryCounter++;


      return new databaseResult($queryData); // Return link resource.
    }
    else {
      trigger_error("MySQL Error; Query: $query; Error: " . mysql_error($this->dbLink),E_USER_ERROR); // The query could not complete.


      return false;
    }
  }


  private function splitArray($array) {
    $columns = array(); // Initialize arrays
    $values = array(); // Initialize arrays

    foreach($array AS $column => $data) { // Run through each element of the $dataArray, adding escaped columns and values to the above arrays.

      if (is_int($data)) { // Safe integer - leave it as-is.
        $columns[] = dbEscape($column);
        $context[] = 'e'; // Equals

        $values[] = $data;
      }

      elseif (is_bool($data)) { // In MySQL, true evals to  1, false evals to 0.
        $columns[] = dbEscape($column);
        $context[] = 'e'; // Equals

        if ($data === true) {
          $values[] = 1;
        }
        elseif ($data === false) {
          $values[] = 0;
        }
      }

      elseif (is_null($data)) { // Null data, simply make it empty.
        $columns[] = dbEscape($column);
        $context[] = 'e';

        $values[] = '""';
      }

      elseif (is_array($data)) { // This allows for some more advanced datastructures; specifically, we use it here to define metadata that prevents dbEscape.
        switch($data['context']) {
          case 'time':
          $columns[] = 'UNIX_TIMESTAMP(' . dbEscape($column) . ')';
          break;

          default:
          $columns[] = dbEscape($column);
          break;
        }

        switch($data['type']) {
          case 'raw':
          $values[] = $data['value'];
          break;

          default:
          $values[] = '"' . dbEscape($data['value']) . '"';
          break;
        }

        $context[] = $data['cond'];
      }

      else { // String or otherwise; encode it using mysql_escape and put it in quotes
        $columns[] = dbEscape($column);
        $context[] = 'e'; // Equals

        $values[] = '"' . dbEscape($data) . '"';
      }
    }

    return array($columns, $values, $context);
  }
}

class databaseResult {
  public function __construct($queryData) {
    $this->queryData = $queryData;
  }

  public function setQuery($queryData) {
    $this->queryData = $queryData;
  }

  public function getAsArray($index = true) {
    $data = array();

    if ($this->queryData !== false) {
      if ($index) { // An index is specified, generate & return a multidimensional array. (index => [key => value], index being the value of the index for the row, key being the column name, and value being the corrosponding value).
        while (false !== ($row = mysql_fetch_assoc($this->queryData))) {
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
        return mysql_fetch_assoc($this->queryData);
      }
    }

    else {
      return false; // Query data is false or null, return false.
    }
  }

  public function getAsTemplate($format) {
    static $data;

    if ($this->queryData !== false && $this->queryData !== null) {
      while (false !== ($row = mysql_fetch_assoc($this->queryData))) { // Process through all rows.
        $uid++;
        $row['uid'] = $uid; // UID is a variable that can be used as the row number in the template.

        $data2 = preg_replace('/\$([a-zA-Z0-9]+)/e','$row[$1]',$format); // the "e" flag is a PHP-only extension that allows parsing of PHP code in the replacement.
        $data2 = preg_replace('/\{\{(.*?)\}\}\{\{(.*?)\}\{(.*?)\}\}/e','stripslashes(iif(\'$1\',\'$2\',\'$3\'))',$data2); // Slashes are appended automatically when using the /e flag, thus corrupting links.
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