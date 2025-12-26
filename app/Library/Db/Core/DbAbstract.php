<?php

namespace App\Library\Db\Core;

use Illuminate\Support\Facades\DB as LaravelDB;
use PDO;
use PDOStatement;

/**
 * This class implements common functions from \App\Library\Db\Core\DbInterface
 * These functions are common in all type of databases. Each specific
 * database type will must extend this class.
 *
 * This class uses Laravel's database connection for all database operations
 *
 * Why abstract class?
 * Don't allow to create an object of this class instead it can be
 * only inherit from base class.
 */
abstract class DbAbstract implements DbInterface
{
    /**
     * Database configurations
     *
     * @var array
     */
    protected $config = [];

    /**
     * @var string Connection name
     */
    protected $connection;

    /**
     * @var array for query logging
     */
    private static array $queryLog = [];

    /**
     * @var string Directory path to save db query log files
     */
    private static string $queryLogDirPath = '';

    /**
     * @var array Stack to manage nested code chunks for query logging
     */
    private static array $codeChunkStack = [];

    /**
     * @var array Logs for each code chunk
     */
    private static array $codeChunkLogs = [];

    /**
     * @var array Counters for each code chunk to create unique query number
     */
    private static array $codeChunkCounters = [];

    /**
     * @param  array  $config  Database connection configuration
     *
     * @see \App\Library\Db\Core\DbFactory::init() docblock comments for more details
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connection = $config['connection'] ?? config('database.default');

        // Test connection
        try {
            LaravelDB::connection($this->connection)->getPdo();
        } catch (\Exception $e) {
            throw new \Exception('Failed to establish database connection: '.$e->getMessage());
        }
    }

    /**
     * Set query log directory path where log files will be saved
     *
     * @return void
     */
    public static function setQueryLogDirPath(string $dirPath)
    {
        self::$queryLogDirPath = $dirPath;
    }

    /**
     * Start query log for given code chunk key
     *
     * @params string $codeChunk
     * Usage e.g.: \App\Library\Db\DB::startQueryLog('code_chunk_1');
     *
     * @return void
     */
    public static function startQueryLog(string $codeChunk)
    {
        // Add to the stack (for nested support)
        self::$codeChunkStack[] = $codeChunk;

        // Initialize log and counter for this code chunk if not exists
        if (! isset(self::$codeChunkLogs[$codeChunk])) {
            self::$codeChunkLogs[$codeChunk] = [];
            self::$codeChunkCounters[$codeChunk] = 0;
        }
    }

    /**
     * End query log for given code chunk key
     *
     * @params string $codeChunk
     * Usage e.g.: \App\Library\Db\DB::endQueryLog('code_chunk_1');
     *
     * @return void
     */
    public static function endQueryLog(string $codeChunk)
    {
        // Remove from stack
        $key = array_search($codeChunk, self::$codeChunkStack);
        if ($key !== false) {
            unset(self::$codeChunkStack[$key]);
            self::$codeChunkStack = array_values(self::$codeChunkStack); // Re-index array
        }

        // Write log file for this code chunk
        if (isset(self::$codeChunkLogs[$codeChunk]) && ! empty(self::$codeChunkLogs[$codeChunk])) {
            self::writeCodeChunkLogFile($codeChunk);
        }
    }

    /**
     * Get current code chunk
     *
     * @return string|null
     */
    public static function getCurrentCodeChunk()
    {
        return ! empty(self::$codeChunkStack) ? end(self::$codeChunkStack) : null;
    }

    /**
     * Check if any code chunk logging is active
     *
     * @return bool
     */
    public static function isCodeChunkLoggingActive()
    {
        return ! empty(self::$codeChunkStack);
    }

    /**
     * Get database configurations
     *
     * @param  string  $key  Optional config key
     * @return array | string If key is null then array will return
     *                        If key is given then string will return
     */
    public function getConfig($key = null)
    {
        if (empty($key)) {
            return $this->config;
        }

        return @$this->config[$key];
    }

    /**
     * Get database platform name
     *
     * @return string Always return lowercase string 'mysql', 'oracle' etc
     */
    public function getDbPlatformName()
    {
        return strtolower(LaravelDB::connection($this->connection)->getDriverName());
    }

    /**
     * Disconnect database connection
     *
     * @return void
     */
    public function disconnect()
    {
        // Laravel manages connections, but we can purge them
        LaravelDB::disconnect($this->connection);
    }

    /**
     * Generate a cryptographically secure unique ID of the given length.
     *
     * This function tries to use `random_bytes` or `openssl_random_pseudo_bytes`
     * to generate a secure random binary string, which is then converted to a hexadecimal string.
     * If both methods are unavailable, it falls back to generating a pseudo-random hex string.
     *
     * @param  int  $length  Desired length of the unique ID (in characters).
     *                       Must be an even number to ensure full hex output.
     * @return string Lower case Hexadecimal unique ID of the specified length.
     */
    public function generateHexId($length)
    {
        // Convert length to number of bytes (2 hex characters = 1 byte)
        $length = intval($length) / 2;
        $random = false;

        // Try using random_bytes if available (preferred for cryptographic randomness)
        if (function_exists('random_bytes')) {
            $random = random_bytes($length);
        }

        // Fallback to openssl_random_pseudo_bytes if random_bytes is not available
        if ($random === false && function_exists('openssl_random_pseudo_bytes')) {
            $random = openssl_random_pseudo_bytes($length);
        }

        // If a secure random string was generated successfully, convert it to hex
        if ($random !== false && strlen($random) === $length) {
            return strtolower(bin2hex($random));
        }

        // Final fallback: generate pseudo-random hex string
        $unique_id = '';
        $characters = '0123456789abcdef';
        for ($i = 0; $i < ($length * 2); $i++) {
            $unique_id .= $characters[rand(0, strlen($characters) - 1)];
        }

        return strtolower($unique_id);
    }

    /**
     * Convert comma separated ids to unique ids array
     *
     * @param  string  $values  e.g. "1 , 2, 3, 1"
     * @return array e.g. [1, 2, 3]
     */
    public function explodeUniqueIds($values)
    {
        if (empty($values)) {
            return [];
        }

        $values = array_unique(explode(',', trim(str_replace([' ', PHP_EOL], '', $values), ',')));

        return $values;
    }

    /**
     * Fetch single row using `SELECT` query
     *
     * @param  string  $query  `SELECT` SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is false
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return array Always return an array
     *
     * @throws \Exception fetchRow() function can only used for `SELECT` query
     */
    public function fetchRow($query, array $values = [], $disconnect = false, $debug = false)
    {
        $type = $this->queryType($query);
        if ($type !== 'SELECT') { // Check query type must be SELECT query
            throw new \Exception('fetchRow() function can only used for `SELECT` query');
        }

        // Validate query parameters each key must be like //:key
        if (! empty($values)) {
            $this->validateValues($values);
        }

        if ($debug) {
            $this->debug($query, $values);
        }

        $startTime = null;
        if (self::isCodeChunkLoggingActive()) {
            $startTime = microtime(true);
        }

        $data = LaravelDB::connection($this->connection)->select($query, $values);
        // Convert stdClass objects to arrays
        $data = json_decode(json_encode($data), true);

        if ($startTime !== null) {
            $endTime = microtime(true);
            $this->logQuery($query, $values, $startTime, $endTime);
        }

        if ($disconnect) { // Disconnect database connection after query execution
            $this->disconnect();
        }

        if (empty($data)) { // Always return an array
            return [];
        }

        $result = $this->formatKeys($data);

        return $result[0];
    }

    /**
     * Fetch multiple rows using `SELECT` query
     *
     * @param  string  $query  `SELECT` SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is false
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return array Always return an array
     *
     * @throws \Exception fetchRows() function can only used for `SELECT` query
     */
    public function fetchRows($query, array $values = [], $disconnect = false, $debug = false)
    {
        $type = $this->queryType($query);
        if ($type !== 'SELECT') { // Check query type must be SELECT query
            throw new \Exception('fetchRows() function can only used for `SELECT` query');
        }

        // Validate query parameters each key must be like //:key
        if (! empty($values)) {
            $this->validateValues($values);
        }

        if ($debug) {
            $this->debug($query, $values);
        }

        $startTime = null;
        if (self::isCodeChunkLoggingActive()) {
            $startTime = microtime(true);
        }

        $data = LaravelDB::connection($this->connection)->select($query, $values);
        // Convert stdClass objects to arrays
        $data = json_decode(json_encode($data), true);

        if ($startTime !== null) {
            $endTime = microtime(true);
            $this->logQuery($query, $values, $startTime, $endTime);
        }

        if ($disconnect) { // Disconnect database connection after query execution
            $this->disconnect();
        }

        if (empty($data)) { // Always return an array
            return [];
        }

        $result = $this->formatKeys($data);

        return $result;
    }

    /**
     * Fetch single column using `SELECT` query
     *
     * @param  string  $query  `SELECT` SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is false
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return array Always return an array
     *
     * @throws \Exception fetchRows() function can only used for `SELECT` query
     */
    public function fetchColumn($query, array $values = [], $disconnect = false, $debug = false)
    {
        $data = $this->fetchRows($query, $values, $disconnect, $debug);

        if (empty($data)) {
            return [];
        }

        $result = [];
        foreach ($data as $k => $v) {
            $v = array_values($v);
            $result[] = $v[0];
        }

        return $result;
    }

    /**
     * Fetch string value for given key from single row
     *
     * @param  string  $key  Name of key to get value
     * @param  string  $query  `SELECT` SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is false
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return string Value string
     *
     * @throws \Exception fetchRow() function can only used for `SELECT` query
     */
    public function fetchKey($key, $query, array $values = [], $disconnect = false, $debug = false)
    {
        $row = $this->fetchRow($query, $values, $disconnect, $debug);

        if (isset($row[$key])) {
            return $row[$key];
        }

        return '';
    }

    /**
     * To execute `UPDATE` query
     *
     * @param  string  $query  `UPDATE` SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is false
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return int Number of affected rows
     *
     * @throws \Exception update() function can only used for `UPDATE` query
     */
    public function update($query, array $values = [], $disconnect = false, $debug = false)
    {
        $type = $this->queryType($query);
        if ($type !== 'UPDATE') { // Check query type must be UPDATE query
            throw new \Exception('update() function can only used for `UPDATE` query');
        }

        // Validate query parameters each key must be like //:key
        if (! empty($values)) {
            $this->validateValues($values);
        }

        if ($debug) {
            $this->debug($query, $values);
        }

        $startTime = null;
        if (self::isCodeChunkLoggingActive()) {
            $startTime = microtime(true);
        }

        $affectedRows = LaravelDB::connection($this->connection)->update($query, $values);

        if ($startTime !== null) {
            $endTime = microtime(true);
            $this->logQuery($query, $values, $startTime, $endTime);
        }

        if ($disconnect) { // Disconnect database connection after query execution
            $this->disconnect();
        }

        return $affectedRows;
    }

    /**
     * To execute `DELETE` query
     *
     * @param  string  $query  `DELETE` SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is false
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return int Number of affected rows
     *
     * @throws \Exception delete() function can only used for `DELETE` query
     */
    public function delete($query, array $values = [], $disconnect = false, $debug = false)
    {
        $type = $this->queryType($query);
        if ($type !== 'DELETE') { // Check query type must be DELETE query
            throw new \Exception('delete() function can only used for `DELETE` query');
        }

        // Validate query parameters each key must be like //:key
        if (! empty($values)) {
            $this->validateValues($values);
        }

        if ($debug) {
            $this->debug($query, $values);
        }

        $startTime = null;
        if (self::isCodeChunkLoggingActive()) {
            $startTime = microtime(true);
        }

        $affectedRows = LaravelDB::connection($this->connection)->delete($query, $values);

        if ($startTime !== null) {
            $endTime = microtime(true);
            $this->logQuery($query, $values, $startTime, $endTime);
        }

        if ($disconnect) { // Disconnect database connection after query execution
            $this->disconnect();
        }

        return $affectedRows;
    }

    /**
     * To execute SQL query other than `SELECT`, `INSERT`, `UPDATE`, `DELETE`
     * like calling stored procedure
     *
     * @param  string  $query  SQL query
     * @param  array  $values  Query parameters
     * @param  bool  $checkType  Check query type
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is false
     * @param  bool  $debug  Don't execute query. Just print it for debugging. Default is false
     * @return PDOStatement $result
     *
     * @throws \Exception For `SELECT` query use any \App\Library\Db\Core\DbInterface::fetch*()
     * @throws \Exception For `INSERT` query use \App\Library\Db\Core\DbInterface::insert()
     * @throws \Exception For `UPDATE` query use \App\Library\Db\Core\DbInterface::update()
     * @throws \Exception For `DELETE` query use \App\Library\Db\Core\DbInterface::delete()
     */
    public function query($query, array $values = [], $checkType = true, $disconnect = false, $debug = false)
    {
        if ($checkType) {
            $type = $this->queryType($query);

            if ($type === 'SELECT') {
                throw new \Exception('For `SELECT` query use any \App\Library\Db\Core\DbInterface::fetch*()');
            }
            if ($type === 'INSERT') {
                throw new \Exception('For `INSERT` query use \App\Library\Db\Core\DbInterface::insert()');
            }
            if ($type === 'UPDATE') {
                throw new \Exception('For `UPDATE` query use \App\Library\Db\Core\DbInterface::update()');
            }
            if ($type === 'DELETE') {
                throw new \Exception('For `DELETE` query use \App\Library\Db\Core\DbInterface::delete()');
            }
        }

        // Validate query parameters each key must be like //:key
        if (! empty($values)) {
            $this->validateValues($values);
        }

        if ($debug) {
            $this->debug($query, $values);
        }

        $startTime = null;
        if (self::isCodeChunkLoggingActive()) {
            $startTime = microtime(true);
        }

        $result = LaravelDB::connection($this->connection)->statement($query, $values);

        if ($startTime !== null) {
            $endTime = microtime(true);
            $this->logQuery($query, $values, $startTime, $endTime);
        }

        if ($disconnect) { // Disconnect database connection after query execution
            $this->disconnect();
        }

        return $result;
    }

    /**
     * Build where clause according to provided filters
     *
     * @param  array  $filters  e.g. ['AND' => [
     *                          ['field' => 'A.ADMIN_ACTIVE_STATUS', 'operator' => '=', 'value' => 1],
     *                          'OR' => [
     *                          ['field' => 'A.ADMIN_USERNAME', 'operator' => '%LIKE%', 'value' => 'john', 'transform' => 'lower'],
     *                          ['field' => 'B.ADMIN_PROFILE_DEPARTMENT', 'operator' => 'LIKE%', 'value' => 'it', 'transform' => 'lower'],
     *                          ['field' => 'B.ADMIN_PROFILE_DEPARTMENT', 'operator' => 'IS NOT NULL'],
     *                          ],],]
     * @param  string  $defaultLogic  AND | OR
     * @param  int  $paramCounter  0
     * @return array ['field1=:field1', [':field1'=>'value1']]
     */
    public function buildWhereClause(array $filters, string $defaultLogic = 'AND', int &$paramCounter = 0): array
    {
        $clauses = [];
        $values = [];

        foreach ($filters as $logic => $conditions) {
            $logicUpper = strtoupper($logic);

            // If the key is numeric, treat it as a single condition (no group)
            if (is_numeric($logic)) {
                $condition = $conditions;
                $operator = trim($condition['operator'] ?? '=');
                $field = $condition['field'] ?? null;
                $value = $condition['value'] ?? null;
                $transform = $condition['transform'] ?? null;

                if (! $field) {
                    continue;
                }

                // Skip if value is null or empty string, but allow 0, false, etc.
                if (($value === null || $value === '') && ! preg_match('/^(IS\s+NOT\s+NULL|IS\s+NULL)$/i', $operator)) {
                    continue;
                }

                $paramBase = ':P'.(++$paramCounter);
                $fieldExpr = $transform === 'lower' ? "LOWER($field)" : $field;

                // Check if operator contains pattern prefix/suffix (e.g., %LIKE%, LIKE%, %LIKE)
                $pattern = '';
                $actualOperator = $operator;

                if (preg_match('/^(%?)(LIKE|NOT\s+LIKE)(%?)$/i', $operator, $matches)) {
                    $prefixPercent = $matches[1];
                    $actualOperator = strtoupper($matches[2]);
                    $suffixPercent = $matches[3];

                    if ($prefixPercent || $suffixPercent) {
                        $pattern = $prefixPercent.'like'.$suffixPercent;
                    }
                } else {
                    $actualOperator = strtoupper($actualOperator);
                }

                switch ($actualOperator) {
                    case 'LIKE':
                    case 'NOT LIKE':
                        if ($value === null) {
                            continue 2;
                        }
                        $val = (string) $value;

                        // Apply pattern based on operator format
                        if ($pattern === '%like%') {
                            $val = "%$val%";
                        } elseif ($pattern === 'like%') {
                            $val = "$val%";
                        } elseif ($pattern === '%like') {
                            $val = "%$val";
                        }

                        if ($transform === 'lower') {
                            $val = strtolower($val);
                        }
                        $clauses[] = "$fieldExpr $actualOperator $paramBase";
                        $values[$paramBase] = $val;
                        break;

                    case 'IN':
                    case 'NOT IN':
                        if (! is_array($value) || empty($value)) {
                            continue 2;
                        }
                        $paramList = [];
                        foreach ($value as $v) {
                            $p = ':P'.(++$paramCounter);
                            $paramList[] = $p;
                            $values[$p] = $v;
                        }
                        $clauses[] = "$fieldExpr $actualOperator (".implode(', ', $paramList).')';
                        break;

                    case 'IS NULL':
                    case 'IS NOT NULL':
                        $clauses[] = "$fieldExpr $actualOperator";
                        break;

                    default:
                        $clauses[] = "$fieldExpr $actualOperator $paramBase";
                        $values[$paramBase] = $value;
                }
            } else {
                // It's a nested group (e.g., "AND" => [...])
                [$nestedClause, $nestedValues] = $this->buildWhereClause($conditions, $logicUpper, $paramCounter);
                if ($nestedClause) {
                    $clauses[] = "($nestedClause)";
                    $values = array_merge($values, $nestedValues);
                }
            }
        }

        $where = implode(" $defaultLogic ", $clauses);

        return [$where, $values];
    }

    /**
     * Get PDO object
     *
     * @return PDO
     */
    protected function getPdo()
    {
        return LaravelDB::connection($this->connection)->getPdo();
    }

    /**
     * Identify query type
     *
     * @param  string  $query  SQL query
     * @return string 'SELECT', 'INSERT', 'UPDATE', 'DELETE'
     */
    protected function queryType($query)
    {
        $query = trim($query);

        if (strpos(strtoupper($query), 'SELECT') === 0) {
            return 'SELECT'; // For SELECT query use all \App\Library\Db\Core\DbInterface::fetch*()
        } elseif (strpos(strtoupper($query), 'INSERT') === 0) {
            return 'INSERT'; // For INSERT query use \App\Library\Db\Core\DbInterface::insert()
        } elseif (strpos(strtoupper($query), 'UPDATE') === 0) {
            return 'UPDATE'; // For UPDATE query use \App\Library\Db\Core\DbInterface::update()
        } elseif (strpos(strtoupper($query), 'DELETE') === 0) {
            return 'DELETE'; // For DELETE query use \App\Library\Db\Core\DbInterface::delete()
        }

        return 'OTHERS';    // For other query use \App\Library\Db\Core\DbInterface::query()
    }

    /**
     * Just for debugging. Don't use this query to execute
     *
     * @param  string  $query  SQL query
     * @param  array  $values  Query parameters
     * @return string Exact SQL query with replaced query parameters
     */
    protected function preparedQuery($query, array $values = [])
    {
        if (empty($values)) { // Nothing to replace in query
            return $query;
        }

        // Replace $values keys in SQL query
        $keys = array_keys($values);
        foreach ($keys as $key) {
            // Do not quote value if its integer
            if (is_int($values[$key])) {
                $value = $values[$key];
            } else {
                $pdo = LaravelDB::connection($this->connection)->getPdo();
                $value = $pdo->quote($values[$key]);
            }

            $query = str_replace($key, $value, $query);
        }

        return $query;
    }

    /**
     * Result array keys must be of same format
     *
     * @param  array  $data  Two dimensional array
     * @return array Two dimensional array
     */
    protected function formatKeys(array $data = [])
    {
        if (empty($data)) {
            return [];
        }

        // Always return lowercase keys in $data array
        $result = array_map(function ($data) {
            $single = [];
            foreach ($data as $k => $v) {
                $column = trim(strtolower($k));
                $single[$column] = $v;
            }

            return $single;
        }, $data);

        return $result;
    }

    /**
     * Validate query parameters each key must be like //:key
     *
     * @param  array  $values  Query parameters
     * @return void
     *
     * @throws \Exception When key not formatted as //:key
     */
    protected function validateValues(array $values = [])
    {
        if (! empty($values)) {
            foreach ($values as $key => $value) {
                // $key must not have space
                if (strpos($key, ' ') !== false) {
                    throw new \Exception('Invalid binding key. Space found in `'.$key.'`');
                }

                // $key must start with :
                if (strpos($key, ':') !== 0) {
                    throw new \Exception('Invalid binding key found `'.$key.'` it must be like `:'.$key.'`');
                }
            }
        }
    }

    /**
     * Pretty print array/object for debug
     *
     * @param  array|object  $params  Array/object to be print
     * @param  bool  $exit  Exit after print
     * @return void
     */
    protected function pr($params, $exit = true)
    {
        echo '<pre>';
        print_r($params);
        echo '</pre>';

        if ($exit == true) {
            exit();
        }
    }

    /**
     * Debugging query with query parameters
     *
     * @param  string  $query  SQL query
     * @param  array  $values  Query parameters
     * @return void
     */
    protected function debug($query, $values = [])
    {
        $array = [];
        $array['prepared_query'] = $this->preparedQuery($query, $values);
        $array['query'] = $query;
        $array['values'] = $values;
        $this->pr($array); // Print $array and exit
    }

    /*
    * Log query for current code chunk
    *
    * @param  string  $codeChunk  Code chunk key
    * @param  string  $query  SQL query
    * @param  array  $values  Query parameters
    * @param  float  $executionTime  Execution time in milliseconds
    * @return void
    */
    private static function logQueryForCodeChunk(string $codeChunk, string $query, array $values, float $executionTime)
    {
        self::$codeChunkCounters[$codeChunk]++;
        $queryType = self::getQueryTypeStatic($query);
        $key = 'query_number_'.self::$codeChunkCounters[$codeChunk].'_'.strtolower($queryType);

        // Clean up query formatting
        $cleanQuery = self::cleanQuery($query);
        $preparedQuery = self::prepareQueryStatic($cleanQuery, $values);

        self::$codeChunkLogs[$codeChunk][$key] = [
            'query' => $cleanQuery,
            'values' => $values,
            'prepared_query' => $preparedQuery,
            'execution_time' => $executionTime,
            'execution_time_ms' => round($executionTime, 2).' milliseconds',
            'timestamp' => date('Y-m-d H:i:s'),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
        ];
    }

    /**
     * Write code chunk log file
     *
     * @param  string  $codeChunk  Code chunk key
     */
    private static function writeCodeChunkLogFile(string $codeChunk)
    {
        $logDirPath = ! empty(self::$queryLogDirPath) ? self::$queryLogDirPath : sys_get_temp_dir();
        $safeCodeChunk = preg_replace('/[^a-zA-Z0-9_-]/', '_', $codeChunk);
        $logFilePath = $logDirPath.DIRECTORY_SEPARATOR.'db_query_log_'.$safeCodeChunk.'_'.date('Y_m_d_H_i_s').'.json';

        file_put_contents($logFilePath, json_encode(self::$codeChunkLogs[$codeChunk], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Clean up query formatting for better readability
     *
     * @param  string  $query  SQL query
     * @return string Cleaned SQL query
     */
    private static function cleanQuery(string $query)
    {
        // Remove new lines, tabs for better readability
        $query = str_replace(["\n", "\r", "\t", PHP_EOL], ' ', $query);

        // Replace multiple spaces with single space
        return preg_replace('/\s+/', ' ', trim($query));
    }

    /**
     * Identify query type in static context
     *
     * @param  string  $query  SQL query
     * @return string 'SELECT', 'INSERT', 'UPDATE', 'DELETE'
     */
    private static function getQueryTypeStatic(string $query)
    {
        $query = trim($query);

        if (strpos(strtoupper($query), 'SELECT') === 0) {
            return 'SELECT';
        } elseif (strpos(strtoupper($query), 'INSERT') === 0) {
            return 'INSERT';
        } elseif (strpos(strtoupper($query), 'UPDATE') === 0) {
            return 'UPDATE';
        } elseif (strpos(strtoupper($query), 'DELETE') === 0) {
            return 'DELETE';
        }

        return 'OTHERS';
    }

    /**
     * Prepare query in static context
     *
     * @param  string  $query  SQL query
     * @param  array  $values  Query parameters
     * @return string Prepared SQL query
     */
    private static function prepareQueryStatic(string $query, array $values = [])
    {
        if (empty($values)) {
            return $query;
        }

        // Simple replacement for static context
        $keys = array_keys($values);
        foreach ($keys as $key) {
            if (is_int($values[$key])) {
                $value = $values[$key];
            } else {
                $value = "'".$values[$key]."'"; // Simple quoting
            }
            $query = str_replace($key, $value, $query);
        }

        return $query;
    }

    /**
     * Log query execution details
     *
     * @param  string  $query  SQL query
     * @param  array  $values  Query parameters
     * @param  float  $startTime  Start time in microseconds
     * @param  float  $endTime  End time in microseconds
     * @return void
     */
    private function logQuery(string $query, array $values, float $startTime, float $endTime)
    {
        $executionTime = ($endTime - $startTime) * 1000; // in milliseconds

        // Check if we should log for code chunks or global logging
        $currentCodeChunk = self::getCurrentCodeChunk();

        // Log for current code chunk
        self::logQueryForCodeChunk($currentCodeChunk, $query, $values, $executionTime);
    }
}
