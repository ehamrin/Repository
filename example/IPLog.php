<?php
namespace example;
/**
 * @Table ["log"]
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

    /**
     * @Column
     * @Required    ["Must have a user"]
     * @var \example\User
     * @MappedBy ["id"]
     */
    public $user;

}