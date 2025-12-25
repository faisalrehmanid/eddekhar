<?php

namespace App\Library\Db\Core;

interface SQLFragmentInterface
{
    /**
     * Get SQL query fragment
     *
     * @return string SQL query fragment
     */
    public function getFragment();

    /**
     * Get SQL query fragment parameters to be bound
     *
     * @return array SQL query fragment parameters
     */
    public function getValues();
}
