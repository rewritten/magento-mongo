<?php
/**
 * Common data type conversion from PHP values to Mongo values
 */
class Cm_Mongo_Model_Type_Tomongo
{

  public function any($mapping, $value)
  {
    return $value;
  }

  public function int($mapping, $value)
  {
    return (int) $value;
  }

  public function string($mapping, $value)
  {
    return (string) $value;
  }

  public function float($mapping, $value)
  {
    return (float) $value;
  }

  public function bool($mapping, $value)
  {
    return (bool) $value;
  }

  public function MongoId($mapping, $value)
  {
    if($value instanceof MongoId) {
      return $value;
    }
    if(is_string($value)) {
      return new MongoId($value);
    }
    return NULL;
  }
  
  public function MongoDate($mapping, $value)
  {
    if($value instanceof MongoDate) {
      return $value;
    }
    else if(is_array($value) && isset($value['sec'])) {
      return new MongoDate($value['sec'], isset($value['usec']) ? $value['usec'] : 0);
    }

    $value = $this->timestamp($mapping, $value);
    if($value === NULL) {
      return NULL;
    }
    return new MongoDate((int)$value);
  }

  public function timestamp($mapping, $value)
  {
    if($value instanceof MongoDate) {
      return $value->sec;
    }
    else if($value === NULL) {
      return NULL;
    }
    else if(is_int($value) || is_float($value)) {
      return (int) $value;
    }
    else if($value instanceof Zend_Date) {
      return $value->getTimestamp();
    }
    else if(is_array($value) && isset($value['sec'])) {
      return $value['sec'];
    }
    else if( ! strlen($value)) {
      return NULL;
    }
    else if(ctype_digit($value)) {
      return intval($value);
    }
    else if(($time = strtotime($value)) !== false) {
      return $time;
    }
    return NULL;
  }

  public function datestring($mapping, $value)
  {
    if($value instanceof Zend_Date) {
      return $value->toString(Varien_Date::DATE_INTERNAL_FORMAT);
    }
    else if(is_int($value)) {
      $date = new Zend_Date($value);
      return $value->toString(Varien_Date::DATE_INTERNAL_FORMAT);
    }
    else if($value instanceof MongoDate) {
      $date = new Zend_Date($value->sec);
      return $value->toString(Varien_Date::DATE_INTERNAL_FORMAT);
    }
    else {
      return (string) $value;
    }
  }

  public function set($mapping, $value)
  {
    $value = array_values((array) $value);
    if($mapping->subtype) {
      $subtype = (string) $mapping->subtype;
      foreach($value as &$val) {
        $val = $this->$subtype($mapping, $val);
      }
    }
    return $value;
  }
  
  public function hash($mapping, $value)
  {
    if( ! count($value)) {
      return new ArrayObject;
    }
    if($mapping->subtype) {
      $subtype = (string) $mapping->subtype;
      foreach($value as $key => $val) {
        $value[$key] = $this->$subtype($mapping, $val);
      }
    }
    return (array) $value;
  }

  public function enum($mapping, $value)
  {
    return isset($mapping->options->$value) ? (string) $value : NULL;
  }

  public function enumSet($mapping, $value)
  {
    if($value instanceof Mage_Core_Model_Config_Element) {
      $value = array_keys($value->asCanonicalArray());
    }
    
    if( ! is_array($value) || ! count($value)) {
      return array();
    }
    $value = array_unique($value);
    $rejects = array();
    foreach($value as $val) {
      if( ! $this->enum($mapping, $val)) {
        $rejects[] = $val;
      }
    }
    if($rejects) {
      return array_diff($value, $rejects);
    }
    return $value;
  }

  public function reference($mapping, $value)
  {
    $value = ($value instanceof Varien_Object ? $value->getId() : $value);
    return Mage::getResourceSingleton((string) $mapping->model)->castToMongo('_id', $value);
  }

  public function referenceSet($mapping, $value)
  {
    $ids = array();
    foreach($value as $item) {
      $id = $item instanceof Varien_Object ? $item->getId() : $item;
      $ids[] = Mage::getResourceSingleton((string) $mapping->model)->castToMongo('_id', $id);
    }
    return $ids;
  }

  public function __call($name, $args)
  {
    return Mage::getSingleton($name)->toMongo($args[0], $args[1]);
  }

}
