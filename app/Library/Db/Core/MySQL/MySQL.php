<?php

namespace App\Library\Db\Core\MySQL;

use App\Library\Db\Core\DbAbstract;
use App\Library\Db\Core\SQLFragmentInterface;

/**
 * This class implements \App\Library\Db\Core\DbInterface specifically for MySQL
 */
class MySQL extends DbAbstract
{
    /**
     * @param  array  $config  Database connection configuration
     *
     * @see \App\Library\Db\Core\DbFactory::init() docblock comments for more details
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    /**
     * Get collection of commonly used SQL expressions
     * Each database type will implement accordingly
     *
     * @return object \App\Library\Db\Core\ExpressionInterface
     */
    public function getExpression()
    {
        return new Expression($this->getPdo(), 'mysql');
    }

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
    ) {
        $page_number = (string) $page_number;
        $records_per_page = (string) $records_per_page;
        $start = (string) $start;

        if (! $page_number || ! ctype_digit($page_number) || ! ($page_number > 0)) {
            return [];
        }

        if (! $records_per_page || ! ctype_digit($records_per_page) || ! ($records_per_page > 0)) {
            return [];
        }

        if (! $start || ! ctype_digit($start) || ! ($start > 0)) {
            $start = 0;
        }

        // Calculation
        @$start = (($page_number - 1) * $records_per_page) + $start;
        @$length = $records_per_page;

        $query .= ' LIMIT '.intval($length).' OFFSET  '.intval($start);
        $data = $this->fetchRows($query, $values, $disconnect, $debug);

        return $data;
    }

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
    ) {
        $data = $values;

        if (empty($data)) {
            throw new \Exception('$values could not be empty for insert() function');
        }

        if (! is_string($table)) {
            throw new \Exception('$table name must be string for MySQL');
        }

        $keys = array_keys($data);
        if ($keys[0] !== 0) {
            $data = [$data];
        }
        $row_count = count($data);

        // Build insert query from array values
        $values = [];
        $query = ' INSERT INTO '.$table.' ( ';
        foreach ($data as $i => $columns) {
            if ($i == 0) { // column names only once
                foreach ($columns as $column => $column_value) {
                    $query .= '`'.trim(strtolower($column)).'`, ';
                }

                $query = trim($query, ', ');
                $query .= ' ) VALUES ( ';
            } else {
                $query .= ', ( ';
            }

            foreach ($columns as $column => $column_value) {
                if ($column_value instanceof SQLFragmentInterface) {
                    $fragment = $column_value->getFragment();

                    // Add index to each value
                    foreach ($column_value->getValues() as $k => $v) {
                        $fragment = str_replace($k, $k.'_'.$i, $fragment);
                        $values[$k.'_'.$i] = $v;
                    }
                    $query .= $fragment.', ';
                } else {
                    $query .= ':'.$column.'_'.$i.', ';
                    $values[':'.$column.'_'.$i] = $column_value;
                }
            }
            $query = trim($query, ', ');
            $query .= ' )';
        }

        // Validate query parameters each key must be like :key
        if (! empty($values)) {
            $this->validateValues($values);
        }

        if ($debug) {
            $this->debug($query, $values);
        }

        $this->reconnectIfNeeded();
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($values);

        if ($disconnect) { // Disconnect database connection after query execution
            $this->disconnect();
        }

        $generated_value = $this->pdo->lastInsertId();
        if ($generated_value == 0) {
            return false;
        }

        return $generated_value + ($row_count - 1);
    }

    /**
     * To import SQL Script
     *
     * @param string SQL script to execute
     * @param  bool  $disconnect  Disconnect database connection after query execution. Default is true
     * @return void Nothing returns
     */
    public function importSQL($query, $disconnect = true)
    {
        $this->reconnectIfNeeded();
        $this->pdo->exec($query);

        if ($disconnect) { // Disconnect database connection after query execution
            $this->disconnect();
        }
    }
}
