<?php

namespace annotation\model;

use annotation\DocBlockReader;

abstract class AnnotationModel implements IModel
{
    private $_modelErrors = array();

    public function isValid(array $_repositoryList = null){

        $reflectionClass = new \ReflectionClass(get_class($this));
        $properties = $reflectionClass->getProperties();

        foreach($properties as $property){

            $reader = new DocBlockReader($property->class, $property->name, 'property');
            $property->setAccessible(true);

            $value = $property->getValue($this);
            foreach($reader->getParameters() as $name => $parameter){

                //Validate against validation classes
                $validationClass = '\\annotation\\validators\\' . $name;
                if(class_exists($validationClass)){
                    if(is_array($parameter)){
                        $validator = new $validationClass(...$parameter);
                    }else{
                        $validator = new $validationClass($parameter);
                    }
                    /** @var $validator \annotation\validation\Validation */
                    if(!$validator->Validate($value) && !($name == 'Required' && $reader->getParameter('Default'))){
                        $this->_modelErrors[$property->name][$name] = $validator->GetMessage();
                    }
                }

                if($_repositoryList != null){
                    switch($name){
                        case 'Unique':
                        case 'Primary':
                            foreach($_repositoryList as $item){
                                if($this != $item){

                                    if($value == $property->getValue($item)){
                                        $message = $parameter;

                                        if(is_array($parameter)){
                                            $message = $message[0];
                                        }

                                        $this->_modelErrors[$property->name][$name] = $message;
                                    }
                                }
                            }
                            break;
                    }
                }
            }
        }


        if(count($this->_modelErrors)){
            return $this->_modelErrors;
        }

        return true;
    }

    public function getModelError(){
        return $this->_modelErrors;
    }
}