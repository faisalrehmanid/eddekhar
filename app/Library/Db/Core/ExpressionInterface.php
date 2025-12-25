<?php

namespace App\Library\Db\Core;

/**
 * Collection of commonly used SQL Expressions
 *
 * Rules:
 * Use get prefix if function only valid for `SELECT` query
 * Use set prefix if function only valid for `INSERT`, `UPDATE`, `DELETE` query
 * Don't use any prefix if function valid for `SELECT`, `INSERT`, `UPDATE`, `DELETE` query
 */
interface ExpressionInterface
{
    /**
     * Use this function for `SELECT`, `UPDATE`, `DELETE` query to
     * prevent SQL Injection for IN Clause
     *
     * @param  array  $array  Single dimension array like ['value-1', 'value-2', ...]
     * @return object \App\Library\Db\Core\SQLFragmentInterface
     */
    public function in(array $array = []);

    /**
     * Use this function for `SELECT` query to get datetime from database in `Y-m-d H:i:s` format
     *
     * @param  string  $column  name like: table.column, column
     * @param  bool  $time  if true then it will return date with time. Default is true
     * @return string Formatted SQL query fragment
     *
     * @throws \Exception Symbol quote identifier not allowed in column name
     * @throws \Exception Space not allowed in column name
     * @throws \Exception column name could not be empty
     */
    public function getDate($column, $time = true);

    /**
     * Use this function for `INSERT`, `UPDATE` query to set datetime in database
     *
     * @param  string  $value  Format must be: `Y-m-d H:i:s` or `Y-m-d`
     * @return object \App\Library\Db\Core\SQLFragmentInterface
     *
     * @throws \Exception Invalid datetime format it must be: `Y-m-d H:i:s` or `Y-m-d`
     */
    public function setDate($value = '');

    /**
     * Use this function for `SELECT` query to get UUID from database
     *
     * @param  string  $column  name like: table.column, column
     * @return string Formatted SQL query fragment
     *
     * @throws \Exception Symbol quote identifier not allowed in column name
     * @throws \Exception Space not allowed in column name
     * @throws \Exception column name could not be empty
     */
    public function getUuid($column);

    /**
     * Use this function for `INSERT`, `UPDATE` query to set UUID in database
     *
     * @param  string  $value
     * @return object \App\Library\Db\Core\SQLFragmentInterface
     */
    public function setUuid($value);
}
