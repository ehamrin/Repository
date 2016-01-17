<?php


namespace annotation\validation;


interface IValidation
{
    function GetMessage();
    function Validate($value);
}