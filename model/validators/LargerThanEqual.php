<?php
namespace model\validators;

class LargerThanEqual extends \model\Validation
{
    private $max;

    public function __construct($max, $message)
    {
        parent::__construct($message);
        $this->max = $max;
    }

    public function Validate($value)
    {
        if(!is_numeric($value)){
            return false;
        }

        return empty($value) || ($value >= $this->max);
    }
}