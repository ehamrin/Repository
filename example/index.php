<?php
require_once '../autoloader.php';

use \example\UserRepository;

$conn = new \PDO('mysql:host=localhost;dbname=testar;', 'root', '', array(\PDO::FETCH_OBJ));
$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

$ipRepository = new \annotation\repository\PDORepository("\\example\\IPLog", $conn);
$userRepository = new UserRepository($conn);


debug($userRepository->find(11));