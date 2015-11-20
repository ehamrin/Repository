<?php
require_once '../autoloader.php';

use \example\UserRepository;

$conn = new \PDO('mysql:host=localhost;dbname=testar;', 'root', '', array(\PDO::FETCH_OBJ));
$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$repository = new UserRepository($conn);
/*
 * OR:
 * $repository = new \model\annotation\PDORepository("\\example\\User", $conn);
 */

//$model = $repository->find(19);
//$model->name = "test";
//$repository->save($model);

debug($repository->findAll());
