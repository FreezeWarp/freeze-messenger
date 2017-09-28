<?php
require_once(__DIR__ . '/DatabaseManualInsertIDTrait.php');
require_once(__DIR__ . '/DatabaseDefinitionsMySQL.php');

trait DatabasePDOTrait {
    use DatabaseManualInsertIDTrait;

    /**
     * @var PDO
     */
    public $connection;

    /**
     * @var string An error string registered on connection failure.
     */
    public $connectionError;

    public function getVersion() {
        return $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function getLastError() {
        return $this->connectionError ?: $this->connection->errorInfo()[2];
    }

    public function close() {
        unset($this->connection);
        return true;
    }

    public function escape($text, $context) {
        switch ($context) {
            case DatabaseTypeType::integer:
            case DatabaseTypeType::timestamp:
            case DatabaseTypeType::bitfield:
            case DatabaseTypeType::float:
            case DatabaseTypeType::blob:
            case DatabaseTypeType::string:
                $this->preparedParams[] = $text;
                return '?';
            break;

            case DatabaseTypeType::search:
                $this->preparedParams[] = '%' . $text . '%';
                return '?';
            break;
        }

        return $text;
    }

    public function query($rawQuery) {
        $query = $this->connection->prepare($rawQuery);
        try {
            if (!$query->execute($this->preparedParams)) {
                $this->preparedParams = [];
                return false;
            }

            $this->preparedParams = [];
            $this->incrementLastInsertId($this->connection->lastInsertId());
            return $query;
        } catch (Exception $ex) {
            $query->debugDumpParams();
            var_dump($ex, $rawQuery, $this->preparedParams);

            $this->preparedParams = [];
            return false;
        }
    }

    public function startTransaction() {
        $this->connection->beginTransaction();
    }

    public function endTransaction() {
        $this->connection->commit();
    }

    public function rollbackTransaction() {
        $this->connection->rollBack();
    }
}
?>