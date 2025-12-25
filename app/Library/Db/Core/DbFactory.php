<?php

namespace App\Library\Db\Core;

/**
 * This class provide abstraction layer of database
 * and always create object of type \App\Library\Db\Core\DbInterface
 *
 * Why not using __construct() instead of init()?
 * Because constructors don't return anything
 * and init() return object based on specific database driver selection.
 *
 *
 * Example: How to use this class?
 *
 * ```
 * <?php
 *      $DB = new \App\Library\Db\Core\DbFactory();
 *      $DB = $DB->init($config); // Check init() docblock comments for $config
 *      $query = " select * from users where user_email = :user_email ";
 *      $values = array(':user_email' => 'email@email.com');
 *      $result = $DB->fetchRow($query, $values); // $result is single row array
 * ?>
 * ```
 */
class DbFactory
{
    /**
     * Initialize or create database object based on driver selection
     *
     * // MySQL connection configuration (Laravel style)
     * $config = array( 'connection' => 'mysql');
     *
     * // Or with explicit connection details
     * $config = array( 'driver' => 'mysql',
     * 'connection' => 'mysql');
     *
     * // Oracle connection configuration (Laravel style)
     * $config = array( 'connection' => 'oracle');
     *
     * // Or with explicit driver
     * $config = array( 'driver' => 'oracle',
     * 'connection' => 'oracle');
     *
     * @param  array  $config  Database connection configuration
     *
     * Note: The connection should be configured in config/database.php
     * @return object \App\Library\Db\Core\DbInterface
     *
     * @throws \Exception When invalid driver given in connection configuration
     */
    public function init(array $config)
    {
        // Get driver from config or from Laravel connection
        $connection = $config['connection'] ?? config('database.default');
        $driver = $config['driver'] ?? config("database.connections.{$connection}.driver");

        $driver = strtolower($driver);

        if (in_array($driver, ['oci8', 'oracle'])) {
            return new Oracle\Oracle($config);
        }

        if (in_array($driver, ['mysql', 'pdo_mysql'])) {
            return new MySQL\MySQL($config);
        }

        $drivers = ['mysql', 'pdo_mysql', 'oracle', 'oci8'];
        throw new \Exception('Invalid driver. Driver must be: '.implode(', ', $drivers));
    }
}
