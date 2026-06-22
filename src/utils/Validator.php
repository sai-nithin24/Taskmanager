<?php
class Validator
{
    private array $errors = [];
    // rules 
    public function required(string $field, mixed $value): self
    {
        // Treat 0, "0", "", null, and whitespace-only strings as empty
        $strVal = trim((string)$value);
        if (!isset($value) || $strVal === '' || $strVal === '0') {
            $this->errors[$field] = ucfirst($field) . ' is required.';
        }
        return $this;
    }
    public function minLength(string $field, string $value, int $min): self
    {
        if (strlen(trim($value)) < $min) {
            $this->errors[$field] = "Must be at least {$min} characters.";
        }
        return $this;
    }

    public function maxLength(string $field, string $value, int $max): self
    {
        if (strlen(trim($value)) > $max) {
            $this->errors[$field] = "Must be under {$max} characters.";
        }
        return $this;
    }

    public function startsAlphaNum(string $field, string $value): self
    {
        if (!preg_match('/^[a-zA-Z0-9]/', trim($value))) {
            $this->errors[$field] = 'Must start with a letter or number.';
        }
        return $this;
    }

    public function inList(string $field, mixed $value, array $list): self
    {
        if (!in_array($value, $list, true)) {
            $this->errors[$field] = 'Invalid value.';
        }
        return $this;
    }

    // result 

    public function passes(): bool  { return empty($this->errors); }
    public function errors(): array { return $this->errors; }
}