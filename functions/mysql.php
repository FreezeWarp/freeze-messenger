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
* Performs a MySQL Query. TRANSITIONAL
*
* @param string $query - The raw query to execute.
* @return The object returned by the query.
* @author Joseph Todd Parsons
*/
function dbQuery($query) {
  $startTime = microtime(true);

  if ($queryData = mysql_query($query)) {
    return $queryData;
  }
  else {
    trigger_error("MySQL Error; Query: $query; Error: " . mysql_error(),E_USER_ERROR);
  }

  $endTime = microtime(true);

  if (($endTime - $startTime) > 2) {
    file_put_contents('query_log.txt',"Spent " . ($endTime - $startTime) . " on: $queryData",FILE_APPEND);
  }
}


/**
* Returns the insert ID of the last run query. TRANSITIONAL
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
* A function equvilent to an IF-statement that returns a true or false value. It is similar to the function in most spreadsheets (EXCEL, LibreOffice CALC, Lotus 123). TRANSITIONAL
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
* Closes a MySQL resource. TRANSITIONAL
*
* @param link - A resource created by dbConnect
* @return void - true on success, false on failure
* @author Joseph Todd Parsons
*/
function dbClose($link = false) {
  global $database, $integrationDatabase, $slaveDatabase;

  $database->close();
//  $integrationDatabase->close();
//  $slaveDatabase->close();
}


require_once('mysqlOOP.php');
?>