<?php

namespace annotation\validators;

class URL extends \annotation\validation\Validation
{
    public function Validate($value)
    {
        return empty($value) || filter_var($value, FILTER_VALIDATE_URL);
    }
}