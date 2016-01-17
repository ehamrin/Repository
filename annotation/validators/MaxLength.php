<?php

namespace annotation\validators;
class MaxLength extends \annotation\validation\Validation
{
    private $max;

    public function __construct($max, $message)
    {
        parent::__construct($message);
        $this->max = $max;
    }

    public function Validate($value)
    {
        return empty($value) || (is_string($value) && strlen($value) <= $this->max);
    }
}