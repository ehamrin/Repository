<?php
require_once '../autoloader.php';

use \example\UserRepository;

$conn = new \PDO('mysql:host=localhost;dbname=testar;', 'root', '', array(\PDO::FETCH_OBJ));
$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$repository = new UserRepository($conn);
/*
 * OR:
 * $repository = new \model\annotation\repository\PDORepository("\\example\\User", $conn);
 */


$model = new \example\User();
$model->name = "test";
$model->pid = "900101-0101";
/*
if(!$repository->save($model)){
  debug($model->getModelError());
}
*/

debug($repository->findAll());
