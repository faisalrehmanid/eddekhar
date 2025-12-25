<?php

namespace App\Library\Db\Core;

/**
 * SQL fragment is small part of SQL query string
 *
 * This class is use when SQL query fragment have parameters to be bound
 * It return query string fragment with values in order to prevent SQL injection
 */
class SQLFragment implements SQLFragmentInterface
{
    /**
     * @var string
     */
    protected $fragment;

    /**
     * @var array
     */
    protected $values;

    /**
     * @param  string  $fragment
     */
    public function __construct($fragment, array $values = [])
    {
        $this->fragment = $fragment;
        $this->values = $values;
    }

    /**
     * Get SQL query fragment
     *
     * @return string SQL query fragment
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Get SQL query fragment parameters to be bound
     *
     * @return array SQL query fragment parameters
     */
    public function getValues()
    {
        return $this->values;
    }
}
