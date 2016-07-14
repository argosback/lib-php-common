<?php
class CommandLineOptions {
  
  function __construct($arg) {
    $this->arg = $arg;
  }
  
  public static function getAll($arg) {
    array_shift($arg);
    $result = array();
    $follow = array();
    foreach ($arg as $param) {
      if (preg_match('/^--?(.*)$/', $param, $matches)) {
        $p = $matches[1];
        $result[$p] = true;
        $last_key = $p;
      } else {
        if ($last_key) {
          $result[$last_key] = $param;
          $last_key = null;
        }
        else $follow[] = $param;
      }
    }
    return array($result, $follow);
  }
  /*
  function get($param) {
    
  }
  
  function getbool($param) {
    return (array_search($param) !== false);
  }
  */

}


?>
