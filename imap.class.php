<?php
class IMAP {
  public $handle;
  public $server;
  public $username;
  public $password;
  
  function __construct($server, $username, $password) {
    $this->server = $server;
    $this->username = $username;
    $this->password = $password;
  }
  
  function connect() {
    $this->handle = imap_open('{' . $this->server . '/novalidate-cert}', $this->username, $this->password);
    if ($this->handle) return true;
    else return false;
  }
  
  function count() {
    return imap_num_msg($this->handle);
  }
  
  function deletemsg($number) {
    imap_delete($this->handle, $number);
    imap_expunge($this->handle);
  }
  
  // field msgno is the number
  function listmsg() {
    $R = array();
    $headers = imap_fetch_overview($this->handle, '1:' . $this->count());
    foreach ($headers as $header) {
      $header = get_object_vars($header);
      $header['human_duration'] = Time::human_duration(time() - strtotime($header['date']));
      $R[] = $header;
    }
    return $R;
  }
  
  function readmsg($number) {
    $header_str = imap_fetchheader($this->handle, $number);
    
    $R = self::parseheader($header_str);
    $R['body'] = trim(imap_body($this->handle, $number));
    
    return $R;
  }
  
  // could also use imap_headerinfo
  protected static function parseheader($header) {
    $R = array();
    preg_match_all('/[\S ]+([\r\n]+[\t ]+[\S ]+){0,}/i', $header, $arr, PREG_PATTERN_ORDER);
    foreach ($arr[0] as $line) {
      list($key, $value) = explode(': ', $line, 2);
      $key = strtolower($key);
      $value = preg_replace('/[\r\n]+[\t ]+/i', "\n", $value);
      $value = trim($value);
      
      if (isset($R[$key])) {
        $R[$key] = Arr::arrayify($R[$key]);
        $R[$key][] = $value;
      } else {
        $R[$key] = $value;
      }
    }
    
    return $R;
  }
  
  function searchrecent($time, $params=array()) {
    $R = array();
    foreach ($this->listmsg() as $msg) {
      if (time() - strtotime($msg['date']) > $time) continue;
      foreach ($params as $key => $param) {
        if (! Common::strContains(strtolower($msg[$key]), strtolower($param))) continue 2;
      }
      
      $R[] = $this->readmsg($msg['msgno']);
    }
    
    return $R;
  }
  
}


?>
