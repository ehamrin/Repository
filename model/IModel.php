<?php


namespace model;

interface IModel
{
    function isValid();
    function getModelError();
}