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