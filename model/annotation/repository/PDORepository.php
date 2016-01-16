<?php


namespace model\annotation\repository;


use model\annotation\AnnotationModel;
use model\IRepository;
use model\annotation\DocBlockReader;

class PDORepository extends AnnotationRepository implements IRepository
{

    private static $_objects = array();

    private static $deleteOnMapMismatch = true;
    private static $checkTableOnConstruct = true;
    private static $updateIndexOnConstruct = true;

    public function __construct($className, \PDO $conn)
    {
        parent::__construct($className);

        if(!isset(self::$_objects[$this->tableName])){
            self::$_objects[$this->tableName] = array();
        }

        $this->db = $conn;

        if(self::$checkTableOnConstruct){
            $this->checkTable();
        }
        PDORepositoryFactory::add($this->model, $this);
    }

    /**
     * @param $primary
     * @return AnnotationModel
     */
    public function find($primary)
    {
        if (!isset(self::$_objects[$this->tableName][$primary])) {
            $stmt = $this->db->prepare("SELECT * FROM $this->tableName WHERE $this->primaryKey = :primary LIMIT 1");
            $stmt->execute(array(
                'primary' => $primary
            ));

            if (!$stmt->rowCount()) {
                return null;
            }
            $obj = $stmt->fetchObject($this->model);
            $this->mapObject($obj);
        }
        return self::$_objects[$this->tableName][$primary];
    }


    /**
     * @return AnnotationModel[]
     */
    public function findAll()
    {

        $stmt = $this->db->prepare("SELECT * FROM $this->tableName");
        $stmt->execute();
        while ($obj = $stmt->fetchObject($this->model)) {
            if (!isset(self::$_objects[$this->tableName][$this->getPrimaryValue($obj)])) {
                $this->mapObject($obj);
            }
        }
        return self::$_objects[$this->tableName];
    }

    /**
     * @return AnnotationModel[]
     */
    public function findWhere($column, $operator, $value)
    {

        $stmt = $this->db->prepare("SELECT * FROM $this->tableName WHERE $column $operator ?");
        $stmt->execute(array($value));
        while ($obj = $stmt->fetchObject($this->model)) {
            if (!isset(self::$_objects[$this->tableName][$this->getPrimaryValue($obj)])) {
                $this->mapObject($obj);
            }
        }
        return self::$_objects[$this->tableName];
    }

    private function mapObject(AnnotationModel $model){
        foreach($this->columnAnnotation as $column => $values){
            if(isset($values['MappedBy']) && isset($values['var'])){
                if(class_exists($values['var'])){
                    $this->setValue($model, $column, $this->findExternal($values['var'], $this->getColumnValue($column, $model)));
                }
            }
        }

        self::$_objects[$this->tableName][$this->getPrimaryValue($model)] = $model;
        $this->fetchExternalAttributes($model);
    }

    private function fetchExternalAttributes($model){
        $reader = new DocBlockReader($this->model);
        if($reader->getParameter("ManyToMany")) {
            $relationship = $reader->getParameter("ManyToMany");
            if (is_array($relationship)) {
                $class = $relationship[0];
                $property = $relationship[1];
                $table = $relationship[2];

                if (!$this->tableExists($table)) {
                    $this->setupExternalTable($table, self::getShortClassName($this->model), self::getShortClassName($class));
                }

                $stmt = $this->db->prepare("SELECT " . self::getShortClassName($class) . " FROM $table WHERE " . self::getShortClassName($this->model) . " = ?");
                $stmt->execute(array($this->getPrimaryValue($model)));

                $result = array();
                foreach ($stmt->fetchAll() as $row) {
                    $result[] = $this->findExternal($class, $row[self::getShortClassName($class)]);

                }

                $model->$property = $result;
            }
        }

        if($reader->getParameter("HasMany")) {
            $relationship = $reader->getParameter("HasMany");
            if (is_array($relationship)) {
                $class = $relationship[0];
                $property = $relationship[1];
                $connected = $relationship[2];

                $repository = PDORepositoryFactory::get($class, $this->db);

                $this->{$property} = $repository->findWhere($connected, '=', $this->getPrimaryValue($model));
            }
        }
    }

    private function setupExternalTable($table, $thisClass, $externalClass){

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `$table` (
                `$thisClass` int(11) NOT NULL,
                `$externalClass` int(11) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
        ");
    }

    private function findExternal($class, $primaryValue){
        $repository = PDORepositoryFactory::get($class, $this->db);
        return $repository->find($primaryValue);
    }

    /**
     * @param $maximumRows
     * @param $startRowIndex
     * @param $totalRowCount
     * @return AnnotationModel[]
     */
    public function paginate($maximumRows, $startRowIndex, &$totalRowCount)
    {
        $pagination = $this->db->prepare("SELECT * FROM $this->tableName LIMIT " . intval($maximumRows) . " OFFSET " . intval(($startRowIndex / $maximumRows) + 1));
        $pagination->execute();

        $rows = $this->db->prepare("SELECT count(*) as rowCount FROM $this->tableName");
        $rows->execute();

        $totalRowCount = $rows->fetchObject()->rowCount;

        return $pagination->fetchAll(\PDO::FETCH_CLASS, $this->model);
    }

    /**
     * @param AnnotationModel $model
     * @return bool
     */
    public function save($model)
    {
        $this->isOfProperClass($model);

        if ($model->isValid($this->FindAll()) !== TRUE) {
            return false;
        }

        if (is_null($this->getPrimaryValue($model))) {
            return $this->Create($model);
        }

        return $this->Update($model);

    }

    /**
     * @param AnnotationModel $model
     */
    public function delete($model)
    {
        $this->isOfProperClass($model);

        $stmt = $this->db->prepare("DELETE FROM $this->tableName WHERE $this->primaryKey = :primary");
        $stmt->execute(array(
            'primary' => $this->getPrimaryValue($model)
        ));
    }

    public function uninstall(){
        $this->db->exec("
          DROP TABLE IF EXISTS `{$this->tableName}`
        ");
    }

    /**
     * @param string $value
     * @param string $order
     * @return array
     */
    public function orderBy($value = null, $order = 'ASC'){
        $value = $value == null ? $this->primaryKey : $value;
        usort(self::$_objects[$this->tableName], function($a, $b) use ($value, $order) {

            $a = $this->getColumnValue($value, $a);
            $b = $this->getColumnValue($value, $b);

            if ($a == $b){
                return 0;
            }elseif($order == 'ASC'){
                return ($a < $b) ? -1 : 1;
            }else{
                return ($b < $a) ? -1 : 1;
            }

        });

        return self::$_objects[$this->tableName];
    }

    /**
     * @param AnnotationModel $model
     * @return bool
     */
    private function update(AnnotationModel $model)
    {
        $this->db->beginTransaction();
        try {
            $values = array();
            $params = array();
            foreach ($this->columns as $column) {
                $values[] = "$column = :$column";
                $params[$column] = $this->getColumnValue($column, $model);
            }

            $stmt = $this->db->prepare("UPDATE $this->tableName SET " . implode(', ', $values) . " WHERE $this->primaryKey = :primary");

            $params = array_merge(
                array(
                    'primary' => $this->getPrimaryValue($model)
                ), $params
            );

            $stmt->execute($params);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new \PDOException("Error occurred when updating model in db");
        }

        return true;
    }

    /**
     * @param AnnotationModel $model
     * @return bool
     * @throws \Exception
     */
    private function create(AnnotationModel $model)
    {
        $this->db->beginTransaction();
        try {
            $values = array();
            $params = array();
            $columns = array();
            foreach ($this->columns as $column) {
                //Don't insert into table if the value is NULL and it has a default value in database
                if($this->getColumnValue($column, $model) != null || !isset($this->columnAnnotation[$column]['Default'])){
                    $columns[] = $column;
                    $values[] = ":$column";
                    $params[$column] = $this->getColumnValue($column, $model);

                    if(is_object($params[$column]) && is_a($params[$column], '\\model\\annotation\\AnnotationModel') && isset($this->columnAnnotation[$column]['MappedBy'])){

                        $params[$column] = $this->getPrimaryFromExternal(get_class($params[$column]), $this->columnAnnotation[$column]['MappedBy'][0], $params[$column]);

                    }elseif(is_array($params[$column]) && isset($this->columnAnnotation[$column]['MappedBy'])){
                        $array = array();
                        foreach($params[$column] as $object){
                            $array[] = $this->getPrimaryFromExternal(get_class($object), $this->columnAnnotation[$column]['MappedBy'][0], $object);
                        }

                        $params[$column] = json_encode($array);
                    }
                }
            }

            $stmt = $this->db->prepare("INSERT INTO $this->tableName (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")");

            $stmt->execute($params);
            $id = $this->db->lastInsertId();
            $this->setPrimaryValue($model, $id);
            self::$_objects[$this->tableName][$id] = $model;

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
            //throw new \PDOException("Error occurred when adding model to db");
        }

        return true;
    }

    private function getPrimaryFromExternal($class, $primary, $object){
        $value = new \ReflectionProperty($class, $primary);
        $value->setAccessible(true);

        if($value == null){
            throw new \Exception("Mapped value cannot be null");
        }

        return $value->getValue($object);


    }

    /**
     * @comment Queries database to check if the table exists
     */
    private function tableExists($table)
    {
        try {
            $result = $this->db->query("SELECT 1 FROM $table LIMIT 1");
        } catch (\Exception $e) {
            return FALSE;
        }

        // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
        return $result !== FALSE;
    }

    /**
     * @comment Determines if the table needs to be created or updated
     */
    private function checkTable()
    {
        if ($this->tableExists($this->tableName)) {
            $this->updateTable();
        } else {
            $this->setupTable();
        }
    }

    /**
     * @comment Adds table and columns to database along with Unique and Primary Key
     */
    private function setupTable()
    {
        $columns = array();
        $unique = array();
        foreach ($this->columnAnnotation as $column => $annotations) {
            if(isset($annotations['Unique'])){
                $unique[] = $column;
            }

            $columns[] = $this->getSqlForColumn($column, $annotations);
        }



        $sql = "
                CREATE TABLE IF NOT EXISTS `$this->tableName` (
                    `$this->primaryKey` int(11) NOT NULL,
                    " . implode(', ', $columns) . "
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;

                  ALTER TABLE `$this->tableName`
                  ADD PRIMARY KEY (`$this->primaryKey`);

                  ALTER TABLE `$this->tableName`
                  MODIFY `$this->primaryKey` int(11) NOT NULL AUTO_INCREMENT;

                {$this->getUniqueSql($unique)}
            ";



        $this->db->exec($sql);
    }

    /**
     * @comment Checks table and columns in database along with Unique and Primary Key and updates if necessary
     */
    private function updateTable()
    {
        $stmt = $this->db->prepare("DESCRIBE $this->tableName");
        $stmt->execute();

        $columns = $this->columnAnnotation;

        $sql = '';
        $inTable = array();
        $unique = array();

        while($row = $stmt->fetchObject()){
            $inTable[$row->Field] = $row;
        }

        foreach($columns as $name => $doc){
            if(isset($doc['Unique'])){
                $unique[$name] = $name;
            }

            if(isset($inTable[$name])){
                $row = $inTable[$name];

                if(
                    $row->Null == 'YES' && isset($doc['Required']) ||
                    $row->Null == 'NO' && !isset($doc['Required']) ||
                    (isset($doc['DbType']) && $row->Type != strtolower($doc['DbType'][0])) ||
                    (isset($doc['Default']) && !($row->Default == strtolower($doc['Default'][0]) || $row->Default == $doc['Default'][0])) ||
                    (!isset($doc['Default']) && !is_null($row->Default))
                ){

                    $sql .= "ALTER TABLE `$this->tableName` CHANGE `$row->Field` {$this->getSqlForColumn($name, $doc)};";
                }
            }else{
                $sql .= "ALTER TABLE `$this->tableName` ADD {$this->getSqlForColumn($name, $doc)};";
                $inTable[$name] = true;
            }
        }

        foreach($inTable as $field => $obj){
            if(is_object($obj) && $field != $this->primaryKey){
               if(!isset($columns[$field])){
                   if(self::$deleteOnMapMismatch){
                       $sql .= "ALTER TABLE `$this->tableName` DROP COLUMN $field;";
                   }else{
                       throw new \Exception("Field \"$field\" in table \"$this->tableName\" found that isn't marked as a column in class \"$this->model\"");
                   }

               }
            }
        }

        if(self::$updateIndexOnConstruct){
            $sql .= $this->updateUniqueSql($unique);
        }

        if(!empty($sql)){
            $this->db->exec($sql);
        }
    }

    /**
     * @param $column
     * @param $docs
     * @return string
     *
     * @comment Return SQL query for column
     */
    private function getSqlForColumn($column, $docs)
    {
        $type = isset($docs['DbType']) ? $docs['DbType'][0] : 'varchar(150)';
        $default = 'DEFAULT NULL';
        if(
            isset($docs['Required']) && isset($docs['Default']) ||
            !isset($docs['Required']) && isset($docs['Default'])
        ){
            $default = $this->getDefaultValue($docs['Default'][0]);
        }elseif(isset($docs['Required']) && !isset($docs['Default'])){
            $default = '';
        }

        $required = isset($docs['Required']) ? "NOT NULL $default" : "NULL $default";

        return "`$column` $type COLLATE utf8_swedish_ci $required";
    }

    private function getDefaultValue($value){
        switch($value){
            case 'CURRENT_TIMESTAMP':
                return "DEFAULT $value";
                break;
            default:
                return "DEFAULT '$value'";
                break;
        }
    }

    /**
     * @param array $unique
     * @return string
     *
     * @comment Return SQL query for creating a Unique index
     */
    private function getUniqueSql(array $unique){
        if(count($unique)){
            return "
              CREATE UNIQUE INDEX uc_{$this->tableName} ON {$this->tableName}(" . implode(', ', $unique) . ");
            ";
        }

        return "";
    }

    /**
     * @param array $unique
     * @return string
     *
     * @comment Checks if Unique indexes need to be added/deleted and updates them
     */
    private function updateUniqueSql(array $unique){
        $sql = "";
        $stmt = $this->db->prepare("SHOW INDEX FROM {$this->tableName} WHERE Key_name = :name;");
        $stmt->execute(array('name' => "uc_{$this->tableName}"));

        $constraints = array();
        while($obj = $stmt->fetchObject()){
            $constraints[$obj->Column_name] = $obj;
        }
        $resetConstraints = false;
        foreach($constraints as $obj){
            if(!isset($unique[$obj->Column_name]) && !$resetConstraints){
                $sql .= " DROP INDEX uc_{$this->tableName} ON {$this->tableName};";
                $sql .= $this->getUniqueSql($unique);
                $resetConstraints = true;
                break;
            }
        }

        if(!$resetConstraints){
            foreach($unique as $u){
                if(!isset($constraints[$u])){
                    if(count($constraints)){
                        $sql .= " DROP INDEX uc_{$this->tableName} ON {$this->tableName};";
                    }
                    $sql .= $this->getUniqueSql($unique);
                    break;
                }
            }
        }

        return $sql;
    }

}