<?php
require_once(__DIR__ . '/DatabaseDefinitionsMySQL.php');
require_once(__DIR__ . '/DatabaseManualInsertIDTrait.php');

class DatabaseSQLMysqli extends DatabaseDefinitionsMySQL {
    use DatabaseManualInsertIDTrait;

    /**
     * @var mysqli
     */
    public $connection;

    public function connect($host, $port, $username, $password, $database = false) {
        $this->connection = new mysqli($host, $username, $password, $database ?: null, (int) $port);

        return $this->connection->connect_error ? false : $this->connection;
    }

    public function getVersion() {
        return $this->connection->server_info;
    }

    public function getLastError() {
        return $this->connection->connect_errno ?: $this->connection->error;
    }

    public function close() {
        if ($this->connection) {
            $function = $this->connection->close();
            unset($this->connection);
            return $function;
        }
        else {
            return true;
        }
    }

    public function selectDatabase($database) {
        return $this->connection->select_db($database);
    }

    public function escape($text, $context) {
        /*$this->preparedParams[] = $text;

        switch ($context) {
            case DatabaseTypeType::integer:
            case DatabaseTypeType::timestamp:
            case DatabaseTypeType::bitfield:
                $this->preparedParamTypes[] = 'i';
            break;

            case DatabaseTypeType::float:
                $this->preparedParamTypes[] = 'd';
            break;

            case DatabaseTypeType::blob:
                $this->preparedParamTypes[] = 'b';
            break;

            case DatabaseTypeType::string:
            case DatabaseTypeType::search:
                $this->preparedParamTypes[] = 's';
                break;

            default:
                return $this->connection->real_escape_string($text);
                break;
        }

        return '?';*/

        return $this->connection->real_escape_string($text);
    }

    public function query($rawQuery) {
        /*if (true || count($this->preparedParams) > 0) {
            $query = $this->connection->prepare($rawQuery);

            if (!$query) {
                var_dump($rawQuery); die();
                return false;
            }

            while (count($this->preparedParams) > 0) {
                $query->bind_param(array_pop($this->preparedParamTypes), array_pop($this->preparedParams));
            }
            $query->execute();

            return $query->get_result() || !((bool) $this->connection->errno);
        }
        else {
            return $this->connection->query($rawQuery);
        }
        $query->close();*/

        $query = $this->connection->query($rawQuery);
        $this->incrementLastInsertId($this->connection->insert_id);

        return $query;
    }

    public function startTransaction() {
        $this->connection->autocommit(false); // Use start_transaction in PHP 5.5 TODO
    }

    public function endTransaction() {
        $this->connection->commit();
        $this->connection->autocommit(true);
    }

    public function rollbackTransaction() {
        $this->connection->rollback();
        $this->connection->autocommit(true);
    }
}