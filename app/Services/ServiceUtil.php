<?php

namespace App\Services;

class ServiceUtil
{
    /**
     * All successful responses should be consistent and returned through this function
     *
     * @return array response payload
     */
    public function success(array $data = [], int $code = 200)
    {
        return [
            'status' => 'success',
            'code' => $code,
            'data' => $data,
        ];
    }
}
