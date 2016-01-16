<?php
namespace example;
use model\validators\IP;

/**
 * @Table   ["user"]
 * @ManyToMany ["\\example\\IPLog", "ip", "user_ip"]
 */
class User extends \model\annotation\AnnotationModel
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
    protected $name;

    /**
     * @Column
     * @Required    ["You must assign a pid"]
     * @RegEx       ["/^((19|20)?[0-9]{2})(0[1-9]|1[012])(0[1-9]|[12][0-9]|3[01])(-)?[0-9pPtTfF][0-9]{3}$/", "Must be a valid Swedish personal ID-number"]
     * @MaxLength   [12, "pid must be no longer than 11 characters"]
     */
    protected $pid;

    protected $ip = array();

    /**
     * @Column
     * @Default     ["CURRENT_TIMESTAMP"]
     * @Required    ["You must assign a datetime"]
     * @DbType      ["DateTime"]
     */
    private $created;

    public function addIP(IPLog $log){
        if($this->ip == null){
            $this->ip = array();
        }
        $this->ip[] = $log;
    }

}