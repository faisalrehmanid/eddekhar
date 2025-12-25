<?php

namespace App\Exceptions;

class ApiException extends \Exception
{
    protected $code = 400;

    protected $message = '';

    protected $errors = [];

    protected $id = '';

    public function __construct(
        $code,
        $message,
        array $errors = [],
        $id = ''
    ) {
        if (
            $code == 422 &&
            empty($message)
        ) {
            $message = 'Please correct highlighted errors.';
        }

        // Normalize whitespace
        $message = trim($message);

        // Ensure exactly one trailing period
        $message = rtrim($message, '.').'.';

        $this->code = (int) $code;
        $this->message = (string) $message;
        $this->errors = $errors;
        $this->id = (string) $id;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getId()
    {
        return $this->id;
    }

    public function toArray()
    {
        return [
            'status' => 'error',
            'code' => $this->code,
            'message' => $this->message,
            'errors' => $this->errors,
            'id' => $this->id,
        ];
    }
}
