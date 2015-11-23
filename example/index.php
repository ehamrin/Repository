<?php
require_once '../autoloader.php';

use \example\UserRepository;

$conn = new \PDO('mysql:host=localhost;dbname=testar;', 'root', '', array(\PDO::FETCH_OBJ));
$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);


$userRepository = new UserRepository($conn);

/*
if(!$userRepository->save($model)){
  debug($model->getModelError());
}
*/
debug($userRepository->findAll());