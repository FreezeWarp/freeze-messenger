<?php
interface DatabaseSQLInterface {
    public function connect($host, $port, $username, $password, $database = false);
    public function getVersion();
    public function getLastError();
    public function close();
    public function selectDatabase($database);
    public function escape($text, $context);
    public function query($rawQuery);
    public function getLastInsertId();
    public function startTransaction();
    public function endTransaction();
    public function rollbackTransaction();
}