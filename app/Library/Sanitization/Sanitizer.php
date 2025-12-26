<?php

namespace App\Library\Sanitization;

class Sanitizer
{
    private mixed $value;

    private bool $applyTrim = true;

    private bool $applyLower = false;

    private bool $applyUpper = false;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function value(mixed $value): self
    {
        if (is_array($value) || is_object($value)) {
            $value = '';
        }

        $value = (string) $value;

        return new self($value);
    }

    // Enable/Disable trim
    public function trim(bool $flag = true): self
    {
        $this->applyTrim = $flag;

        return $this;
    }

    // Convert to lowercase
    public function lower(bool $flag = true): self
    {
        if ($flag) {
            $this->applyLower = true;
            $this->applyUpper = false;
        } else {
            $this->applyLower = false;
        }

        return $this;
    }

    // Convert to uppercase
    public function upper(bool $flag = true): self
    {
        if ($flag) {
            $this->applyUpper = true;
            $this->applyLower = false;
        } else {
            $this->applyUpper = false;
        }

        return $this;
    }

    // Sanitize as numeric digits (only numeric digits, no decimal points or chars)
    public function digits($default = null): ?int
    {
        $v = (string) $this->value;

        if ($this->applyTrim) {
            $v = trim($v);
        }

        // Check if all characters are digits using ctype_digit
        if ($v === '' || ! ctype_digit($v)) {
            return $default;
        }

        return (int) $v;
    }

    // Sanitize as float (numeric with optional decimal point)
    public function float($default = null): ?float
    {
        $v = (string) $this->value;

        if ($this->applyTrim) {
            $v = trim($v);
        }

        // Check if the value is numeric and contains at least one dot
        if ($v === '' || ! is_numeric($v) || strpos($v, '.') === false) {
            return $default;
        }

        return (float) $v;
    }

    // Sanitize as string
    public function string($default = null): string
    {
        $v = (string) $this->value;

        if ($this->applyTrim) {
            $v = trim($v);
        }

        if ($this->applyLower) {
            $v = mb_strtolower($v, 'UTF-8');
        } elseif ($this->applyUpper) {
            $v = mb_strtoupper($v, 'UTF-8');
        }

        return ($v === '' && $default !== null) ? (string) $default : $v;
    }
}
