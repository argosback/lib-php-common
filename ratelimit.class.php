<?
/* 
CREATE TABLE IF NOT EXISTS `ratelimit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `marker` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `marker` (`marker`(100),`action`(100)),
  KEY `action` (`action`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
*/


class RateLimit {
  public static $table_name = 'ratelimit';
  public static $db_name = null;
  
  protected static function _getTableName() {
    if (! self::$db_name) return self::$table_name;
    else return self::$db_name . '.' . self::$table_name;
  }
  
  static function add($action, $marker = null) {
    if ($marker === null) $marker = $_SERVER['REMOTE_ADDR'];
    
    DB::insert(self::_getTableName(), array('marker' => $marker, 'action' => $action));
  }
  
  static function check($action, $duration, $marker = null, $deleteOld = true) {
    if ($deleteOld) { 
      DB::query("DELETE FROM %b 
        WHERE action=%s AND ts < DATE_SUB(NOW(), INTERVAL %l)", self::_getTableName(), $action, $duration);
    }
    
    if ($marker === null) $marker = $_SERVER['REMOTE_ADDR'];
    if (! $marker) return 0;
    
    return DB::queryFirstField("SELECT COUNT(*) FROM %b 
      WHERE marker=%s AND action=%s AND ts > DATE_SUB(NOW(), INTERVAL %l)", 
      self::_getTableName(), $marker, $action, $duration);
  }
  
  
  
  
  
}







?>
