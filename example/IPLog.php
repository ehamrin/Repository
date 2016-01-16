<?php
namespace example;
/**
 * @Table ["log"]
 * @ManyToMany ["\\example\\User", "users", "user_ip"]
 */
class IPLog extends \model\annotation\AnnotationModel
{
    /**
     * @Primary
     * @Column
     */
    private $id;

    /**
     * @Column
     * @Required    ["Must enter an IP"]
     * @MaxLength   [50, "IP too long"]
     * @Unique      ["Must be unique"]
     */
    public $address;

    public $users;

}