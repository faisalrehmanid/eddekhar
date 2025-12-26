<?php

/**
 * Pretty print array/object to debug
 *
 * @param  array | object  $params  Array/object to be print
 * @param  bool  $exit  Exit after print
 * @return void
 */
if (! function_exists('pr')) {
    function pr($params, $exit = true)
    {
        echo '<pre>';
        print_r($params);
        echo '</pre>';

        if ($exit == true) {
            exit();
        }
    }
}
