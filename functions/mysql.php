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

/* mysqlConnect(string($host),string($name),string($password),string($database))
 * return = void */
function mysqlConnect($host,$user,$password,$database) {
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

function mysqlEscape($string) {
  return mysql_real_escape_string($string);
}

/* mysqlQuery(string($query))
 * return = MySQL Resource */
function mysqlQuery($query) {
  if ($queryData = mysql_query($query)) {
    return $queryData;
  }
  else {
    trigger_error("MySQL Error<br />Query: $query<br />Error: " . mysql_error(),E_USER_ERROR);
  }
}

/* mysqlArray(mysql_resource($queryData))
 * return = array */
function mysqlArray($queryData,$index = false) {
  if ($queryData !== false  && $queryData !== null) {
    if ($index) {
      while (false !== ($row = mysql_fetch_assoc($queryData))) {
        $data[$row[$index]] = $row;
      }
      return $data;
    }
    else {
      return mysql_fetch_assoc($queryData);
    }
  }
  return false;
}

function sqlArr($query,$index = false) {
  return mysqlArray(mysqlQuery($query),$index);
}

/* mysqlReadThrough(mysql_resource($queryData),string($function))
 * $function uses {{{x}}} to equal $row['x']
 * return = string */
function mysqlReadThrough($queryData,$function) {
  $queryData2 = $queryData;

  while (false != ($row = mysql_fetch_assoc($queryData2))) {
    $uid++;
    $row['uid'] = $uid;
    $data2 = preg_replace('/\$([a-zA-Z0-9]+)/e','$row[$1]',$function); // the "e" flag is a PHP-only extension that allows parsing of PHP code in the replacement.
    $data2 = preg_replace('/\{\{(.*?)\}\}\{\{(.*?)\}\{(.*?)\}\}/e','stripslashes(iif(\'$1\',\'$2\',\'$3\'))',$data2); // For some reason, slashes are appended automatically when using the /e flag, thus corrupting links.
    $data .= $data2;
  }
  return $data;
}

function mysqlInsertId() {
  return mysql_insert_id();
}

function iif($condition,$true,$false) {
  if (eval('return ' . stripslashes($condition) . ';')) {
    return $true;
  }
  return $false;
}

function mysqlClose() {
  mysql_close();
}

function modLog($action,$data) {
  global $sqlPrefix, $user;

  $action = mysqlEscape($action);
  $data = mysqlEscape($data);
  $ip = mysqlEscape($_SERVER['REMOTE_ADDR']);
  $userId = intval($user['userId']);

  if (mysqlQuery("INSERT INTO {$sqlPrefix}modlog (userId, ip, action, data) VALUES ($userId, '$ip', '$action', '$data')")) {
    return true;
  }
  else {
    return false;
  }
}
?>