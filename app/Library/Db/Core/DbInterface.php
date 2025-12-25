<?php

namespace App\Library\Db\Core;

/**
 * Every database type must implements this DbInterface
 */
interface DbInterface
{
    /**
     * Set query log directory path where log files will be saved
     *
     * @return void
     */
    public static function setQueryLogDirPath(string $dirPath);

    /**
     * Start query log for given code chunk key
     *
     * @params string $codeChunk
     *
     * @return void
     */
    public static function startQueryLog(string $codeChunk);

    /**
     * End query log for given code chunk key
     *
     * @params string $codeChunk
     *
     * @return void
     */
    public static function endQueryLog(string $codeChunk);

    /**
     * Get current code chunk
     *
     * @return string|null
     */
    public static function getCurrentCodeChunk();

    /**
     * Check if any code chunk logging is active
     *
     * @return bool
     */
    public static function isCodeChunkLoggingActive();

    /**
     * Get database configurations
     *
     * @param  string  $key  Optional config key
     * @return array | string If key is null then array will return
     *                        If key is given then string will return
     */
    public function getConfig($key = null);

    /**
     * Get database platform name
     *
     * @return string Always return lowercase string
     */
    public function getDbPlatformName();

    /**
     * Disconnect database connection
     *
     * @return void
     */
    public function disconnect();

    /**
     * Generate a cryptographically secure unique ID of the given length.
     *
     * This function tries to use `random_bytes` or `openssl_random_pseudo_bytes`
     * to generate a secure random binary string, which is then converted to a hexadecimal string.
     * If both methods are unavailable, it falls back to generating a pseudo-random hex string.
     *
     * @param  int  $length  Desired length of the unique ID (in characters).
     *                       Must be an even number to ensure full hex output.
     * @return string Hexadecimal unique ID of the specified length.
     */
    public function generateHexId($length);

    /**
     * Convert comma separated ids to unique ids array
     *
     * @param  string  $values  e.g. "1 , 2, 3, 1"
     * @return array e.g. [1, 2, 3]
     */
    public function explodeUniqueIds($values);

    /**
     * Fetch single row using `SELECT` query
     *
     * @param  string  $query  `SELECT` SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is true
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return array Always return an array
     *
     * @throws \Exception fetchRow() function can only used for `SELECT` query
     */
    public function fetchRow($query, array $values = [], $disconnect = true, $debug = false);

    /**
     * Fetch multiple rows using `SELECT` query
     *
     * @param  string  $query  `SELECT` SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is true
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return array Always return an array
     *
     * @throws \Exception fetchRows() function can only used for `SELECT` query
     */
    public function fetchRows($query, array $values = [], $disconnect = true, $debug = false);

    /**
     * Fetch single column using `SELECT` query
     *
     * @param  string  $query  `SELECT` SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is true
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return array Always return an array
     *
     * @throws \Exception fetchRows() function can only used for `SELECT` query
     */
    public function fetchColumn($query, array $values = [], $disconnect = true, $debug = false);

    /**
     * Fetch string value for given key from single row
     *
     * @param  string  $key  Name of key to get value
     * @param  string  $query  `SELECT` SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is true
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return string Value string
     *
     * @throws \Exception fetchRow() function can only used for `SELECT` query
     */
    public function fetchKey($key, $query, array $values = [], $disconnect = true, $debug = false);

    /**
     * To execute `UPDATE` query
     *
     * @param  string  $query  `UPDATE` SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is true
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return int Number of affected rows
     *
     * @throws \Exception update() function can only used for `UPDATE` query
     */
    public function update($query, array $values = [], $disconnect = true, $debug = false);

    /**
     * To execute `DELETE` query
     *
     * @param  string  $query  `DELETE` SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is true
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return int Number of affected rows
     *
     * @throws \Exception delete() function can only used for `DELETE` query
     */
    public function delete($query, array $values = [], $disconnect = true, $debug = false);

    /**
     * To execute SQL query other than `SELECT`, `INSERT`, `UPDATE`, `DELETE`
     * like calling stored procedure
     *
     * @param  string  $query  SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $checkType  Check query type
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is true
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return object $result
     *
     * @throws \Exception For `SELECT` query use any \App\Library\Db\Core\DbInterface::fetch*()
     * @throws \Exception For `INSERT` query use \App\Library\Db\Core\DbInterface::insert()
     * @throws \Exception For `UPDATE` query use \App\Library\Db\Core\DbInterface::update()
     * @throws \Exception For `DELETE` query use \App\Library\Db\Core\DbInterface::delete()
     */
    public function query($query, array $values = [], $checkType = true, $disconnect = true, $debug = false);

    /**
     * To import SQL Script
     *
     * @param string SQL script to execute
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is true
     * @return void Nothing returns
     */
    public function importSQL($query, $disconnect = true);

    /**
     * Get collection of commonly used SQL expressions
     * Each database type will implement accordingly
     *
     * @return object \App\Library\Db\Core\ExpressionInterface
     */
    public function getExpression();

    /**
     * Fetch rows chunk for pagination using `SELECT` query
     *
     * @param  string  $query  `SELECT` SQL query
     * @param  array  $values  Query parameters
     * @param  int  $page_number  Page number starts from 1. Default is 1
     * @param  int  $records_per_page  Records per page. Default is 50 records per page
     * @param  int  $start  Start index for pagination. Default is 0
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is true
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return array Always return an array
     *
     * @throws \Exception fetchRows() function can only used for `SELECT` query
     */
    public function fetchChunk(
        $query,
        array $values = [],
        $page_number = 1,
        $records_per_page = 50,
        $start = 0,
        $disconnect = true,
        $debug = false
    );

    /**
     * Insert data into database
     *
     * @param  string|array  $table  When string it must be table name
     *                               When array it must be like:
     *                               ['table' => 'table-name', 'sequence' => 'sequence-name', 'column' => 'column-name']
     * @param  array  $values  Data to be inserted
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is true
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return int|false Last generated value or false
     *
     * @throws \Exception $values could not be empty for insert() function
     */
    public function insert(
        $table,
        array $values,
        $disconnect = true,
        $debug = false
    );
}
