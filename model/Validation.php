<?php


namespace model;

abstract class Validation
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