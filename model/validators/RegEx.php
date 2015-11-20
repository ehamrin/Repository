<?php

namespace model\validators;
class RegEx extends \model\Validation
{
    const SWEDISH_PID = "/^^((19|20)?[0-9]{2})(0[1-9]|1[012])(0[1-9]|[12][0-9]|3[01])(-)?[0-9pPtTfF][0-9]{3}$/";
    // 19930730-1234, 930730-1234, 930730-P123(foreigners)

    const US_SOCIAL_SECURITY = "/^([0-9]{3}[-]*[0-9]{2}[-]*[0-9]{4})*$/";

    const SWEDISH_POSTAL_CODE = "/^\d{3}(\s)?\d{2}$/";
    // 12345, 123 45

    const DATE = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";
    // YYYY-MM-DD

    const HEXA_DECIMAL = "/^#?([a-f0-9]{6}|[a-f0-9]{3})$/";
    // #a3c113

    const CREDIT_CARD = "/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|622((12[6-9]|1[3-9][0-9])|([2-8][0-9][0-9])|(9(([0-1][0-9])|(2[0-5]))))[0-9]{10}|64[4-9][0-9]{13}|65[0-9]{14}|3(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})*$/";

    private $regex;

    public function __construct($regex, $message)
    {
        parent::__construct($message);
        $this->regex = $regex;
    }

    public function Validate($value)
    {
        return empty($value) || preg_match($this->regex , $value);
    }
}