<?php
namespace example;

class UserRepository extends \model\annotation\PDORepository
{
    public function __construct(\PDO $conn)
    {
        parent::__construct('\\example\\User', $conn);
    }

    /**
     * Add you own methods etc. here
     */
}