<?php

namespace model\validators;
class Email extends \model\Validation
{
    public function Validate($value)
    {
        return empty($value) || filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}