<?php

namespace annotation\validators;
class LessThan extends \annotation\validation\Validation
{
    private $min;

    public function __construct($min, $message)
    {
        parent::__construct($message);
        $this->min = $min;
    }

    public function Validate($value)
    {
        if(!is_numeric($value)){
            return false;
        }
        return empty($value) || ($value < $this->min);
    }
}