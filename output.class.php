<?php

Output::addTrigger('debug', 'echo');
Output::addTrigger('out', 'echo');
Output::addTrigger('warn', 'echo');
Output::addTrigger('error', 'die');

class Output {
  private static $triggers = array();
  private static $triggers_final = array();
  public static $group = '';
  
  public static function addTrigger($name, $trigger, $param = null) {
    $final = false;
    
    if (!isset(self::$triggers_final[$name]) || !is_array(self::$triggers_final[$name])) self::$triggers_final[$name] = array();
    if (!isset(self::$triggers[$name]) || !is_array(self::$triggers[$name])) self::$triggers[$name] = array();
    
    if ($trigger === 'echo') {
      $trigger = function($text) { echo $text . "\n"; };
    } else if ($trigger === 'throw') {
      if (! $param) die("didn't specify exception to throw!");
      $trigger = function($text = '') use ($param) { throw new $param($text); };
      $final = true;
    } else if ($trigger === 'die') {
      $trigger = function($text = '') { die($text . " ..dying!\n"); };
      $final = true;
    } else if ($trigger == 'call') {
      if (! $param || !is_callable($param)) die("didn't specify a callable method!");
      $trigger = function($text = '') use ($param) { call_user_func($param, $text); };
      $final = true;
    } else if ($trigger === 'null') {
      self::$triggers[$name] = array();
      return;
    } else if ($trigger === 'log') {
      if (! $param) die("didn't specify file to log to!");
      $trigger = function($text = '') use ($param, $name) {
        $timestamp = date('Y-m-d h:i:s a');
        File::append($param, "[$timestamp] [$name]: $text\n");
      };
    }
    
    if ($final) self::$triggers_final[$name] = array($trigger);
    else self::$triggers[$name][] = $trigger;
  }
  
  public static function setTrigger($name, $trigger, $param = null) {
    self::$triggers[$name] = self::$triggers_final[$name] = array();
    return self::addTrigger($name, $trigger, $param);
  }
  
  public static function __callStatic($name, $args) {
    if (! is_array(self::$triggers[$name])) return;
    
    if (self::$group) $args[0] = '[' . self::$group . '] ' . $args[0];
    
    foreach (self::$triggers[$name] as $trigger) {
      call_user_func_array($trigger, $args);
    }
    
    if (! is_array(self::$triggers_final[$name])) return;
    foreach (self::$triggers_final[$name] as $trigger) {
      call_user_func_array($trigger, $args);
    }
  }

}







?>
