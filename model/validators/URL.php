<?php

namespace model\validators;

class URL extends \model\Validation
{
    public function Validate($value)
    {
        return empty($value) || filter_var($value, FILTER_VALIDATE_URL);
    }
}