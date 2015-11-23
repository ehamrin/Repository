<?php


namespace model\annotation\repository;


class PDORepositoryFactory
{
    private static $repositories = array();

    /**
     * @param $class
     * @param ...$params
     * @return PDORepository
     */
    public static function get($class, ...$params){
        if(!isset(self::$repositories[$class])){
            self::$repositories[$class] = new PDORepository($class, ...$params);
        }
        return self::$repositories[$class];
    }

    public static function add($class, PDORepository $repo){
        self::$repositories[$class] = $repo;
    }

    private function __construct()
    {
    }


}