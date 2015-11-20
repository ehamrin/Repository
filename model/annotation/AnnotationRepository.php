<?php

namespace model\annotation;

abstract class AnnotationRepository
{

    private static $defaultPrimaryKey = 'id';

    protected $model;
    protected $primaryKey;
    protected $tableName;
    protected $reflection;
    protected $columns;
    protected $columnAnnotation;

    public function __construct($className)
    {
        if(!class_exists($className)){
            throw new \BadMethodCallException("The class does not exist");
        }

        $this->model = $className;
        $this->reflection = new \ReflectionClass($this->model);
        $this->primaryKey = $this->findPrimaryKey();
        $this->columns = $this->findColumns();
        $this->tableName = $this->findTableName();

    }

    /*******************************************************************************************
     *
     *
     *                              INTERNAL FUNCTIONS
     *
     *
     *******************************************************************************************/



    protected function isOfProperClass(\model\IModel $model){
        //Add root namespace to classname if missing
        $modelClass = get_class($model);
        if(substr($modelClass, 0, 1) != "\\"){
            $modelClass = "\\" . $modelClass;
        }
        if($modelClass != $this->model){
            throw new \BadMethodCallException("AnnotationModel is not a repository model class");
        }
    }

    private function findPrimaryKey(){
        foreach($this->reflection->getProperties() as $property){
            $reader = new DocBlockReader($this->model, $property->name, 'property');
            if($reader->getParameter("Primary")){
                return $property->name;
            }
        }

        return self::$defaultPrimaryKey;
    }

    private function findColumns(){
        $columns = array();
        foreach($this->reflection->getProperties() as $property){
            $reader = new DocBlockReader($this->model, $property->name, 'property');
            if($reader->getParameter("Column") && !$reader->getParameter("Primary")){
                $columns[$property->name] = $property->name;
                $this->columnAnnotation[$property->name] = $reader->getParameters();
            }
        }
        return $columns;
    }

    private function findTableName(){
        $reader = new DocBlockReader($this->model);
        if($reader->getParameter("Table")){
            return $reader->getParameter("Table")[0];
        }
        return array_pop(explode('\\', $this->model));
    }

    protected function getColumnValue($column, $model){
        $primary = new \ReflectionProperty($this->model, $column);
        $primary->setAccessible(true);
        return $primary->getValue($model);
    }

    protected function getPrimaryValue(\model\IModel $model){
        $primary = new \ReflectionProperty($this->model, $this->primaryKey);
        $primary->setAccessible(true);
        return $primary->getValue($model);
    }

    protected function setPrimaryValue(\model\IModel $model, $value){
        $primary = new \ReflectionProperty($this->model, $this->primaryKey);
        $primary->setAccessible(true);
        $primary->setValue($model, $value);
    }


}