<?php

namespace App\Library\Db\Core\Oracle;

use App\Library\Db\Core\DbAbstract;
use App\Library\Db\Core\SQLFragmentInterface;

/**
 * This class implements \App\Library\Db\Core\DbInterface specifically for Oracle
 */
class Oracle extends DbAbstract
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
        return new Expression($this->getPdo(), 'oracle');
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
        @$offset = (($page_number - 1) * $records_per_page) + $start;
        @$length = $offset + $records_per_page;
        @$start = $offset + 1;

        $query = ' SELECT * FROM ( SELECT TEMP_CHUNK_TABLE.*, ROWNUM META_ROWNUM FROM ( '.$query.' ) TEMP_CHUNK_TABLE
                                        WHERE ROWNUM <= '.intval($length).' )
                                WHERE META_ROWNUM >=  '.intval($start);
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

        $sequence = '';
        if (is_array($table)) {
            if (@empty($table['table'])) {
                throw new \Exception('$table must have `table` key for table name');
            }

            if (@empty($table['sequence'])) {
                throw new \Exception('$table must have `sequence` key for sequence name');
            }

            if (@empty($table['column'])) {
                throw new \Exception('$table must have `column` key for column name');
            }

            $sequence = strtoupper($table['sequence']);
            $seq_column = strtoupper($table['column']);
            $table = strtoupper($table['table']);
        }

        if (! is_string($table)) {
            throw new \Exception('$table must be string or array');
        }

        $table = strtoupper($table);
        $keys = array_keys($data);
        if ($keys[0] !== 0) {
            $data = [$data];
        }

        $values = [];
        $last_generated_value = false;

        // Build insert query from array values
        $query = ' INSERT ALL ';
        foreach ($data as $i => $columns) {
            if (! empty($sequence)) {
                $squery = ' SELECT  '.$sequence.'.NEXTVAL FROM DUAL ';
                $seq_value = $this->fetchKey('nextval', $squery);

                $columns[$seq_column] = $seq_value;
                $last_generated_value = $seq_value;
            }

            $query .= ' INTO '.$table.' ( ';
            foreach ($columns as $column => $column_value) {
                $query .= '"'.trim(strtoupper($column)).'", ';
            }
            $query = trim($query, ', ');
            $query .= ' ) VALUES ( ';

            foreach ($columns as $column => $column_value) {
                if ($column_value instanceof SQLFragmentInterface) {
                    $fragment = $column_value->getFragment();

                    // add index to each value
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
        $query .= ' SELECT 1 FROM DUAL ';

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

        return $last_generated_value;
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
        $queries = explode(';', $query);

        $this->reconnectIfNeeded();
        foreach ($queries as $query) {
            $query = trim(str_replace(';', '', $query));
            if (! empty($query)) {
                $this->pdo->exec($query);
            }
        }

        if ($disconnect) { // Disconnect database connection after query execution
            $this->disconnect();
        }
    }
}
