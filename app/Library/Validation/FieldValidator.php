<?php

namespace App\Library\Validation;

class FieldValidator
{
    public string $field;

    public mixed $value;

    public Validator $Validator;

    public function __construct(string $field, mixed $value, Validator $Validator)
    {
        $this->field = $field;
        $this->value = $value;
        $this->Validator = $Validator;
    }

    /**
     * Check value is required
     *
     * Example:
     * $Validator->field('field_name', $v)->required('Please enter required field')
     */
    public function required(
        string $message = 'This field is required',
        string $code = 'required'
    ): self {
        if ($this->Validator->shouldStop($this->field)) {
            return $this;
        }

        if (empty($this->value)) {
            $this->add($code, $message);
        }

        return $this;
    }

    /**
     * Check given value is empty
     *
     * Example:
     * $Validator->field('field_name')->isEmpty($value, 'Value is empty')
     */
    public function isEmpty(
        $value,
        string $message = 'Value is empty',
        string $code = 'is_empty'
    ): self {
        if ($this->Validator->shouldStop($this->field)) {
            return $this;
        }

        if (empty($value)) {
            $this->add($code, $message);
        }

        return $this;
    }

    /**
     * Check given value is not empty
     *
     * Example:
     * $Validator->field('field_name')->isNotEmpty($value, 'Value is not empty')
     */
    public function isNotEmpty(
        $value,
        string $message = 'Value is not empty',
        string $code = 'is_not_empty'
    ): self {
        if ($this->Validator->shouldStop($this->field)) {
            return $this;
        }

        if (! empty($value)) {
            $this->add($code, $message);
        }

        return $this;
    }

    /**
     * Validate email format
     *
     * Example:
     * $Validator->field('field_name', $v)->email('Please enter valid email address')
     */
    public function email(
        string $message = 'Invalid email format',
        string $code = 'email'
    ): self {
        if ($this->Validator->shouldStop($this->field)) {
            return $this;
        }

        if (! filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
            $this->add($code, $message);
        }

        return $this;
    }

    /**
     * Check maximum length
     *
     * Example:
     * $Validator->field('field_name', $v)->maxLength(100)
     */
    public function maxLength(int $max,
        ?string $message = null,
        string $code = 'max_length'
    ): self {
        if ($this->Validator->shouldStop($this->field)) {
            return $this;
        }

        if (strlen((string) $this->value) > $max) {
            $this->add($code, $message ?? "Maximum {$max} characters allowed");
        }

        return $this;
    }

    /**
     * Check minimum length
     *
     * Example:
     * $Validator->field('field_name', $v)->minLength(3)
     */
    public function minLength(
        int $min,
        ?string $message = null,
        string $code = 'min_length'
    ): self {
        if ($this->Validator->shouldStop($this->field)) {
            return $this;
        }

        if (strlen((string) $this->value) < $min) {
            $this->add($code, $message ?? "Minimum {$min} characters required");
        }

        return $this;
    }

    /**
     * Value must be from allowed list
     *
     * $Validator->field('field_name', $v)
     *           ->in(['0', '1'], true, 'Value must be from list')
     *
     * @param  array  $allowed  list of allowed values
     */
    public function in(
        array $allowed,
        bool $strict = true,
        string $message = 'Select value from list',
        string $code = 'in'
    ): self {
        if ($this->Validator->shouldStop($this->field)) {
            return $this;
        }

        if (! in_array($this->value, $allowed, $strict)) {
            $this->add($code, $message);
        }

        return $this;
    }

    /**
     * Validate Captcha code
     *
     * @param  string  $captcha_code  Captcha code to verify
     * @param  array  $captcha  Captcha record fetched from DB
     */
    public function validateCaptcha(
        string $captcha_code,
        array $captcha
    ): self {
        if ($this->Validator->shouldStop($this->field)) {
            return $this;
        }

        if (empty($captcha)) {
            $this->add('not_found', 'Captcha not found, Please try again');

            return $this;
        }

        if ((strtolower($captcha_code) !== strtolower($captcha['captcha_code']) ||
             $captcha['captcha_ip_address'] !== @$_SERVER['REMOTE_ADDR'] ||
             $captcha['captcha_user_agent'] !== @$_SERVER['HTTP_USER_AGENT'])
        ) {
            $this->add('invalid', 'Invalid captcha code, Please try again');

            return $this;
        }

        if (time() > strtotime($captcha['captcha_expired_at'])) {
            $this->add('expired', 'Captcha expired, Please try again');

            return $this;
        }

        return $this;
    }

    /**
     * Value must be same as other_field_value
     *
     * $Validator->field('field_name', $v)
     *           ->sameAs('other_field_value', true, 'Value must be same')
     */
    public function sameAs(
        string $other_field_value,
        bool $strict = true,
        string $message = 'Value must be same',
        string $code = 'same_as'
    ): self {
        if ($this->Validator->shouldStop($this->field)) {
            return $this;
        }

        $isSame = $strict
        ? ($this->value === $other_field_value)
        : ($this->value == $other_field_value);

        if (! $isSame) {
            $this->add($code, $message);
        }

        return $this;
    }

    /**
     * Value must be different from other_field_value
     *
     * $Validator->field('field_name', $v)
     *           ->different('other_field_value', false, 'Value must be different')
     */
    public function different(
        string $other_field_value,
        bool $strict = true,
        string $message = 'Value must be different',
        string $code = 'different'
    ): self {
        if ($this->Validator->shouldStop($this->field)) {
            return $this;
        }

        $isSame = $strict
        ? ($this->value === $other_field_value)
        : ($this->value == $other_field_value);

        if ($isSame) {
            $this->add($code, $message);
        }

        return $this;
    }

    /**
     * Validate currency code (3-letter uppercase)
     *
     * Example:
     * $Validator->field('currency', $v)->currencyCode('Invalid currency code')
     */
    public function currencyCode(
        string $message = 'Currency must be a 3-letter code (e.g., USD, EUR)',
        string $code = 'currency_code'
    ): self {
        if ($this->Validator->shouldStop($this->field)) {
            return $this;
        }

        if (! preg_match('/^[A-Z]{3}$/', $this->value)) {
            $this->add($code, $message);
        }

        return $this;
    }

    private function add(string $code, string $message): void
    {
        // Normalize whitespace
        $message = trim($message);

        // Ensure exactly one trailing period
        $message = rtrim($message, '.').'.';

        $this->Validator->addError($this->field, [$code => $message]);
    }
}
