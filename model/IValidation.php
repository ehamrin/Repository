<?php


namespace model;


interface IValidation
{
    function GetMessage();
    function Validate($value);
}