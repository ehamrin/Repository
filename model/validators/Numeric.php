<?php

namespace model\validators;
class Numeric extends \model\Validation
{
    public function Validate($value)
    {
        return empty($value) || is_numeric($value);
    }
}
