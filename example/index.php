<?php
require_once '../autoloader.php';

use \example\UserRepository;

$conn = new \PDO('mysql:host=localhost;dbname=testar;', 'root', '', array(\PDO::FETCH_OBJ));
$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$ipRepository = new \model\annotation\repository\PDORepository("\\example\\IPLog", $conn);
$userRepository = new UserRepository($conn);
/*
$model = new \example\User();
$model->name = "Testkalle";
$model->pid = "910101-01" . rand(10, 99);

$i = 0;
while($i < 10){
    $ip = $ipRepository->find(rand(1, 60));
    if($ip != null){
        $model->addIP($ip);
        $i++;
    }
}

debug($model);

if(!$userRepository->save($model)){
  debug($model->getModelError());
}
*/
debug($ipRepository->findAll());