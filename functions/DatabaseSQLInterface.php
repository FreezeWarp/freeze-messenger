<?php
interface DatabaseSQLInterface {
    /**
     * Connect to a remote (or local) database server with a known host, port, username, and password.
     *
     * @param string      $host
     * @param integer     $port
     * @param string      $username
     * @param string      $password
     * @param bool|string $database The database to connect to, or false if no specific database will be accessed.
     *
     * @return mixed Generally, false on failure, or the database connection otherwise.
     */
    public function connect($host, $port, $username, $password, $database = false);

    /**
     * @return mixed The version string of the database connected to.
     */
    public function getVersion();

    /**
     * @return string The last error that occurred. This should, correctly implemented, return the last error even if a query without an error is run.
     */
    public function getLastError();

    /**
     * Close the connection to the database server.
     *
     * @return mixed False on failure; varying query data on success.
     */
    public function close();

    /**
     * Connect to a database after opening a connection to the database server.
     *
     * @param string $database
     *
     * @return mixed False on failure; varying query data on success.
     */
    public function selectDatabase($database);

    /**
     * Escape a given text string for use in a raw database query.
     *
     * @param string $text Text to escape.
     * @param string $context The text's type information, some value from DatabaseTypeType.
     *
     * @return mixed
     */
    public function escape($text, $context);

    /**
     * Send a raw query string to the database connection for execution.
     *
     * @param string $rawQuery
     *
     * @return mixed False on failure; varying query data on success.
     */
    public function query($rawQuery);

    /**
     * Send a raw query string to the database connection for execution, returning a resultset.
     *
     * @param string $rawQuery
     *
     * @return mixed False on failure; DatabaseResultInterface on success.
     */
    public function queryReturningResult($rawQuery);

    /**
     * @return string The last value of a serial column incremented in an INSERT. This should, correctly implemented, return the last serial column value even if a query without a serial column is run.
     */
    public function getLastInsertId();

    /**
     * Begin a transaction. Correctly implemented, this should nest LIFO.
     *
     * @return mixed False on failure; varying query data on success.
     */
    public function startTransaction();

    /**
     * End a transaction. Correctly implemented, this should nest LIFO.
     *
     * @return mixed False on failure; varying query data on success.
     */
    public function endTransaction();

    /**
     * Rollback the last-open transaction. Correctly implemented, this should nest LIFO.
     *
     * @return mixed False on failure; varying query data on success.
     */
    public function rollbackTransaction();
}