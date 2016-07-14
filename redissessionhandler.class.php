<?php
class RedisSessionHandler implements SessionHandlerInterface {
  public $redis;
  public $expire_time;

  function __construct($expire_time, $redis=null) {
    $this->redis = $redis;
    if (! $this->redis) $this->redis = new Predis\Client();
    $this->expire_time = $expire_time;
  }

  function open($save_path, $name) { return true; }
  function close() { return true; }
  function gc($maxlifetime) { return true; }

  function write($session_id, $session_data) {
    if ($session_data === '') return true;
    try {
      $this->redis->set("sessions:$session_id", $session_data);
      $this->redis->expire("sessions:$session_id", $this->expire_time);
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

  function read($session_id) {
    try {
      $result = $this->redis->get("sessions:$session_id");
      if (! $result) throw new Exception();
      return $result;
    } catch (Exception $e) {
      return '';
    }
  }

  function destroy($session_id) {
    try {
      $this->redis->del("sessions:$session_id");
      return true;
    } catch (Exception $e) {
      return false;
    }
  }
}

