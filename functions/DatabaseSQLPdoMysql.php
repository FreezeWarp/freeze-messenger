<?php
require_once(__DIR__ . '/DatabaseReconnectOnSelectDatabaseTrait.php');
require_once(__DIR__ . '/DatabasePDOTrait.php');

class DatabaseSQLPdoMysql extends DatabaseDefinitionsMySQL {
    use DatabasePDOTrait, DatabaseReconnectOnSelectDatabaseTrait;

    public $tableQuoteStart = '';
    public $tableQuoteEnd = '';
    public $tableAliasQuoteStart = '';
    public $tableAliasQuoteEnd = '';
    public $columnQuoteStart = '';
    public $columnQuoteEnd = '';
    public $columnAliasQuoteStart = '';
    public $columnAliasQuoteEnd = '';
    public $databaseQuoteStart = '';
    public $databaseQuoteEnd = '';
    public $databaseAliasQuoteStart = '';
    public $databaseAliasQuoteEnd = '';
    public $stringQuoteStart = '';
    public $stringQuoteEnd = '';

    /**
     * @var string This is handled by escape() instead.
     */
    public $stringFuzzy = '';


    public $preparedParams = [];

    public function connect($host, $port, $username, $password, $database = false) {
        // keep the user and password in memory to allow for reconnects with selectdb
        $this->connectionUser = $username;
        $this->connectionPassword = $password;

        try {
            $this->connection = new PDO("mysql:" . ($database ? "dbname=$database" : '') . ";host=$host:$port", $username, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->registerConnection($host, $port, $username, $password);
        } catch (PDOException $e) {
            $this->connectionError = $e->getMessage();
            return false;
        }

        return $this->connection;
    }


}