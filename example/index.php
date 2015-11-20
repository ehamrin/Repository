<?php
require_once '../autoloader.php';
require_once 'User.php';
require_once 'UserRepository.php';


$conn = new \PDO('mysql:host=localhost;dbname=testar;', 'root', '', array(\PDO::FETCH_OBJ));
$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$repository = new UserRepository($conn);
$repository->findAll();
