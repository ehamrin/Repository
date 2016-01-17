<?php


namespace annotation\model;

interface IModel
{
    function isValid();
    function getModelError();
}