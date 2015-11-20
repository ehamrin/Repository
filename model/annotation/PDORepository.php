<?php


namespace model\annotation;


class PDORepository extends AnnotationRepository implements \model\IRepository
{

    private $_objects = null;


    private static $deleteOnMapMismatch = true;
    private static $checkTableOnConstruct = true;
    private static $updateIndexOnConstruct = true;

    public function __construct($className, \PDO $conn)
    {
        parent::__construct($className);
        $this->db = $conn;

        if(self::$checkTableOnConstruct){
            $this->checkTable();
        }
    }

    /**
     * @param $primary
     * @return \model\IModel
     */
    public function find($primary)
    {
        if (!isset($this->_objects[$primary])) {
            $stmt = $this->db->prepare("SELECT * FROM $this->tableName WHERE $this->primaryKey = :primary LIMIT 1");
            $stmt->execute(array(
                'primary' => $primary
            ));

            if (!$stmt->rowCount()) {
                throw new \PDOException("Could not find model in database");
            }
            $obj = $stmt->fetchObject($this->model);
            $this->_objects[$this->getPrimaryValue($obj)] = $obj;
        }
        return $this->_objects[$primary];
    }


    /**
     * @return \model\IModel[]
     */
    public function findAll()
    {

        $stmt = $this->db->prepare("SELECT * FROM $this->tableName");
        $stmt->execute();
        while ($obj = $stmt->fetchObject($this->model)) {
            if (!isset($this->_objects[$this->getPrimaryValue($obj)])) {
                $this->_objects[$this->getPrimaryValue($obj)] = $obj;
            }
        }
        return $this->_objects;
    }

    /**
     * @param $maximumRows
     * @param $startRowIndex
     * @param $totalRowCount
     * @return \model\IModel[]
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
     * @param \model\IModel $model
     * @return bool
     */
    public function save(\model\IModel $model)
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
     * @param \model\IModel $model
     */
    public function delete(\model\IModel $model)
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
     * @param \model\IModel $model
     * @return bool
     */
    private function update(\model\IModel $model)
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
     * @param \model\IModel $model
     * @return bool
     * @throws \Exception
     */
    private function create(\model\IModel $model)
    {
        $this->db->beginTransaction();
        try {
            $values = array();
            $params = array();
            foreach ($this->columns as $column) {
                $values[] = ":$column";
                $params[$column] = $this->getColumnValue($column, $model);
            }

            $stmt = $this->db->prepare("INSERT INTO $this->tableName (" . implode(', ', $this->columns) . ") VALUES (" . implode(', ', $values) . ")");

            $stmt->execute($params);
            $id = $this->db->lastInsertId();
            $this->setPrimaryValue($model, $id);
            $this->_objects[$id] = $model;

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new \PDOException("Error occurred when adding model to db");
        }

        return true;
    }

    private function tableExists()
    {
        try {
            $result = $this->db->query("SELECT 1 FROM $this->tableName LIMIT 1");
        } catch (\Exception $e) {
            return FALSE;
        }

        // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
        return $result !== FALSE;
    }

    private function checkTable()
    {
        if ($this->tableExists()) {
            $this->updateTable();
        } else {
            $this->setupTable();
        }
    }

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
                    (isset($doc['DbType']) && $row->Type != $doc['DbType'])
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

    private function getSqlForColumn($column, $docs)
    {
        $type = isset($docs['DbType']) ? $docs['DbType'][0] : 'varchar(150)';
        $required = isset($docs['Required']) ? 'NOT NULL' : 'NULL DEFAULT NULL';
        return "`$column` $type COLLATE utf8_swedish_ci $required";
    }

    private function getUniqueSql(array $unique){
        if(count($unique)){
            return "
              CREATE UNIQUE INDEX uc_{$this->tableName} ON {$this->tableName}(" . implode(', ', $unique) . ");
            ";
        }

        return "";
    }

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