<?php

namespace App\Library\Validation;

use App\Exceptions\ApiException;

class Validator
{
    public bool $stopOnFirst = true;

    private array $fields = [];

    private array $errors = [];

    private array $values = [];

    public static function create(bool $stopOnFirst = true): self
    {
        $v = new self;
        $v->stopOnFirst = $stopOnFirst;

        return $v;
    }

    public function stopOnFirstError(bool $flag = true): self
    {
        $this->stopOnFirst = $flag;

        return $this;
    }

    public function field(string $name, mixed $value = null): FieldValidator
    {
        $this->values[$name] = $value;
        $field = new FieldValidator($name, $value, $this);
        $this->fields[$name] = $field;

        return $field;
    }

    public function addError(string $field, array $error): void
    {
        // Initialize the field errors array if it doesn't exist
        if (! isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        // Merge new error with existing errors for this field
        $this->errors[$field] = array_merge($this->errors[$field], $error);
    }

    /**
     * Merge external errors into the validator
     *
     * @param  array  $errors  Associative array of errors [field => [errorKey => message]]
     */
    public function mergeErrors(array $errors): self
    {
        foreach ($errors as $fieldName => $fieldErrors) {
            if (! isset($this->errors[$fieldName])) {
                $this->errors[$fieldName] = [];
            }

            if (is_array($fieldErrors)) {
                $this->errors[$fieldName] = array_merge($this->errors[$fieldName], $fieldErrors);
            }
        }

        return $this;
    }

    public function getValue(string $field)
    {
        return $this->values[$field] ?? null;
    }

    public function shouldStop(string $field): bool
    {
        // Stop if stopOnFirst is true AND this specific field already has errors
        return $this->stopOnFirst && isset($this->errors[$field]) && ! empty($this->errors[$field]);
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function validate(string $message = ''): void
    {
        if ($this->hasErrors()) {
            throw new ApiException(422, $message, $this->errors);
        }
    }
}
