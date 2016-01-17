<?php

namespace annotation\validators;
class Numeric extends \annotation\validation\Validation
{
    public function Validate($value)
    {
        return empty($value) || is_numeric($value);
    }
}
