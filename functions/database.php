<?php
/* FreezeMessenger Copyright c 2014 Joseph Todd Parsons

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
/* This file is the outline of FreezeMessenger's database class. The legwork will be performed by another class (usually databaseSQL), while this defines variables and includes documentation for the interface. */


/**** TODO ****/
/* "float" support
 * triggers (e.g. a watch() function)
 */

abstract class database
{

    public $classVersion = 3;
    public $classProduct = 'fim';

    public $queryCounter = 0;
    public $insertId = null;
    public $errors = array();
    public $printErrors = false;
    public $getTablesEnabled = false;
    public $errorFormatFunction = '';
    public $storeTypes;

    protected $errorLevel = E_USER_ERROR;
    protected $activeDatabase = false;
    protected $dbLink = null;
    public $connectionInformation = array();

    protected $conditionArray = array();
    protected $sortArray = array();
    protected $limitArray = array();

    public $sqlPrefix;


    /*********************************************************
     ************************ START **************************
     ******************* General Functions *******************
     *********************************************************/

    /**
     * Construct
     * Pipes in connect().
     *
     * @param mixed $host
     * @param mixed $port
     * @param mixed $user
     * @param mixed $password
     * @param mixed $database
     * @param mixed $driver
     * @param mixed $tablePrefix
     * @return \database
     *
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function __construct($host = false, $port = false, $user = false, $password = false, $database = false, $driver = false, $tablePrefix = '')
    {
        if ($host !== false) $this->connect($host, $port, $user, $password, $database, $driver, $tablePrefix);
    }


    /**
     * Construct
     *
     * @return void
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function __destruct()
    {

    }

    public function startsWith($haystack, $needle)
    {
        return (strpos($haystack, $needle) === 0);
    }


    public function endsWith($haystack, $needle)
    {
        return (substr($haystack, -strlen($needle)) === $needle);
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
     * Connect to MySQL server using MySQLi driver:
     * <code>
     * $db = new database()
     * $dB->connect('localhost', 3306, 'root', 'r00tpassword', 'database1', 'mysqli');
     * </code>
     *
     * Alternatively, the connection information can be specified in the __construct() call:
     * <code>
     * $db = new database('localhost', 3306, 'root', 'r00tpassword', 'database1', 'mysqli');
     * </code>
     *
     * @internal Implementors who use a file (e.g. JSON) instead of a database are suggested to use "host", "user", and "password" as login details for a remote session, with "database" corresponding to the proper file. "user" and "password" could also be used to authenticate access to the file itself, but it is recommended that this be the same as the remote login information, if a remote login is used. Similarily, implementors who use a PHP object should follow the same remote guidelines if a remote session is possible, with the "database" corresponding with the unique object identifier.
     * @internal Ideally, all alternative SQL implementations should use databaseSQL.php. If, however, you are creating an SQL implementation, please respect the procedure used there: $host as the server host, $port as the server port, $user as the authentication name, $password as the authentication password, $database as the database name, and $driver as a unique referrence to your implementation. Again, user and password should be the same used for server authentication as for database authentication. If an implementation uses SQLite or a similar file-based cache, the file location should be that of the either $database or $host, as best determined by the implementor.
     *
     * @return bool - True if a connection was successfully established, false otherwise.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    abstract public function connect($host, $port, $user, $password, $database, $driver, $tablePrefix);


    /**
     * Closes a connection to a database server.
     *
     * @return void
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    abstract public function close();

    /*********************************************************
     ************************* END ***************************
     ******************* General Functions *******************
     *********************************************************/


    /*********************************************************
     ************************ START **************************
     ******************** Error Handling *********************
     *********************************************************/

    /**
     * Trigger an Error
     * When a database error is encountered, database implementations should call this function. It will both store the error in the $this->errors property and, potentially, log the error. $errorType should be specified to determine whether or not the error is critical (see below).
     *
     * In this example, an error, "Database is locked.", will be issued using the defined error level.
     * <code>
     * $this->triggerError('Database is locked.', 'function');
     * </code>
     *
     * @param string errorMessage - The error message to issue.
     * @param string errorType - The type of error encountered, one of:
     *  - 'function' - A function returns false. For instance, selectDB() fails because a database could not be found.
     *  - 'syntax' - A function can not complete due to a syntax error. Some drivers may not trigger this kind of error.
     *  - 'validation' - A function can not complete because the data specified does not validate, for instance a value is not recognised or is of the wrong type.
     *  - 'connection' - A connection failed. For instance, connect() returns false or the MySQL server is down. The latter error may not always be detected.
     *  - 'logic' - A logic error in the function occured. (Honestly, you should probably throw an exception instead, but whatever.)
     * @param bool suppressErrors - Do not trigger an error. The error will still be logged, but it will not interupt the program flow.
     * @return void
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    protected function triggerError($errorMessage, $errorData = '', $errorType = false, $suppressErrors = false)
    {
        if ($this->printErrors) { // Trigger error is not guaranteed to output the error message to the client. If this flag is set, we send the message before triggering the error. (At the same time, multiple messages may appear.)
            echo $errorMessage;
        }

        if (!$suppressErrors) {
            if ($this->errorFormatFunction && function_exists($this->errorFormatFunction)) {
                throw new $this->errorFormatFunction('dbError', json_encode(array(
                    "Message" => $errorMessage,
                    "Database Error" => $this->getLastError(),
                    "Error Data" => $errorData,
                    "Query Log" => $this->queryLog,
                    "Stack Trace" => debug_backtrace(false),
                )));
            }
            else {
                throw new Exception('A database error has occured (' . $this->getLastError() . '). Additional Data: "' . $errorData . '"');
            }
        }

        $this->newError($errorMessage . "\nAdditional Information:\n" . print_r($errorData, true));


        // If transaction mode is active, then any error will result in a rollback and the closure of the connection. Once transaction mode is ended, errors no longer result in a connection closure.
        if ($this->transaction) {
            $this->rollbackTransaction();
            $this->close();
        }
    }


    /**
     * Defines what error level should be used for all database errors called by the class. Class exceptions will not be affected.
     * This function's main purpose in surpressing errors at certain points in script execution, e.g.:
     * <code>
     * $db = new database('localhost', 3306, 'root', 'r00tpassword', 'database1', 'mysqli');
     * $db->setErrorLevel(E_NOTICE); // Surpress errors
     *
     * ...
     * if (count($db->select(...)) > 5) {
     *   trigger_error('Too many results in query.', $db->errorLevel);
     * }
     * </code>
     *
     * @param int level - PHP error level to use for all errors called by the class.
     * @return string - New error level.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function setErrorLevel($errorLevel)
    {
        return $this->errorLevel = $errorLevel;
    }


    /**
     * Get the Current Error Level
     * This function should be used to retrive the current error level. It could be useful, for instance, in showing other errors outside of the database class, e.g:
     * <code>
     * $db = new database('localhost', 3306, 'root', 'r00tpassword', 'database1', 'mysqli');
     * ...
     * if (count($db->select(...)) > 5) {
     *   trigger_error('Too many results in query.', $db->errorLevel);
     * }
     * </code>
     *
     * @return string - Current error level.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function getErrorLevel()
    {
        return $this->errorLevel;
    }


    /**
     * Adds a new error to the error log. This should normally only be called by triggerError(), but is left protected if a class wishes to use it.
     *
     * @param string $errorMessage - Text of message to log.
     * @return void
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    protected function newError($errorMessage)
    {
        $this->errors[] = $errorMessage;
    }


    /**
     * Retrives the last logged error.
     *
     * @return string - Last logged error.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function getLastError()
    {
        return end($this->errors);
    }


    /**
     * Clears the error log.
     *
     * @return void
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function clearErrors()
    {
        $this->errors = array();
    }

    /*********************************************************
     ************************* END ***************************
     ******************** Error Handling *********************
     *********************************************************/


    /*********************************************************
     ************************ START **************************
     ********************* Transactions **********************
     *********************************************************/


    abstract function startTransaction();


    abstract function rollbackTransaction();


    abstract function endTransaction();



    /*********************************************************
     ************************* END ***************************
     ********************* Transactions **********************
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
     * @param array $tableColumns - The columns of the table as an array in which all entries should be <code>array(columnName => columnProperties)</code>. The following is a list of allowed indexes for any given "type" index:
     *  - "int" type: INT "maxlen", BOOL "autoincrement"
     *  - "string" type: INT "maxlen", ARRAY "restrict"
     *  - "bitfield" type: INT "bits"
     *  - "time" type: none
     *  - "bool" type: none
     * @param array $tableIndexes - The indexes of the table.
     *
     * In this example, a new table, named "table1" will be created using the primary store type and with three columns, one each for integers, strings, and bitfields.
     * <code>
     * $db = new database('localhost', 3306, 'root', 'r00tpassword', 'database1', 'mysqli');
     * $db->createTable('table1', 'Our first table!', 'general', array(
     *   'column1' => array(
     *     'type' => 'int',
     *     'maxlen' => 10,
     *     'autoincrement' => true,
     *   ),
     *   'column2' => array(
     *     'type' => 'string',
     *     'maxlen' => 10,
     *     'restrict' => array('value1', 'value2', 'value3'),
     *   ),
     *   'column3' => array(
     *     'type' => 'bitfield',
     *     'bits' => 8,
     *   ),
     * ), array(
     *   'column1' => array(
     *     'type' => 'primary'
     *   ),
     *   'column2,column3' => array(
     *     'type' => 'unique',
     *   ),
     * ));
     * </code>
     *
     * @internal Implementers do not need to support $tableComment, which is only used for when developers wish to interact with the database. It serves no real purpose aside from documentation.
     * @internal Implementers are required to populate $this->storeTypes as described in its documentation.
     * @internal Implementers are required to support autoincrement on integer columns. If native support does not exist, this should be simulated, for instance by including a column property known as "uniqueValue" that increments with each insert, by maintaining a cache or database schema, or, if a database can function smoothly in doing so, by selecting the last inserted row in a table. For instance, in a JSON implementation, one could support a table as { row 1 : {} row 2 : {} uniqueValue : INT }.
     * @internal Restrict is strongly encouraged for implementation, but strictly speaking is not required. Developers of applications using the database are encouraged to validate data prior to database insertion. Implementations that do not support "restrict" should note this, so that developers can accomodate. (FreezeMessenger does validate all input, but also includes "restrict" for documentation purposes.)
     * @internal Finally, indexes are designed around SQL concepts. Implementators can support "primary" and "index" indexes mostly at their descretion -- they shouldn't affect a query's logic flow, and only exist to increase the speed of queries (something some implementations may benefit from, but others may not). However, "unique", which species that an index can not be duplicatd, must be emulated if it is not natively supported. Schema is the recommended way to emulate this: when the implementation's __construct is called, it should retrieve all unique indexes (likely from a cache or schema table). All insert() calls should then check to see if a unique index is violated, and respond accordingly.
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
     * In this example, the table named "table1" will be renamed to "specialTable1".
     * <code>
     * $db = new database('localhost', 3306, 'root', 'r00tpassword', 'database1', 'mysqli');
     * $db->renameTable('table1', 'specialTable1');
     * </code>
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
     * In this example, the table named "table1" will be deleted.
     * <code>
     * $db = new database('localhost', 3306, 'root', 'r00tpassword', 'database1', 'mysqli');
     * $db->deleteTable('table1');
     * </code>
     *
     * @return bool - True on success, false on failure.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    abstract public function deleteTable($tableName);


    /**
     * Returns an array of the tables in a database. The method used is driver specific, but where possible the SQL-standard INFORMATION_SCHEMA database will be used.
     *
     * @internal Due to the nature of this function, drivers that do not natively implement this functionality do not have to be emulated. However, such drivers MUST specify the object property $this->getTablesEnabled as being false.
     *
     * @return array - All table names (as strings) in a database.
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


    public function where($conditionArray)
    {
        $this->conditionArray = $conditionArray;

        return $this;
    }

    public function sortBy($sortArray)
    {
        $this->sortArray = $sortArray;

        return $this;
    }

    public function limit($limitArray)
    {
        $this->limitArray = $limitArray;

        return $this;
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
     * In this example, a row will be inserted with values specified for column1, column2, and column4. If one of these columns is "unique" and the value specified already exists, however, then only column2 will be updated with the new information.
     * <code>
     * $db = new database('localhost', 3306, 'root', 'r00tpassword', 'database1', 'mysqli');
     * $db->insert('table1', array(
     *   'column1' => 'value1',
     *   'column2' => 'value2',
     *   'column4' => 'value4',
     * ), array(
     *   'column2' => 'value2',
     * ));
     * </code>
     *
     * @internal The UPDATE functionality is required of all implementations. This could be implemented using a single query, as with most SQL variations, but it could also first check to see if a key restriction exists, and then either inserting or updating accordingly. If a database does not have native key restrictions, implementators are encouraged to support "simulated" key restrictions as defined in createTable().
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
     * In this example, an existing row's values will be updated as shown whereever both that rows' "column2" is equal to "value2" and that rows' "column3" is equal to "column3".
     * <code>
     * $db = new database('localhost', 3306, 'root', 'r00tpassword', 'database1', 'mysqli');
     * $db->update('table1', array(
     *   'column1' => 'value1',
     *   'column2' => 'value4',
     *   'column4' => 'value4',
     * ), array(
     *   'column2' => 'value2',
     *   'column3' => 'value3',
     * ));
     * </code>
     *
     * @return bool - True on success, false on failure.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    abstract public function update($table, $dataArray, $conditionArray = false);


    /**
     * Deletes rows from a table of the database.
     *
     * @param string $table - The table to delete from.
     * @param array $updateArray - The conditions to delete rows by.
     *
     * In this example, one or more existing rows will be deleted whereever both that rows' "column2" is equal to "value2" and that rows' "column3" is equal to "column3":
     * <code>
     * $db = new database('localhost', 3306, 'root', 'r00tpassword', 'database1', 'mysqli');
     * $db->delete('table1', array(
     *   'column2' => 'value2',
     *   'column3' => 'value3',
     * ));
     * </code>
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
     * Define a value as being of a certain type for database operations.
     *
     * @param mixed $value - The value to "type".
     * @param string $type - The type to attribute to the value, either:
     *  - 'int', an integer
     *  - 'ts', a timestamp
     *  - 'str', a string
     *  - 'col', a column
     * @param mixed $comp - How the value will be compared to the data present as an index, either:
     *  - 'search'
     *  - 'e'
     *
     * @internal Note that minimal casting actually occurs here, and should instead be performed by the select() function in each implementation. Instead, it simply ensures that basic PHP typing is present: integer for integers and timestamps, strings for strings and columns, arrays for arrays, and floats for floats.
     *
     * @return special - Returns a special representation of a column int only for use in database functions.
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */

    public function type($type, $value = '', $comp = 'e')
    {
        switch ($comp) {
            case 'e':      $typeComp = DatabaseTypeComparison::equals;            break;
            case 'lt':     $typeComp = DatabaseTypeComparison::lessThan;          break;
            case 'lte':    $typeComp = DatabaseTypeComparison::lessThanEquals;    break;
            case 'gt':     $typeComp = DatabaseTypeComparison::greaterThan;       break;
            case 'gte':    $typeComp = DatabaseTypeComparison::greaterThanEquals; break;
            case 'search': $typeComp = DatabaseTypeComparison::search;            break;
            case 'in':     $typeComp = DatabaseTypeComparison::in;                break;
            case 'notin':  $typeComp = DatabaseTypeComparison::notin;             break;
            case 'bAnd':   $typeComp = DatabaseTypeComparison::binaryAnd;         break;

            default:
                throw new Exception("Invalid comparison '$comp'");
                break;
        }


        switch ($type) {
            case 'int': case 'integer':
                return new DatabaseType(DatabaseTypeType::integer, (int)$value, $typeComp);
                break;

            case 'ts': case 'timestamp':
            return new DatabaseType(DatabaseTypeType::timestamp, (int)$value, $typeComp);
                break;

            case 'str': case 'string':
            return new DatabaseType(DatabaseTypeType::string, (string)$value, $typeComp);
                break;

            case 'col': case 'column':
            return new DatabaseType(DatabaseTypeType::column, (string)$value, $typeComp);
                break;

            case 'flt': case 'float':
                throw new Exception('Float is currently unimplemented.');
                return new DatabaseType(DatabaseTypeType::float, (string)$value, $typeComp);
                break;

            case 'bool':
                return new DatabaseType(DatabaseTypeType::bool, (bool)$value, DatabaseTypeComparison::equals);
                break;

            case 'empty':
                return new DatabaseType(DatabaseTypeType::null, DatabaseType::null, DatabaseTypeComparison::equals);
                break;

            case 'equation':
                return new DatabaseType(DatabaseTypeType::equation, (string)$value, $typeComp);
                break;

            case 'blob':case 'binary':
                return new DatabaseType(DatabaseTypeType::blob, $value, $typeComp);
                break;

            case 'arr': case 'array':
                if (count($value) === 0) $this->triggerError('Empty arrays can not be specified.', false, 'validation');

                return new DatabaseType(DatabaseTypeType::arraylist, (array)$value, $typeComp);
                break;

            default:
                $this->triggerError("Unrecognised type '$type'");
                break;
        }
    }

    protected function isTypeObject($type)
    {
        return (is_object($type) && get_class($type) === 'DatabaseType'); // TODO: instanceof, dummy
    }

    public function in($value)
    {
        return $this->type('arr', $value, 'in');
    }

    public function search($value)
    {
        return $this->type('string', $value, 'search');
    }

    public function bool($value)
    {
        return $this->type('bool', $value);
    }

    public function blob($value)
    {
        return $this->type('blob', $value);
    }

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
    public function int($value, $comp = 'e')
    {
        return $this->type('int', $value, $comp);
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
    public function ts($value, $comp = 'e')
    {
        return $this->type('ts', $value, $comp);
    }


    /**
     * An alias for ts('time', time() + $offset). This function is recommended over that alternative, however, because it allows for potentially greater precision than time().
     *
     * @param int $offset - A number of seconds that should be added to the current time. Use a negative value for subtraction.
     * @param str $comp - See ts().
     */
    public function now($offset = 0, $comp = 'e')
    {
        return $this->ts(time() + $offset, $comp);
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
    public function str($value, $comp = 'e')
    {
        return $this->type('string', (string)$value, $comp);
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
    public function col($value, $comp = 'e')
    {
        return $this->type('column', $value, $comp);
    }

    /*********************************************************
     ************************* END ***************************
     **************** Type-Casting Functions *****************
     *********************************************************/

    /**
     * Applies $function to the $data. If $data is an instance of DatabaseType, its other values will be retained, though its type may change if $forceType is set to one of the DatabaseTypeType values.
     * If $data was not an instance of DatabaseType, it will be returned as one, with $forceType or DatabaseTypeType::string as its type, and DatabaseTypeComparison::equals as its comparison operator.
     *
     * @param callable $function
     * @param mixed|DatabaseType $data
     * @param bool|DatabaseTypeType $forceType
     * @return DatabaseType
     * @throws Exception
     */
    protected function applyTransformFunction($function, $data, $forceType = false): DatabaseType {
        // If $data is an instance of DatabaseType...
        if ($this->isTypeObject($data)) {
            // Equations and columns cannot be transformed.
            if ($data->type === DatabaseTypeType::equation || $data->type === DatabaseTypeType::column) {
                throw new Exception('Database data transformation attempted on unsuported object.');
            }

            // Lists are fancy -- we trasnsform the elements of the list recursively. The elements can be of any type that can be passed to applyTransformFunction normally.
            elseif ($data->type === DatabaseTypeType::arraylist) {
                foreach ($data->value AS &$value) {
                    $value = $this->applyTransformFunction($function, $value, $forceType);
                }

                return $data;
            }

            // All other values apply the function and, if $forceType is set, the relevant datatype. The comparison operator is not changed.
            else {
                return new DatabaseType(($forceType ? $forceType : $data->type), call_user_func($function, $data), $data->comparison);
            }
        }

        // If $data isn't an instance of DatabaseType, set it to one, with $forceType or DatabaseTypeType::string as its type, and DatabaseTypeComparison::equals as its comparison operator.
        else {
            return new DatabaseType(($forceType ? $forceType : DatabaseTypeType::string), call_user_func($function, $data), DatabaseTypeComparison::equals);
        }
    }


    /**
     * Opens a database result object using the specified parameters.
     *
     * @param mixed $queryData - Typically the object associated with a query to a database using a certain driver.
     * @param mixed $sourceQuery - Typically a string or array containing the data used to make the query to the driver that resulted in $queryData. Generally used for debugging.
     * @param array $driver - The driver used to make the query.
     *
     * @return databaseResult
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    abstract protected function databaseResultPipe($queryData, $query, $driver);
}

class databaseResult
{
    /**
     * Construct
     *
     * @param object $queryData - The database object.
     * @param string $sourceQuery - The source query, which can be stored for referrence.
     * @return void
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function __construct($queryData, $sourceQuery, $language)
    {
        $this->queryData = $queryData;
        $this->sourceQuery = $sourceQuery;
        $this->driver = $language;
    }


    /**
     * Calls a database function, such as mysql_connect or mysql_query, using lookup tables
     *
     * @return void
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function functionMap($operation)
    {
        $args = func_get_args();
        switch ($this->driver) {
            case 'mysql':
                switch ($operation) {
                    case 'fetchAsArray' :
                        return (($data = mysql_fetch_assoc($args[1])) === false ? false : $data);
                        break;
                    case 'getCount' :
                        return mysql_num_rows($args[1]);
                        break;
                }
                break;

            case 'mysqli':
                switch ($operation) {
                    case 'fetchAsArray' :
                        return (($data = $this->queryData->fetch_assoc()) === null ? false : $data);
                        break;
                    case 'getCount' :
                        return $args[1]->num_rows;
                        break;
                }
                break;

            case 'pdo':
                switch ($operation) {
                    case 'fetchAsArray' :
                        return ((($data = $this->queryData->fetch(PDO::FETCH_ASSOC)) === null) ? false : $data);
                        break;
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
    public function setQuery($queryData)
    {
        $this->queryData = $queryData;
    }


    public function getCount()
    {
        return $this->functionMap('getCount', $this->queryData);
    }


    /**
     * Get Database Object as an Associative Array. An empty array will be returned if an error occurs.
     *
     * @param mixed $index
     * @return array
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function getAsArray($index = true, $group = false)
    {
        $data = array();
        $indexV = 0;

        if ($this->queryData !== false) {
            if ($index) { // An index is specified, generate & return a multidimensional array. (index => [key => value], index being the value of the index for the row, key being the column name, and value being the corrosponding value).
                while ($row = $this->functionMap('fetchAsArray', $this->queryData)) {
                    if ($row === null || $row === false) break;

                    if ($index === true) { // If the index is boolean "true", we simply create numbered rows to use. (1,2,3,4,5)
                        $data[$indexV++] = $row; // Append the data.
                    } else { // If the index is not boolean "true", we instead get the column value of the index/column name.
                        $index = (array)$index;

                        // Okay, so here's the thing: there's no easy way to build this thing below with an unlimited number of index values. Instead, just because I can, I'm going to hardcode five. If someone can submit a patch that is more flexible, it would be awesome -- I simply can't think of a good way how myself.
                        if ($group) { // Allow duplicate values.
                            switch (count($index)) {
                                case 1:
                                    $data[$row[$index[0]]][] = $row;
                                    break;
                                case 2:
                                    $data[$row[$index[0]]][$row[$index[1]]][] = $row;
                                    break;
                                case 3:
                                    $data[$row[$index[0]]][$row[$index[1]]][$row[$index[2]]][] = $row;
                                    break;
                                case 4:
                                    $data[$row[$index[0]]][$row[$index[1]]][$row[$index[2]]][$row[$index[3]]][] = $row;
                                    break;
                                case 5:
                                    $data[$row[$index[0]]][$row[$index[1]]][$row[$index[2]]][$row[$index[3]]][$row[$index[4]]][] = $row;
                                    break;
                            }
                        } else { // Overwrite duplicate values.
                            switch (count($index)) {
                                case 1:
                                    $data[$row[$index[0]]] = $row;
                                    break;
                                case 2:
                                    $data[$row[$index[0]]][$row[$index[1]]] = $row;
                                    break;
                                case 3:
                                    $data[$row[$index[0]]][$row[$index[1]]][$row[$index[2]]] = $row;
                                    break;
                                case 4:
                                    $data[$row[$index[0]]][$row[$index[1]]][$row[$index[2]]][$row[$index[3]]] = $row;
                                    break;
                                case 5:
                                    $data[$row[$index[0]]][$row[$index[1]]][$row[$index[2]]][$row[$index[3]]][$row[$index[4]]] = $row;
                                    break;
                            }
                        }
                    }
                }

                return $data; // All rows fetched, return the data.
            } else { // No index is present, generate a two-dimensional array (key => value, key being the column name, value being the corrosponding value).
                $return = $this->functionMap('fetchAsArray', $this->queryData);
                return (!$return ? array() : ($index !== false ? $return[$index] : $return));
            }
        } else {
            return array(); // Query data is false or null, return an empty array.
        }
    }


    public function getColumnValues($column, $columnKey = false)
    {
        $columnValues = array();

        while ($row = $this->functionMap('fetchAsArray', $this->queryData)) {
            if ($columnKey)
                $columnValues[$row[$columnKey]] = $row[$column];

            else
                $columnValues[] = $row[$column];
        }

        return $columnValues;
    }


    public function getColumnValue($column)
    {
        $row = $this->functionMap('fetchAsArray', $this->queryData);

        return $row[$column];
    }


    /**
     * Get the database object as a string, using the specified format/template. Each result will be passed to this template and stored in a string, which will be appended to the entire result.
     *
     * @param string $format
     * @return mixed
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function getAsTemplate($format)
    {
        static $data;
        $uid = 0;

        if ($this->queryData !== false && $this->queryData !== null) {
            while (false !== ($row = $this->functionMap('fetchAsArray', $this->queryData))) { // Process through all rows.
                $uid++;
                $row['uid'] = $uid; // UID is a variable that can be used as the row number in the template.

                $data2 = preg_replace('/\$([a-zA-Z0-9]+)/e', '$row[$1]', $format); // the "e" flag is a PHP-only extension that allows parsing of PHP code in the replacement.
                $data2 = preg_replace('/\{\{(.*?)\}\}\{\{(.*?)\}\{(.*?)\}\}/e', 'stripslashes(iif(\'$1\',\'$2\',\'$3\'))', $data2); // Slashes are appended automatically when using the /e flag, thus corrupting links.
                $data .= $data2;
            }

            return $data;
        } else {
            return false; // Query data is false or null, return false.
        }
    }
}

class DatabaseTypeType {
    const __default = self::string;

    const null = 0;
    const string = 1;
    const integer = 2;
    const timestamp = 3;
    const arraylist = 4;
    const bool = 5;
    const column = 6;
    const equation = 7;
    const blob = 8;
}

class DatabaseTypeComparison {
    const __default = self::equals;

    const notin = -4;
    const lessThan = -2;
    const lessThanEquals = -1;
    const equals = 0;
    const greaterThan = 1;
    const greaterThanEquals = 2;
    const search = 3;
    const in = 4;
    const binaryAnd = 5;

    const assignment = 1000;
}

class DatabaseType {
    const null = null;

    public $type;
    public $value;
    public $comparison;

    public function __construct($type, $value, $comparison) {
        /* Validation Checks */
        if ($type === DatabaseTypeType::arraylist && !($comparison === DatabaseTypeComparison::in || $comparison === DatabaseTypeComparison::notin))
            throw new Exception('Arrays can only be compared with in and notin.');
        if ($type !== DatabaseTypeType::arraylist && ($comparison === DatabaseTypeComparison::in || $comparison === DatabaseTypeComparison::notin)) {
            throw new Exception('in and notin can only be used with arrays.');
        }


        $this->type = $type;
        $this->value = $value;
        $this->comparison = $comparison;
    }
}

?>