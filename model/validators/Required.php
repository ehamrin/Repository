<?php

namespace model\validators;
class Required extends \model\Validation
{
    public function Validate($value)
    {
        return !(is_null($value) || empty(trim($value)));
    }
}