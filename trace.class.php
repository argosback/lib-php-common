<?php
class Trace {
  public $stats = array();
  
  function __construct() {
    declare(ticks=1);
    register_tick_function(array($this, 'tick'));
    echo 'hi';
  }
  
  function __destruct() {
    print_r($this->stats);
  }
  
  public function tick() {
    $trace = debug_backtrace();
    if (is_array($trace[1]['args'])) $func_args = implode(", ",$trace[1]['args']);
    else $func_args = '';
    
    $new = array(
      "current_time" => microtime(true),
      "memory" => memory_get_usage(true),
      "file" => $trace[1]['file'] . ': ' . $trace[1]['line'],
      "function" => $trace[1]["function"].'('.$func_args.')',
      "called_by" => $trace[2]["function"].' in '.$trace[2]["file"].': '.$trace[2]["line"],
    );
    
    $this->stats[] = $new;
  }
  
}


?>
