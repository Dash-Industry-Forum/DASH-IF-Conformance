<?php


namespace DASHIF;

class MP4ValidatorAdapter // extends ValidatorAdapter 
{
    private $xml;

    protected function setupFunction(){
      //Do whatever module specific functionality needed here.
      $this->xml = get_DOM($this->filePath . '/atomInfo.xml', 'atomlist');
    }

    public function isLoaded(){
      return $this->xml !== null;
    }

    public function hasNamedBox($boxName) {
      return $this->xml->getElementsByTagName($boxname)->length > 0;
    }
    public function getNamedBoxCount($boxName) {
      return $this->xml->getElementsByTagName($boxname)->length;
    }
    public function getNamedBoxes($boxName) {
      return $this->xml->getElementsByTagName($boxname);
    }

    public function getNamedBoxProperties($boxName, $propertyName){
      $result = array();
      $boxes = $this->getNamedBoxes($boxName);
      foreach($boxes as $box){
        $result[] = $box->getAttribute($propertyName);
      }
      return $result;
    }

    public function getNamedBoxProperty($boxName, $propertyName, $boxIndex){
      $boxes = $this->getNamedBoxes($boxName);
      if ($boxes->length <= $boxIndex){return null;}
      return $boxes->item($boxIndex)->getAttribute($propertyName);
    }
}
