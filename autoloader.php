<?php
ini_set('display_errors', TRUE);
error_reporting(E_ALL);

function debug($data){
    echo '<div class="info debugging"><pre>';
    var_dump($data);
    echo '</pre></div>';
}

spl_autoload_register(function ($class) {
    $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);

    //MVC structure
    $filename = __DIR__ . DIRECTORY_SEPARATOR . $class . '.php';
    if(file_exists($filename)){
        require_once $filename;
    }
});