<?php


namespace DASHIF;


class ValidatorAdapter
{
    public $name;
    private $filePath;

    public function __construct($filePath)
    {
        $this->loadNew($filePath);
    }

    public function loadNew($filePath){
        $this->filePath = $filePath;
        if($this->filePath){
            $this->setupFunction();

        }


    }

    public function isLoaded(){
        return false;
    }

    protected function setupFunction(){
        //Do whatever module specific functionality needed here.
    }

    public function hasNamedBox($boxName) {
        return false;
    }

    public function getNamedBoxes($boxName) {
        return array();
    }

    public function getNamedBoxProperties($boxName, $propertyName){
        return array();
    }

    public function getNamedBoxProperty($boxName, $propertyName, $boxIndex){
        return '';
    }

}

