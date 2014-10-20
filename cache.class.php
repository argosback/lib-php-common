<?
class Cache {
  public static $memcache = null;

  public static function getMemcache() {
    if (! self::$memcache) {
      $m = new Memcache;
      if (! $m->pconnect('localhost', 11211)) return false;
      self::$memcache = $m;
    }
    return self::$memcache;
  }

  public static function set($key, $value, $compress, $expire) {
    if (!$m = self::getMemcache()) return false;
    $value = serialize($value);
    return $m->set($key, $value, $compress, $expire);
  }

  public static function get($key) {
    if (!$m = self::getMemcache()) return false;
    return unserialize($m->get($key));
  }

}
?>
