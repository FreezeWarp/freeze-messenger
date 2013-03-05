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
/* This file is the outline of FreezeMessenger's database class. The legwork will be performed by another class (usually databaseSQL), while this defines variables and includes documentation for the interface.
 */


/**** BASIC POINTERS ****/
/* Select requires that you use the cast functions before passing values. Pretty simple, but important.
 * Delete, Update, and Insert commands are fairly straight forward. They all use the same format, and shouldn't be too hard to get the hang of.
 */

abstract class database {
  

  /*********************************************************
  ************************ START **************************
  ******************* General Functions *******************
  *********************************************************/
  
  public $queryCounter = 0;
  public $insertId = null;
  public $error = false;
  protected $errorLevel = E_USER_ERROR;
  protected $activeDatabase = false;
  protected $dbLink = null;
  
  
  
  /**
   * Construct
   * Pipes in connect().
   *
   * @return void
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function __construct($host, $port, $user, $password, $database, $driver) {
    $this->connect($host, $port, $user, $password, $database, $driver);
  }
  

  
  /**
   * Construct
   *
   * @return void
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */ 
  public function __destruct() {
    if ($this->dbLink !== null) { // When close is called, the dbLink is nulled. This prevents redundancy.
      $this->close();
    }
  }
  
  
  
  /**
   * Connect to a database server.
   *
   * @param string $host - The host of the database server.
   * @param int port - The port the server is located on, if applicable. (Note: Some drivers, like MySQL, normally include the port in the host. For this abstraction, please seperate the host and the port, including them in $host and $port respectively.)
   * @param string $user - The database user
   * @param string $password - The password of the user.
   * @param string $database - The database to connect to. (Note: This can be selected using selectDatabase(), but for most purposes it should be specified now.)
   * @param string $driver - The driver that will power the abstraction. At present, only "mysql" and "mysqli" are supported.
   *
   * @return bool - True if a connection was successfully established, false otherwise.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  abstract public function connect($host, $port, $user, $password, $database, $driver);
  


  /**
   * Closes a connection to a database server.
   *
   * @return void
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  abstract public function close();

  
  
  /**
   * Returns a string properly escaped for raw queries.
   * Developer Note: This is only passed through functionMap(), and not an exended class's implementation of escape. If, for whatever reason, it needs to be overwritten, a class method of escape() will automatically replace it.
   *
   * @return string
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  abstract public function escape($string, $context = 'string');

  

  /**
   * Defines what error level should be used for all database errors called by the class. Class exceptions will not be affected.
   *
   * @param int level - PHP error level to use for all errors called by the class.
   * @return string - New error level.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function setErrorLevel($errorLevel) {
    return $this->errorLevel = $errorLevel;
  }
  
  
  
  /**
   * Get the Current Error Level
   *
   * @return string - Current error level.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  public function getErrorLevel() {
    return $this->errorLevel;
  }
  
  
  
  /**
   * Set the Error Level for Display
   *
   * @return string - New error level.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  protected function triggerError($errorMessage) {
    trigger_error($errorMessage, $this->errorLevel);
  }
  
  /*********************************************************
  ************************* END ***************************
  ******************* General Functions *******************
  *********************************************************/

  

  /*********************************************************
  ************************ START **************************
  ****************** Database Functions *******************
  *********************************************************/
  
  /**
   * Creates a new database on the database server. This function is not possible on all drivers (e.g. PostGreSQL).
   *
   * @param string $database - The name of the databse.
   * @return bool - True if the operation was successful, false otherwise.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  abstract public function createDatabase($database);


  
  /**
   * Alters the active database of the connection.  This function is not possible on all drivers (e.g. PostGreSQL). The connetion character set will also be set to UTF8 on certain drivers (e.g. MySQL).
   *
   * @param string $database - The name of the databse.
   * @return bool - True if the operation was successful, false otherwise.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  abstract public function selectDatabase($database);
  
  /*********************************************************
  ************************* END ***************************
  ****************** Database Functions *******************
  *********************************************************/



  
  /*********************************************************
  ************************ START **************************
  ******************* Table Functions *********************
  *********************************************************/
  
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
  abstract public function createTable($tableName, $tableComment, $storeType, $tableColumns, $tableIndexes);

  

  /**
   * Renames/moves a table. It will remain in the active database.
   *
   * @param string $oldName - The current table name.
   * @param string $newName - The name the table should be renamed to.
   *
   * @return bool - True on success, false on failure.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  abstract public function renameTable($oldName, $newName);


  
  /**
   * Deletes a table.
   *
   * @param string $tableName - The table to delete.
   *
   * @return bool - True on success, false on failure.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  abstract public function deleteTable($tableName);


  
  /**
   * Returns an array of the tables in a database. The method used is driver specific, but where possible the SQL-standard INFORMATION_SCHEMA database will be used.
   *
   * @return mixed - The timefield corrosponding to the current time.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  abstract public function getTablesAsArray();
  
  /*********************************************************
  ************************* END ***************************
  ******************* Table Functions *********************
  *********************************************************/

  
  
  /*********************************************************
  ************************ START **************************
  ******************** Row Functions **********************
  *********************************************************/

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
   
   /* Formatting:
    *
    * To compare a column to a value: 'columnName' => $dB->str('value') (value must be either integer or string, and will be escaped automatically)
    * To compare a column to multiple values using an IN clause: 'columnName' => 'value' (value must be one-dimensional array, all entries of which should either be strings or integers, which will automatically be escaped.
    * To compare a column to another column: 'columnName' => 'column columnName2' (columnName2 must be alphanumeric or it will not be accepted; additionally, above strings must not start with "column")
    *
    * Next, a few caveats:
    ** Everything is case sensitive. Live with it.
  */

  abstract public function select($columns, $conditionArray = false, $sort = false, $limit = false);

  
  
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
  abstract public function insert($table, $dataArray, $updateArray = false);


  
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
  abstract public function update($table, $dataArray, $conditionArray = false );


  
  /**
   * Deletes rows from a table of the database.
   *
   * @param string $table - The table to delete from.
   * @param array $updateArray - The conditions to delete rows by.
   *
   * @return bool - True on success, false on failure.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  abstract public function delete($table, $conditionArray = false);
  
  /*********************************************************
  ************************* END ***************************
  ******************** Row Functions **********************
  *********************************************************/


  
  
  /*********************************************************
  ************************ START **************************
  **************** Type-Casting Functions *****************
  *********************************************************/
  
  /**
   * Define a value as being an integer for database operations.
   *
   * @param mixed $value - The value to cast.
   * @param mixed $comp - How the value will be compared to the data present as an index.
   *
   * @return special - Returns a special representation of a column int only for use in database functions.
   *
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function int($value, $comp = 'e') {
    return array('int', (int) $value, $comp);
  }
  
  
  
  /**
   * Define a value as being a timestamp for database operations.
   *
   * @param mixed $value - The value to cast.
   * @param mixed $comp - How the value will be compared to the data present as an index.
   *
   * @return special - Returns a special representation of a timestamp value only for use in database functions.
   *
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function ts($value, $comp = 'e') {
    return array('ts', (int) $value, $comp);
  }
  
  
  
  /**
   * Define a value as being an string for database operations.
   *
   * @param mixed $value - The value to cast.
   * @param mixed $comp - How the value will be compared to the data present as an index.
   *
   * @return special - Returns a special representation of a string value only for use in database functions.
   *
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function str($value, $comp = 'e') {
    return array('str', (string) $value, $comp);
  }
  
  
  
  /**
   * Define a value as being a column for database operations.
   *
   * @param mixed $value - The value to cast.
   * @param mixed $comp - How the value will be compared to the data present as an index.
   *
   * @return special - Returns a special representation of a column value only for use in database functions.
   *
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function col($value, $comp = 'e') {
    return array('col', (string) $value, $comp);
  }
  
  /*********************************************************
  ************************* END ***************************
  **************** Type-Casting Functions *****************
  *********************************************************/
}
?>