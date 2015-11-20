<?php

namespace model\validators;
class LessThanEqual extends \model\Validation
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

        return empty($value) || ($value <= $this->min);
    }
}