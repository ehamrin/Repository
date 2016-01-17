<?php

namespace annotation\validators;
class MinLength extends \annotation\validation\Validation
{
    private $min;

    public function __construct($min, $message)
    {
        parent::__construct($message);
        $this->min = $min;

    }

    public function Validate($value)
    {
        return empty($value) || (is_string($value) && strlen($value) >= $this->min);
    }
}