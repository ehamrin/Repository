<?php


namespace annotation\repository;


interface IRepository
{
    function find($primary);
    function findAll();
    function paginate($maximumRows, $startRowIndex, &$totalRowCount);
    function save($model);
    function delete($model);
}