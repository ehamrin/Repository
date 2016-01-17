<?php

namespace annotation\validators;
class Required extends \annotation\validation\Validation
{
    public function Validate($value)
    {
        return is_object($value) || !(is_null($value) || empty(trim($value)));
    }
}