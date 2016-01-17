<?php


namespace annotation\validation;

abstract class Validation implements IValidation
{
    private $message;
    public function __construct($message){
        $this->message = $message;
    }

    public function GetMessage()
    {
        return $this->message;
    }
}