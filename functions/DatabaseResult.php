<?php
class databaseResult
{
    /**
     * @var array The result-set returned from the query.
     */
    public $queryData;

    /**
     * @var array The column-name mapping (between actual in-database names and column aliases) used to generate this result.
     */
    public $reverseAlias;

    /**
     * @var string The source query used to generate this result.
     */
    public $sourceQuery;

    /**
     * @var DatabaseSQL The database object used to generate this result.
     */
    public $database;

    /**
     * @var bool Whether this result should be considered "paginated." If so, more results are available by simply incrementing the page used in the initial query.
     */
    public $paginated = false;

    /**
     * @var int The number of available results.
     */
    public $count = 0;

    /**
     * @var int The result number we are currently pointing to.
     */
    public $resultIndex = 0;


    /**
     * @var array An array containing the field numbers corresponding to all binary columns in the current resultset.
     */
    public $binaryFields = [];

    public $data = [];

    /**
     * Construct
     *
     * @param object $queryData - The database object.
     * @param string $sourceQuery - The source query, which can be stored for referrence.
     * @return void
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function __construct($queryData, $reverseAlias, $sourceQuery, Database $database, int $resultLimit = 0)
    {
        $this->queryData = $queryData;
        $this->reverseAlias = $reverseAlias;
        $this->sourceQuery = $sourceQuery;
        $this->database = $database;

        if ($this->database->driver === 'pdoMysql') {
            try {
                $this->data = $this->queryData->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $ex) {
                var_dump($ex, $sourceQuery);
            }
        }

        if ($resultLimit > 1 && $this->functionMap('getCount') > $resultLimit) {
            $this->paginated = true;
            $this->count = $resultLimit;
        }
        else {
            $this->count = $this->functionMap('getCount');
        }

        if ($this->database->driver === 'pgsql') {
            $num = pg_num_fields($this->queryData);
            for ($i = 0; $i < $num; $i++) {
                if (pg_field_type($this->queryData, $i) === 'bytea') {
                    $this->binaryFields[] = pg_field_name($this->queryData, $i);
                }
            }
        }
    }


    /**
     * Calls a database function, such as mysql_connect or mysql_query, using lookup tables
     *
     * @return void
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function functionMap($operation)
    {
        switch ($operation) {
            case 'fetchAsArray':
                if ($this->resultIndex++ >= $this->count) {
                    return false;
                }
            break;
        }

        $args = func_get_args();
        switch ($this->database->driver) {
            case 'mysql':
                switch ($operation) {
                    case 'fetchAsArray' :
                        return (($data = mysql_fetch_assoc($this->queryData)) === false ? false : $data);
                    break;
                    case 'getCount' :
                        return mysql_num_rows($this->queryData);
                    break;
                }
            break;

            case 'mysqli':
                switch ($operation) {
                    case 'fetchAsArray' :
                        return (($data = $this->queryData->fetch_assoc()) === null ? false : $data);
                    break;
                    case 'getCount' :
                        return $this->queryData->num_rows;
                    break;
                }
            break;

            case 'pgsql':
                switch ($operation) {
                    case 'fetchAsArray' :
                        $data = pg_fetch_assoc($this->queryData);

                        // Decode bytea values
                        if ($data) {
                            /*
                            $columns = array_keys($data);
                            foreach ($columns AS $i => $column) {
                                echo $column . ':' . pg_field_type($this->queryData, $i) . "\n";
                                if (pg_field_type($this->queryData, $i) === 'bytea') {
                                    $data[$column] = pg_unescape_bytea($data[$column]);
                                }
                            }*/

                            foreach ($this->binaryFields AS $field) {
                                $data[$field] = pg_unescape_bytea($data[$field]);
                            }
                            //var_dump($data);
                            //var_dump(debug_backtrace());
                        }

                        return $data;
                    break;
                    case 'getCount' :
                        return pg_num_rows($this->queryData);
                    break;
                }
            break;

            case 'pdoMysql':
                switch ($operation) {
                    case 'getCount' :
                        return count($this->data);
                    break;
                    case 'fetchAsArray':
                        return $this->data[$this->resultIndex - 1];
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
        //$this->queryData = $queryData;
    }


    /**
     * @return int the number of rows in the resultset.
     */
    public function getCount()
    {
        return $this->count;
    }


    /**
     * Get database resultset as a single associative array or multiple associative arrays. An empty array will be returned if an error occurs.
     *
     * @param bool|string $index When this is a string, it corresponds to a column whose value will be used to index the returned arrays. When it is true, the returned arrays will be indexed automatically, starting at 0. When it is false, only one array will be returned.
     * @param bool $group If true, results will be grouped by the index, e.g. if you group on "entries", [1 => [["id" => 1, "entries" => 1], ["id" => 2, "entries" => 1]]] will be returned. If $group were false, [1 => ["id" => 1, "entries" => 1]] would be returned instead.
     *
     * @return array
     * @author Joseph Todd Parsons <josephtparsons@gmail.com>
     */
    public function getAsArray($index = true, $group = false)
    {
        $data = array();

        $rowNumber = 0; // Count the number of results processed, ensuring we don't exceed $this->count.
        $indexV = 0;

        if ($this->queryData !== false) {
            if ($index) { // An index is specified, generate & return a multidimensional array. (index => [key => value], index being the value of the index for the row, key being the column name, and value being the corrosponding value).
                while ($row = $this->functionMap('fetchAsArray')) {
                    if ($rowNumber++ > $this->count) break; // Don't go over the pagination limit. In general, there will only be one extra result, which indicates that more results are available.

                    //if ($row === null || $row === false) break;

                    /* Decoding */
                    foreach ($row AS $columnName => &$columnValue) {
                        $columnValue = $this->applyColumnTransformation($columnName, $columnValue);
                    }



                    if ($index === true) { // If the index is boolean "true", we simply create numbered rows to use. (1,2,3,4,5)
                        $data[$indexV++] = $row; // Append the data.
                    }

                    else { // If the index is not boolean "true", we instead get the column value of the index/column name.
                        $index = (array) $index;

                        $ref =& $data;
                        for ($i = 1; $i <= count($index); $i++) {
                            $ref =& $ref[$row[$index[$i - 1]]];
                        }

                        if ($group)
                            $ref[] = $row;

                        else
                            $ref = $row;
                    }
                }

                return $data; // All rows fetched, return the data.
            }

            else { // No index is present, generate a two-dimensional array (key => value, key being the column name, value being the corrosponding value).
                $return = $this->functionMap('fetchAsArray');

                if ($return) {
                    foreach ($return AS $columnName => &$columnValue) {
                        $columnValue = $this->applyColumnTransformation($columnName, $columnValue);
                    }
                }

                return (!$return ? array() : $return);
            }
        }

        else {
            return array(); // Query data is false or null, return an empty array.
        }
    }


    public function getColumnValues($columns, $columnKey = false)
    {
        $columnValues = array();
        $columns = (array) $columns;

        while ($row = $this->functionMap('fetchAsArray')) {
            $ref =& $columnValues;
            for ($i = 1; $i < count($columns); $i++) {
                $ref =& $ref[$row[$columns[$i - 1]]];
            }

            if ($columnKey)
                $ref[$this->applyColumnTransformation($columnKey, $row[$columnKey])] = $this->applyColumnTransformation(end($columns), $row[end($columns)]);

            else
                $ref[] = $this->applyColumnTransformation(end($columns), $row[end($columns)]);
        }

        return $columnValues;
    }


    public function getColumnValue($column)
    {
        $row = $this->functionMap('fetchAsArray');

        return $this->applyColumnTransformation($column, $row[$column]);
    }


    public function applyColumnTransformation($column, $value) {
        if (isset($this->reverseAlias[$column])) {
            $tableName = $this->reverseAlias[$column][0];

            if (isset($this->database->encode[$tableName][$column])) {
                return call_user_func($this->database->encode[$tableName][$column][2], $value);
            }

            else {
                return $value;
            }
        }
        else {
            return $value;
        }
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
            while (false !== ($row = $this->functionMap('fetchAsArray'))) { // Process through all rows.
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
?>