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

/* These MySQL functions offer a way of changing the backend if needed, as well as simply making more common tasks easier.
 * For instance, conversion to PDO or MySQLi should be reasonably easy at this point. Or, changing from mysql_real_escape_string to mysql_escape_string could be done by changing just this one file.
 * Ultimately, it means extra security can be added if needed. Another example would be detecting for embedded DROP queries if data is not properly sanitized. */


/**
* Connect to a MySQL server.
*
* @param string $host - The host of the MySQL server.
* @param string $user - The MySQL user
* @param string $password - The password of the user.
* @param string $database - The database to connect to.
* @return void
* @author Joseph Todd Parsons
*/
function dbConnect($host,$user,$password,$database) {
  if (!mysql_connect($host,$user,$password)) {
    return false;
  }
  if (!mysql_select_db($database)) {
    return false;
  }

  // Sets the database to utf8 (unicode).
  if (!@mysql_query('SET NAMES "utf8"')) {
    return false;
  }
  return true;
}


/**
* Connect to a MySQL server.
*
* @param string $string - The data to escape.
* @return string - The escaped data.
* @author Joseph Todd Parsons
*/
function dbEscape($string) {
  return mysql_real_escape_string($string);
}


/**
* Performs a MySQL Query.
*
* @param string $query - The raw query to execute.
* @return The object returned by the query.
* @author Joseph Todd Parsons
*/
function dbQuery($query) {
  if ($queryData = mysql_query($query)) {
    return $queryData;
  }
  else {
    trigger_error("MySQL Error; Query: $query; Error: " . mysql_error(),E_USER_ERROR);
  }
}


/**
* Processes an array and returns two seperate arrays containing keys and values.
*
* @param string $host - The host of the MySQL server.
* @param string $user - The MySQL user
* @param string $password - The password of the user.
* @param string $database - The database to connect to.
* @return void
* @author Joseph Todd Parsons
*/
function mydbRowsay($queryData,$index = false) {
  global $queryCounter;

  $queryCounter++;

  if ($queryData !== false  && $queryData !== null) {
    if ($index) {
      while (false !== ($row = mysql_fetch_assoc($queryData))) {
        if ($index === true) {
          $indexV++;
        }
        else {
          $indexV = $row[$index];
        }

        $data[$indexV] = $row;
      }
      return $data;
    }
    else {
      return mysql_fetch_assoc($queryData);
    }
  }
  return false;
}


/**
* A shorthand function that runs a mysql query and returns an associative array of values; the key is an index and the value is a seperate associative array of a single MySQL row.
*
* @param string $query - The query to run.
* @param string $index - The column to base the key values on.
* @return void
* @author Joseph Todd Parsons
*/
function dbRows($query,$index = false) {
  return mydbRowsay(dbQuery($query),$index);
}


/**
* Processes a MySQL object and returns a formatted string based of function.
*
* @param string $queryData - An object returned by dbQuery
* @param string $format - The format each row will be processed uner.
* @return mixed - false if $queryData is false, otherwise the formatted string.
* @author Joseph Todd Parsons
*/
function dbReadThrough($queryData,$format) {
  $queryData2 = $queryData;

  if ($queryData) {
    while (false != ($row = mysql_fetch_assoc($queryData2))) {
      $uid++;
      $row['uid'] = $uid;
      $data2 = preg_replace('/\$([a-zA-Z0-9]+)/e','$row[$1]',$format); // the "e" flag is a PHP-only extension that allows parsing of PHP code in the replacement.
      $data2 = preg_replace('/\{\{(.*?)\}\}\{\{(.*?)\}\{(.*?)\}\}/e','stripslashes(iif(\'$1\',\'$2\',\'$3\'))',$data2); // For some reason, slashes are appended automatically when using the /e flag, thus corrupting links.
      $data .= $data2;
    }
    return $data;
  }
  else {
    return false;
  }
}


/**
* Processes an array and returns two seperate arrays containing keys and values.
*
* @param array $array - The array containing relevant key -> value pairs.
* @return array - An array containing two seperate arrays containing columns and values respectively.
* @author Joseph Todd Parsons
*/
function dbProcessArrayVal($array) {
  $columns = array(); // Initialize arrays
  $values = array(); // Initialize arrays

  foreach($array AS $column => $data) { // Run through each element of the $dataArray, adding escaped columns and values to the above arrays.

    if (is_int($data)) { // Safe integer - leave it as-is.
      $columns[] = dbEscape($column);
      $context[] = 'e';

      $values[] = $data;
    }

    elseif (is_bool($data)) { // In MySQL, true evals to  1, false evals to 0.
      $columns[] = dbEscape($column);
      $context[] = 'e';

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
      $context[] = 'e';

      $values[] = '"' . dbEscape($data) . '"';
    }
  }

  return array($columns, $values, $context);
}


/**
* Inserts data based on key->value pairs, and if needed adds ON DUPLICATE KEY statement.
*
* @param array $dataArray - The array containing relevant key -> value pairs.
* @param string $table - The table to insert into.
* @param array $updateArray - The conditions for ON DUPLICATE KEY UPDATE.
* @return bool - true on success, false on failure
* @author Joseph Todd Parsons
*/
function dbInsert($dataArray,$table,$updateArray) {

  list($columns,$values) = dbProcessArrayVal($dataArray);

  $columns = implode(',',$columns); // Convert the column array into to a string.
  $values = implode(',',$values); // Convert the data array into a string.

  $query = "INSERT INTO $table ($columns) VALUES ($values)";

  if ($updateArray) { // This is used for an ON DUPLICATE KEY request.
    list($columns, $values) = dbProcessArrayVal($updateArray);

    for ($i = 0; $i < count($columns); $i++) {
      $update[] = $columns[$i] . '=' . $values[$i];
    }

    $update = implode($update,',');

    $query = "$query ON DUPLICATE KEY UPDATE $update";
  }

  return dbQuery($query);
}


function dbUpdate($dataArray,$table,$conditionArray = false) {
  list($columns, $values) = dbProcessArrayVal($dataArray);

  for ($i = 0; $i < count($columns); $i++) {
    $update[] = $columns[$i] . ' = ' . $values[$i];
  }

  $update = implode($update,', ');

  $query = "UPDATE {$table} SET {$update}";

  if ($conditionArray) {
    list($columns,$values,$conditions) = dbProcessArrayVal($conditionArray);

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


  return dbQuery($query);

}


function dbDelete($table,$conditionArray = false) {
  list($columns,$values,$conditions) = dbProcessArrayVal($conditionArray);

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
* Returns the insert ID of the last run query.
*
* @return link - A resource created by dbQuery
* @author Joseph Todd Parsons
*/
function dbInsertId($link = false) {
  if ($link) {
    return mysql_insert_id($link);
  }
  else {
    return mysql_insert_id();
  }
}


/**
* A function equvilent to an IF-statement that returns a true or false value. It is similar to the function in most spreadsheets (EXCEL, LibreOffice CALC, Lotus 123), except th
*
* @param string $condition - The condition that will be evaluated. It must be a string.
* @param string $true - A string to return if the above condition evals to true.
* @param string $false - A string to return if the above condition evals to false.
* @return bool - true on success, false on failure
* @author Joseph Todd Parsons
*/
function iif($condition,$true,$false) {
  if (eval('return ' . stripslashes($condition) . ';')) {
    return $true;
  }
  return $false;
}


/**
* Closes a MySQL resource.
*
* @param link - A resource created by dbConnect
* @return void - true on success, false on failure
* @author Joseph Todd Parsons
*/
function dbClose($link = false) {
  if ($link) {
    mysql_close();
  }
  else {
    mysql_close($link);
 }
}
?>