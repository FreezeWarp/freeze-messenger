<?php
class databaseSQL extends database {
  public function __construct() {
    parent::__construct();
  }

  
  private function connectSQL() {
    $this->setLanguage($driver);


    if (!$link = $this->functionMap('connect', $host, $port, $user, $password, $database)) { // Make the connection.
      $this->error = 'The connection was refused: ' . $this->functionMap('error');

      return false;
    }
    else {
      $this->dbLink = $link; // Set the object property "dbLink" to the database connection resource. It will be used with most other queries that can accept this parameter.
    }


    if (!$this->activeDatabase && $database) { // Some drivers will require this.
      if (!$this->selectDatabase($database)) {
        $this->error = 'Could not select database ("' . $database . '"): ' . $this->functionMap('error');

        return false;
      }
    }


    return true;
  }
    
    
    
    /**
   * Set Language / Database Driver
   *
   * @param string language
   * @return void
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  private function setLanguage($language) {
    $this->language = $language;

    switch ($this->language) {
      case 'mysql':
      case 'mysqli':
      $this->languageSubset = 'sql';

      $this->tableQuoteStart = '`';  $this->tableQuoteEnd = '`';  $this->tableAliasQuoteStart = '`';  $this->tableAliasQuoteEnd = '`';
      $this->columnQuoteStart = '`'; $this->columnQuoteEnd = '`'; $this->columnAliasQuoteStart = '`'; $this->columnAliasQuoteEnd = '`';
      $this->stringQuoteStart = '"'; $this->stringQuoteEnd = '"'; $this->emptyString = '""';          $this->tableColumnDivider = '.';
      $this->stringFuzzy = '%';

      $this->sortOrderAsc = 'ASC'; $this->sortOrderDesc = 'DESC';

      $this->tableTypes = array(
        'general' => 'InnoDB',
        'memory' => 'MEMORY',
      );
      break;

      case 'postgresql':
      $this->languageSubset = 'sql';

      $this->tableQuoteStart = '"';    $this->tableQuoteEnd = '"';    $this->tableAliasQuoteStart = '"';  $this->tableAliasQuoteEnd = '"';
      $this->columnQuoteStart = '"';   $this->columnQuoteEnd = '"';   $this->columnAliasQuoteStart = '"'; $this->columnAliasQuoteEnd = '"';
      $this->stringQuoteStart = '"';   $this->stringQuoteEnd = '"';   $this->emptyString = '""';          $this->tableColumnDivider = '.';    
      $this->stringFuzzy = '%';


      $this->sortOrderAsc = 'ASC'; $this->sortOrderDesc = 'DESC';
      break;
    }

    switch ($this->language) {
      case 'mysql':
      case 'mysqli':
      case 'postgresql':
      $this->comparisonTypes = array(
        'e' => '=',
        'ne' => '!=',  '!e' => '!=', // same
        'lt' => '<',   '!gte' => '>', // same
        'gt' => '>',   '!lte' => '>', // same
        'lte' => '<=', '!gt' => '>=', // same
        'gte' => '>=', '!lt' => '>=', // same

        'and' => '&',  '!xor' => '&', // same
        'xor' => '^',  '!and' => '^', // same

        'in' => 'IN',        '!notin' => 'IN', // same
        'notin' => 'NOT IN', '!in' => 'NOT IN', // same

        'regexp' => 'REGEXP', // Applies extended POSIX regular expression to index. It is natively implemented in MySQL, PostGreSQL, and Oracle SQL databases. It is absent in MSSQL, and the status in VoltDB and SQLite is unknown.
        'regex' => 'REGEXP', // Alias of "regexp"

        'glob' => 'LIKE',
        'like' => 'LIKE', // Alias of "glob"
      );

      $this->concatTypes = array(
        'both' => ' AND ',
        'either' => ' OR ',
      );

      $this->keyConstants = array(
        'primary' => 'PRIMARY KEY',
        'unique' => 'UNIQUE KEY',
        'index' => 'KEY',
      );

      $this->defaultPhrases = array(
        '__TIME__' => 'CURRENT_TIMESTAMP',
      );

      $this->columnIntLimits = array(
        1 => 'TINYINT',   2 => 'TINYINT',   3 => 'SMALLINT',  4 => 'SMALLINT',
        5 => 'MEDIUMINT', 6 => 'MEDIUMINT', 7 => 'MEDIUMINT', 8 => 'INT',
        9 => 'INT',       0 => 'BIGINT',
      );

      $this->columnStringPermLimits = array(
        1 => 'CHAR',           255 => 'VARCHAR', 1000 => 'TEXT', 8191 => 'MEDIUMTEXT',
        2097151 => 'LONGTEXT',
      );

      $this->columnStringTempLimits = array(
        255 => 'CHAR',           65535 => 'VARCHAR',
      );

      $this->columnStringNoLength = array('MEDIUMTEXT', 'LONGTEXT');

      $this->columnBitLimits = array(
        0 => 'TINYINT UNSIGNED',  8 => 'TINYINT UNSIGNED', 16 => 'SMALLINT UNSIGNED', 24 => 'MEDIUMINT UNSIGNED',
        32 => 'INTEGER UNSIGNED', 64 => 'LONGINT UNSIGNED',
      );

      $this->globFindArray = array('*', '?');
      $this->globReplaceArray = array('%', '_');
      
      $this->mode = 'SQL';
      break;
    }
  }
  
  
  
  private function selectSQL() {
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
          if (strlen($tableName) > 0) { // If the tableName is defined...
            if (strstr($tableName,' ') !== false) { // A space can be used to create a table alias, which is sometimes required for different queries.
              $tableParts = explode(' ', $tableName);

              $finalQuery['tables'][] = $this->tableQuoteStart . $tableParts[0] . $this->tableQuoteEnd . ' AS ' . $this->tableAliasQuoteStart . $tableParts[1] . $this->tableAliasQuoteEnd; // Identify the table as [tableName] AS [tableAlias]

              $tableName = $tableParts[1];
            }
            else {
              $finalQuery['tables'][] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd; // Identify the table as [tableName]
            }

            if (is_array($tableCols)) { // Table columns have been defined with an array, e.g. ["a", "b", "c"]
              foreach($tableCols AS $colName => $colAlias) {
                if (strlen($colName) > 0) {
                  if (strstr($colName,' ') !== false) { // A space can be used to create identical columns in different contexts, which is sometimes required for different queries.
                    $colParts = explode(' ', $colName);
                    $colName = $colParts[0];
                  }

                  if (is_array($colAlias)) { // Used for advance structures and function calls.
                    if (isset($colAlias['context'])) {
                      throw new Exception('Deprecated context.'); // TODO
                    }

                    $finalQuery['columns'][] = $this->columnQuoteStart . $colName . $this->columnQuoteStart . ' AS ' . $this->columnAliasQuoteEnd . $colAlias['name'] . $this->columnAliasQuoteStart; // Identify column as [columnName] AS [columnAlias]
                    $reverseAlias[$colAlias['name']] = $colName;
                  }
                  else {
                    $finalQuery['columns'][] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd . $this->tableColumnDivider . $this->columnQuoteStart . $colName . $this->columnQuoteStart . ' AS ' . $this->columnAliasQuoteEnd . $colAlias . $this->columnAliasQuoteStart;
                    $reverseAlias[$colAlias] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd . $this->tableColumnDivider . $this->columnQuoteStart . $colName . $this->columnQuoteStart;
                  }
                }
                else {
                  throw new Exception('Invalid select array: column name empty'); // Throw an exception.
                }
              }
            }
            elseif (is_string($tableCols)) { // Table columns have been defined with a string list, e.g. "a,b,c"
              $columnParts = explode(',', $tableCols); // Split the list into an array, delimited by commas

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

                $finalQuery['columns'][] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd . $this->tableColumnDivider . $this->columnQuoteStart . $columnPartName . $this->columnQuoteStart . ' AS ' . $this->columnAliasQuoteEnd . $columnPartAlias . $this->columnAliasQuoteStart;
                $reverseAlias[$columnPartAlias] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd . $this->tableColumnDivider . $this->columnQuoteStart . $columnPartName . $this->columnQuoteStart;
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
                case 'asc': $directionSym = $this->sortOrderAsc; break;
                case 'desc': $directionSym = $this->sortOrderDesc; break;
                default: $directionSym = $this->sortOrderAsc; break;
              }

              $finalQuery['sort'][] = $reverseAlias[$sortCol] . " $directionSym";
            }
            else {
              throw new Exception('Unrecognised sort column: ' . $sortCol);
            }
          }
        }
      }
      elseif (is_string($sort)) {
        $sortParts = explode(',',$sort); // Split the list into an array, delimited by commas

        foreach ($sortParts AS $sortPart) { // Run through each list item
          $sortPart = trim($sortPart); // Remove outside whitespace from the item

          if (strpos($sortPart,' ') !== false) { // If a space is within the part, then the part is formatted as "columnName direction"
            $sortPartParts = explode(' ',$sortPart); // Divide the piece

            $sortCol = $sortPartParts[0]; // Set the name equal to the first part of the piece
            switch (strtolower($sortPartParts[1])) {
              case 'asc': $directionSym = $this->sortOrderAsc; break;
              case 'desc': $directionSym = $this->sortOrderDesc; break;
              default: $directionSym = $this->sortOrderAsc; break;
            }
          }
          else { // Otherwise, we assume asscending
            $sortCol = $sortPart; // Set the name equal to the sort part.
            $directionSym = $this->sortOrderAsc; // Set the alias equal to the default, ascending.
          }

          $finalQuery['sort'][] = $reverseAlias[$sortCol] . " $directionSym";
        }
      }

      $finalQuery['sort'] = implode(', ', $finalQuery['sort']);
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
  
  private function insertSQL() {
    $columns = implode(',', $columns); // Convert the column array into to a string.
    $values = implode(',', $values); // Convert the data array into a string.

    $query = "INSERT INTO $table ($columns) VALUES ($values)";

    if ($updateArray) { // This is used for an ON DUPLICATE KEY request.
      list($columns, $values) = $this->splitArray($updateArray);

      for ($i = 0; $i < count($columns); $i++) {
        $update[] = $columns[$i] . ' = ' . $values[$i];
      }

      $update = implode($update, ', ');

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
  
  
  
  private function deleteSQL($table, $conditionArray) {
    if ($conditionArray === false) {
      $delete = 'TRUE';
    }
    else {
      list($columns, $values, $conditions) = $this->splitArray($conditionArray);

      for ($i = 0; $i < count($columns); $i++) {
        if (!$conditions[$i]) {
          $csym = $this->comparisonTypes['e'];
        }
        elseif (isset($this->comparisonTypes[$conditions[$i]])) {
          $csym = $this->comparisonTypes[$conditions[$i]];
        }
        else {
          throw new Exception('Unrecognised comparison type: ' . $conditions[$i]);
        }

        $delete[] = $columns[$i] . $csym . $values[$i];
      }

      $delete = implode($delete, $this->concatTypes['both']);
    }

    $query = "DELETE FROM $table WHERE $delete";

    return $this->rawQuery($query);
  }
  
  
  
  private function updateSQL($table, $dataArray, $conditionArray) {
    for ($i = 0; $i < count($columns); $i++) {
      $update[] = $columns[$i] . ' = ' . $values[$i];
    }

    $update = implode($update,', ');

    $query = "UPDATE {$table} SET {$update}";

    if ($conditionArray) {
      list($columns, $values, $conditions) = $this->splitArray($conditionArray);

      for ($i = 0; $i < count($columns); $i++) {
        if (!$conditions[$i]) $csym = $this->comparisonTypes['e'];
        elseif (isset($this->comparisonTypes[$conditions[$i]])) $csym = $this->comparisonTypes[$conditions[$i]];
        else throw new Exception('Unrecognised comparison type: ' . $conditions[$i]);

        $cond[] = $columns[$i] . $csym . $values[$i];
      }

      $query .= ' WHERE ' . implode($cond, $this->concatTypes['both']);
    }


    return $this->rawQuery($query);
  }
  
  
  
  private function createTableSQL($tableName, $tableComment, $engine, $tableColumns, $tableIndexes) {
    $tableProperties = '';
    
    foreach ($tableColumns AS $column) {
      $typePiece = '';

      switch ($column['type']) {
        case 'int':
        if (isset($this->columnIntLimits[$column['maxlen']])) {
          if (in_array($type, $this->columnStringNoLength)) $typePiece = $this->columnIntLimits[$column['maxlen']];
          else $typePiece = $this->columnIntLimits[$column['maxlen']] . '(' . (int) $column['maxlen'] . ')';
        }
        else {
          $typePiece = $this->columnIntLimits[0];
        }

        if ($column['autoincrement']) {
          $typePiece .= ' AUTO_INCREMENT'; // Ya know, that thing where it sets itself.
          $tableProperties .= ' AUTO_INCREMENT = ' . (int) $column['autoincrement'];
        }
        break;

        case 'string':
        if ($column['restrict']) {
          $restrictValues = array();

          foreach ((array) $column['restrict'] AS $value) $restrictValues[] = '"' . $this->escape($value) . '"';

          $typePiece = 'ENUM(' . implode(',',$restrictValues) . ')';
        }
        else {
          if ($storeType === 'memory') $this->columnStringLimits = $this->columnStringTempLimits;
          else                         $this->columnStringLimits = $this->columnStringPermLimits;

          $typePiece = '';

          foreach ($this->columnStringLimits AS $length => $type) {
            if ($column['maxlen'] <= $length) {
              if (in_array($type, $this->columnStringNoLength)) $typePiece = $type;
              else $typePiece = $type . '(' . $column['maxlen'] . ')';

              break;
            }
          }

          if (!$typePiece) {
            $typePiece = $this->columnStringNoLength[0];
          }
        }

        $typePiece .= ' CHARACTER SET utf8 COLLATE utf8_bin';
        break;

        case 'bitfield':
        if (!isset($column['bits'])) {
          $typePiece = 'TINYINT UNSIGNED'; // Sane default
        }
        else {
          if ($column['bits'] <= 8)      $typePiece = 'TINYINT UNSIGNED';
          elseif ($column['bits'] <= 16) $typePiece = 'SMALLINT UNSIGNED';
          elseif ($column['bits'] <= 24) $typePiece = 'MEDIUMINT UNSIGNED';
          elseif ($column['bits'] <= 32) $typePiece = 'INTEGER UNSIGNED';
          else                           $typePiece = 'LONGINT UNSIGNED';
        }
        break;

        case 'time':
        $typePiece = 'INTEGER UNSIGNED'; // Note: replace with LONGINT to avoid the Epoch issues in 2038 (...I'll do it in FIM5 or so). For now, it's more optimized. Also, since its UNSIGNED, we actually have more until 2106 or something like that.
        break;

        case 'bool':
        $typePiece = 'TINYINT(1) UNSIGNED';
        break;

        default:
        throw new Exception('Unrecognised type.');
        break;
      }


      if ($column['default']) {
        if (isset($this->defaultPhrases[$column['default']])) {
          $typePiece .= ' DEFAULT ' . $this->defaultPhrases[$column['default']];
        }
        else {
          $typePiece .= ' DEFAULT "' . $this->escape($column['default']) . '"';
        }
      }

      $columns[] = $this->columnQuoteStart . $this->escape($column['name']) . $this->columnQuoteEnd . " {$typePiece} NOT NULL COMMENT \"" . $this->escape($column['comment']) . '"';
    }



    foreach ($tableIndexes AS $key) {
      if (isset($this->keyConstants[$key['type']])) {
        $typePiece = $this->keyConstants[$key['type']];
      }
      else {
        throw new Exception('Unrecognised key type: ' . $key['type']);
      }


      if (strpos($key['name'], ',') !== false) {
        $keyCols = explode(',', $key['name']);

        foreach ($keyCols AS &$keyCol) {
          $keyCol = $this->columnQuoteStart . $keyCol . $this->columnQuoteEnd;
        }

        $key['name'] = implode(',', $keyCols);
      }
      else {
        $key['name'] = $this->columnAliasStart . $key['name'] . $this->columnAliasEnd;
      }


      $keys[] = "{$typePiece} ({$key['name']})";
    }

    return $this->rawQuery('CREATE TABLE IF NOT EXISTS ' . $this->tableQuoteStart . $this->escape($tableName) . $this->tableQuoteEnd . ' (
' . implode(",\n  ",$columns) . ',
' . implode(",\n  ",$keys) . '
) ENGINE="' . $this->escape($engine) . '" COMMENT="' . $this->escape($tableComment) . '" DEFAULT CHARSET="utf8"' . $tableProperties);
  }
  
  
  
  private function deleteTableSQL($tableName) {
    return $this->rawQuery('DROP TABLE ' . $this->tableQuoteStart . $this->escape($tableName) . $this->tableQuoteEnd);
  }
  
  
  
  private function renameTableSQL($oldName, $newName) {
    return $this->rawQuery('RENAME TABLE ' . $this->tableQuoteStart . $this->escape($oldName) . $this->tableQuoteEnd . ' TO ' . $this->tableQuoteStart . $this->escape($newName) . $this->tableQuoteEnd);
  }
  
  
  
  private function getTableAsArraySQL() {
    switch ($this->language) {
      case 'mysql':
      case 'mysqli':
      case 'postgresql':
      $tables = $this->rawQuery('SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA LIKE "' . $this->escape($this->activeDatabase) . '"');
      $tables = $tables->getAsArray('TABLE_NAME');
      $tables = array_keys($tables);
      break;
    }
    
    return $tables;
  }
  
  
  
  private function selectDatabaseSQL($database) {
    if (!$this->functionMap('selectdb', $database)) { // Select the database.
      $this->error = 'Could not select database: ' . $database;

      return false;
    }
    else {
      if ($this->language == 'mysql' || $this->language == 'mysqli') {
        if (!$this->rawQuery('SET NAMES "utf8"')) { // Sets the database encoding to utf8 (unicode).
          $this->error = 'Could not run SET NAMES query.';

          return false;
        }
      }

      return true;
    }
  }
  
  
  
  private function createDatabaseSQL($database) {
    return $this->rawQuery('CREATE DATABASE IF NOT EXISTS ' . $this->databaseQuoteStart . $database . $this->databaseQuoteEnd);
  }
  
}
?>