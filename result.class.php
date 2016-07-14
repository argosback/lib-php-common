<?php
class Result {
  public static $format='text';
  public $result;
  public $desc;
  
  function __construct($res, $desc='', $notes='') {
    if ($res == 'win' || $res === true) $this->result = true;
    else $this->result = false;
    
    $this->desc = $desc;
    $this->notes = $notes;
  }
  
  function json() {
    echo json_encode(get_object_vars($this));
  }
  
  function text() {
    if ($this->result) echo 'ok';
    else echo $this->desc;
  }
  
  function show() {
    if (Result::$format == 'text') $this->text();
    else if (Result::$format == 'json') $this->json();
  }
  
  public static function lose($msg='') {
    $res = new Result(false, $msg);
    $res->show();
  }
  
  public static function win($msg='') {
    $res = new Result(true, $msg);
    $res->show();
  }

}

?>
