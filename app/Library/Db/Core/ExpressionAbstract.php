<?php

namespace App\Library\Db\Core;

use PDO;

/**
 * This class implements common functions from \App\Library\Db\Core\ExpressionInterface
 * These functions are common in all type of databases. Each specific
 * database expression class must extend this class.
 *
 * Why abstract class?
 * Don't allow to create an object of this class instead it can be
 * only inherit from base class.
 */
abstract class ExpressionAbstract implements ExpressionInterface
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var string Database driver name
     */
    protected $driver;

    public function __construct(PDO $pdo, string $driver)
    {
        $this->pdo = $pdo;
        $this->driver = $driver;
    }

    /**
     * Use this function for `SELECT`, `UPDATE`, `DELETE` query to
     * prevent SQL Injection for IN Clause
     *
     * @param  array  $array  Single dimension array like ['value-1', 'value-2', ...]
     * @return object \App\Library\Db\Core\SQLFragmentInterface
     */
    public function in(array $array = [])
    {
        if (empty($array)) {
            return new SQLFragment('', []);
        }

        $keys = [];
        $values = [];
        foreach ($array as $k => $value) {
            $key = $this->generateUniqueKey($k);
            $keys[] = $key;
            $values[$key] = $value;
        }
        $keys = implode(', ', $keys);

        $fragment = ' IN ('.$keys.') ';

        return new SQLFragment($fragment, $values);
    }

    /**
     * Get PDO object
     *
     * @return PDO
     */
    protected function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Validate and format database column name
     *
     * @param  string  $column  name like: table.column, column
     * @return string Formatted column name
     *
     * @throws \Exception Symbol quote identifier not allowed in column name
     * @throws \Exception Space not allowed in column name
     * @throws \Exception column name could not be empty
     */
    protected function validateAndFormatColumnName($column)
    {
        if (empty($column)) {
            throw new \Exception('Column name could not be empty');
        }

        // Get quote symbol based on driver
        $symbol = $this->getQuoteIdentifierSymbol();

        // Quote not allowed
        if (strpos($column, $symbol) !== false) {
            throw new \Exception('Symbol '.$symbol.' not allowed in column name');
        }

        // Space not allowed
        if (strpos($column, ' ') !== false) {
            throw new \Exception('Space not allowed in column name');
        }

        return $this->quoteIdentifierInFragment($column);
    }

    /**
     * Get quote identifier symbol based on database driver
     *
     * @return string
     */
    protected function getQuoteIdentifierSymbol()
    {
        switch ($this->driver) {
            case 'mysql':
                return '`';
            case 'oracle':
                return '"';
            default:
                return '"';
        }
    }

    /**
     * Quote identifier in fragment
     *
     * @param  string  $identifier
     * @return string
     */
    protected function quoteIdentifierInFragment($identifier)
    {
        $symbol = $this->getQuoteIdentifierSymbol();
        $parts = explode('.', $identifier);
        $quotedParts = [];

        foreach ($parts as $part) {
            $part = trim($part);
            // Remove existing quotes if any
            $part = trim($part, $symbol);
            $quotedParts[] = $symbol.$part.$symbol;
        }

        return implode('.', $quotedParts);
    }

    /**
     * Generate unique key
     *
     * @param  string  $value
     * @return string Unique key with :colon
     */
    protected function generateUniqueKey($value = '')
    {
        // Prefix with :key_ because md5() return Hex value which create problems
        $key = ':key_'.md5(uniqid().$value.microtime());

        // Should not more than 16 char otherwise create Exception: Identifier is too long
        return substr($key, 0, 16);
    }
}
