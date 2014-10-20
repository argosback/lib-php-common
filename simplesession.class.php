<?
/*
CREATE TABLE `session` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cookie_key` varchar(32) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `name` varchar(30) NOT NULL,
  `expire_time` int(10) unsigned NOT NULL,
  `ts_last_accessed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cookie_key` (`cookie_key`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `session_params` (
  `id` int(10) unsigned NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

*/

class SimpleSession extends MyObject {
  public static $myobj_haveparams = true;
  public static $myobj_tablename = 'session';
  public static $myobj_primarykey = 'id';
  
  public static $myobj_param_dbname = null;
  public static $myobj_param_tablename = null;
  
  public static $simplesession_useip = true;
  
  public static $me = null;
  
  
  function _pre_destruct() {
    $this->save();
  }
  
  public function cookieName() {
    return 'simplesess_' . $this->name(); 
  }
  
  public static function readCookie($name) {
    return $_COOKIE['simplesess_' . $name];
  }
  
  function _pre_destroy() {
    setcookie($this->cookieName());
  }
  
  
  private function setCookie() {
    setcookie($this->cookieName(), $this->row['cookie_key'], $this->row['expire_time'], '/');
  }
  
  public static function getOrCreate($name='login', $duration='session') {
    $s = static::getSession($name);
    if ($s) return $s;
    
    $s = static::createSession($name, $duration);
    return $s;
  }
    
  public static function createSession($name='login', $duration='session') {
    $s = static::Create();
    
    $s->cookie_key(Common::randomString(32));
    $s->name($name);
    $s->ip($_SERVER['REMOTE_ADDR']);
    
    if ($duration == 'permanent') $s->expire_time(strtotime("+12 months"));
    else $s->expire_time(0);
    
    $s->Save();
    $s->setCookie();
    
    return $s;
  }
  
  public static function getSession($name='login') {
    $cookie_val = static::readCookie($name);
    if (! $cookie_val) return null;
    
    $searchparams = array(
      'cookie_key' => $cookie_val,
      'name' => $name
    );
    
    if (static::$simplesession_useip) $searchparams['ip'] = $_SERVER['REMOTE_ADDR'];
    
    $s = static::SearchOne($searchparams);
    
    return $s;
  }
  
  public function _post_load() {
    if ((time() - strtotime($this->ts_last_accessed())) > 86400) {
      DB::query("UPDATE session SET ts_last_accessed=NOW() WHERE id=%i", $this->id());
      $this->reload();
    }
    
    if ($_SERVER['REMOTE_ADDR'] && $this->ip() != $_SERVER['REMOTE_ADDR']) {
      $this->ip($_SERVER['REMOTE_ADDR']);
      $this->Save();
    }
  }
  

}


?>
