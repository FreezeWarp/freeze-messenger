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
/* Select requires that you use the cast functions before passing values. Pretty simple, but important.
 * Delete, Update, and Insert commands are fairly straight forward. They all use the same format, and shouldn't be too hard to get the hang of.
 */
 
 /**** RANDOM RATIONALE ****/
 /* Though we could just keep the connect() etc. methods in databaseSQL.php/etc., having them as they are has some unique benefits, mainly: consistency (it just looks prettier to keep the core stuff in database as opposed to databaseSQL/etc.), "core" validation (we might, for instance, want to ensure the data passed is valid, a job better suited for the core functions), and others. In general, as a result, we want to understand the difference as follows:
  ** database should parse the structure of queries to the greatest extent possible before sending them to databaseSQL.
  ** databaseSQL should handle the routing and syntax of queries.
 * These are still a WIP, obviously, so they do not quite meet these conditions yet. */

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
    $functionName = 'connect' . $this->mode;
    
    return $this->$functionName($host, $port, $user, $password, $database, $driver);
  }


  
  /**
   * Creates a new database on the database server. This function is not possible on all drivers (e.g. PostGreSQL).
   *
   * @param string $database - The name of the databse.
   * @return bool - True if the operation was successful, false otherwise.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function createDatabase($database) {
    $functionName = 'createDatabase' . $this->mode;
    
    return $this->$functionName($table, $dataArray, $conditionArray);
  }


  
  /**
   * Alters the active database of the connection.  This function is not possible on all drivers (e.g. PostGreSQL). The connetion character set will also be set to UTF8 on certain drivers (e.g. MySQL).
   *
   * @param string $database - The name of the databse.
   * @return bool - True if the operation was successful, false otherwise.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function selectDatabase($database) {
    $functionName = 'createDatabase' . $this->mode;
    
    if ($this->$functionName($table, $dataArray, $conditionArray)) {  
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
   * Developer Note: This is only passed through functionMap(), and not an exended class's implementation of escape. If, for whatever reason, it needs to be overwritten, a class method of escape() will automatically replace it.
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
    $functionName = 'selct' . $this->mode;
    
    return $this->$functionName($columns, $conditionArray, $sort, $limit);
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
        
  /* Shorthand Mode
    *
    * Shorthand mode is, well, shorter. It's 10× simpler, and I may eventually deprecate the full mode in its favour. Right now, however, it simply doesn't support all operations. Eventually, I feel I will be able to make enough analogues that this will be different, though.
    * Anyway, here is a quick usage guide for how the select part (inside of a "both" or "or" array) should look:
    ** To compare a column to a value: 'columnName' => 'value' (value must be either integer or string, and will be escaped automatically)
    ** To compare a column to multiple values using an IN clause: 'columnName' => 'value' (value must be one-dimensional array, all entries of which should either be strings or integers, which will automatically be escaped.
    ** To compare a column to another column: 'columnName' => 'column columnName2' (columnName2 must be alphanumeric or it will not be accepted; additionally, above strings must not start with "column")
    *
    * Next, a few caveats:
    ** Everything is case sensitive. Live with it.
    ** 
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
             * array(TYPE, VALUE, COMPARISON) */

            $i++;
            $sideTextFull[$i] = '';

            $sideText['left'] = $reverseAlias[(startsWith($key, '!') ? $key : $key)]; // This is over-ridden for REGEX.
            $symbol = $this->comparisonTypes[$value[2]];
            
              

            switch ($value[0]) { // Switch the value type
              case 'int':
              if ($value[2] === 'search') {
                $sideText['right'] = ;
              }
              else {
              
              }
              break;
              
              case  'string':
              $sideText['right'] = $value;
              break;
              
              case 'column':
              
              break;
            }
            
            
            switch ($value[2]) { // Comparison
              case 'search':
              $value[
              break;
              
              default:
              
              break;
            }
              
            // Value is Array
            elseif (is_array($value)) {
              $sideText['left'] = $reverseAlias[$key];
              $sideText['right'] = implode(',', $value); // TODO: format for non-INTS/escape/etc.
              $symbol = $this->comparisonTypes['in'];
            }
            
            
            
            $sideTextFull[$i] = "{$sideText['left']} $symbol {$sideText['right']}";

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
    switch ($this->mode) {
      case 'SQL':
      return $this->stringQuoteStart . $this->stringFuzzy . $this->escape($value) . $this->stringFuzzy . $this->stringQuoteEnd;
      break;
    }
  }
  
  
  
  private function formatString($value) {
    switch ($this->mode) {
      case 'SQL':
      return $this->stringQuoteStart . $this->escape($value) . $this->stringQuoteEnd;
      break;
    }
  }
  
  
  
  /**
   * Designates a value to be an integer during SELECT operations.
   *
   * This function should _only_ be used for SELECT operations, and can be understood as being similar to PHP's native (int), (string), etc.
   *
   * @return special - int() returns a custom value that should only be read by the select() function.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function int($value, $comp = 'e') {
    return array('int', (int) $value, $comp);
  }
  
  
  
  /**
   * Designates a value to be a timestamp during SELECT operations.
   *
   * This function should _only_ be used for SELECT operations, and can be understood as being similar to PHP's native (int), (string), etc.
   *
   * @return special - ts() returns a custom value that should only be read by the select() function.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function ts($value, $comp = 'e') {
    return array('ts', (int) $value, $comp);
  }
  
  
  
  /**
   * Designates a value to be a string during SELECT operations.
   *
   * This function should _only_ be used for SELECT operations, and can be understood as being similar to PHP's native (int), (string), etc.
   *
   * @return special - str() returns a custom value that should only be read by the select() function.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */  
  public function str($value, $comp = 'e') {
    return array('str', $this->escape((string) $value), $comp);
  }
  
  
  
  /**
   * Designates a value to be a column referrence (alias the column must be referrenced as) during SELECT operations.
   *
   * This function should _only_ be used for SELECT operations, and can be understood as being similar to PHP's native (int), (string), etc.
   *
   * @return special - col() returns a custom value that should only be read by the select() function.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */  
  public function col($value, $comp = 'e') {
    return array('col', "`$value`", $comp);
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

    $functionName = 'update' . $this->mode;
    
    return $this->$functionName($table, $dataArray, $conditionArray);
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
    $functionName = 'delete' . $this->mode;
    
    return $this->$functionName($table, $conditionArray);
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
    
    $functionName = 'createTable' . $this->mode;
    
    return $this->$functionName($tableName);
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
    $functionName = 'deleteTable' . $this->mode;
    
    return $this->$functionName($oldName, $newName);
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
    $functionName = 'deleteTable' . $this->mode;
    
    return $this->$functionName($tableName);
  }


  
  /**
   * Returns an array of the tables in a database. The method used is driver specific, but where possible the SQL-standard INFORMATION_SCHEMA database will be used.
   *
   * @return mixed - The timefield corrosponding to the current time.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function getTablesAsArray() {
    $functionName = 'getTableAsArray' . $this->mode;
    
    return $this->$functionName();
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