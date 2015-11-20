<?php

/**
 * @Table   ["user"]
 */
class User extends \model\annotation\Model
{
    /**
     * @Primary
     * @Column
     */
    private $id;

    /**
     * @Column
     * @Required    ["You must assign a name"]
     * @MaxLength   [50, "Name must be no longer than 50 characters"]
     */
    public $name;

    /**
     * @Column
     * @Required    ["You must assign a pid"]
     * @MaxLength   [50, "pid must be no longer than 50 characters"]
     */
    public $pid;

    /**
     * @Column
     * @Required    ["You must assign a phone"]
     * @MaxLength   [50, "phone must be no longer than 50 characters"]
     */
    public $phone;

    /**
     * @Column
     * @Required    ["You must assign a email"]
     * @Email       ["Must be a valid email address"]
     * @MaxLength   [50, "email must be no longer than 50 characters"]
     */
    public $email;

    /**
     * @Column
     * @Required    ["You must assign an address"]
     * @MaxLength   [50, "address must be no longer than 50 characters"]
     */
    public $address;

    /**
     * @Column
     * @Required    ["You must assign a city"]
     * @MaxLength   [50, "city must be no longer than 50 characters"]
     */
    public $city;

    /**
     * @Column
     * @Required    ["You must assign a postal"]
     * @MaxLength   [6, "postal must be no longer than 6 characters"]
     * @Regex       ["/^\\d{3}(\\s)?\\d{2}$/", "Must be a valid postal code"]
     * @Numeric     ["postal must be numeric"]
     */
    public $postal;

}