<?php
/*
  CREATE TABLE IF NOT EXISTS `account_params` (
    `id` int(10) unsigned NOT NULL,
    `key` varchar(255) NOT NULL,
    `value` text NOT NULL,
    PRIMARY KEY (`id`,`key`)
  ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
*/

class MyObject {
  public $row = null;
  public $row_orig = null;
  public static $myobj_haveparams = false;
  protected $myobj_params = null;
  
  public static $myobj_tablename = null;
  public static $myobj_dbname = null;
  public static $myobj_primarykey = 'id';
  
  public static $myobj_param_dbname = null;
  public static $myobj_param_tablename = null;
  public static $myobj_lazy_load_params = false;
  
  function __construct($row = null) {
    if ($row === null) die("use " . get_called_class() . "::Create instead!");
    
    $this->row = $this->row_orig = $row;
    if (! static::$myobj_lazy_load_params) $this->_LoadParams();
    
    if ($this->_primaryKey() !== null) {
      if (method_exists($this, '_post_load')) $this->_post_load();
    }
  }
  
  function __destruct() {
    if (method_exists($this, '_pre_destruct')) $this->_pre_destruct();
  }
  
  function _primaryKey() {
    if (isset($this->_insertId)) return $this->_insertId;
    if (! isset($this->row[static::$myobj_primarykey])) return null;
    return $this->row[static::$myobj_primarykey];
  }
  
  function reload($trans = false) {
    if ($trans) $for_update = 'FOR UPDATE';
    else $for_update = '';
    
    $row = DB::queryOneRow("SELECT * FROM %b WHERE %b=%i $for_update", static::_getTableName(), static::$myobj_primarykey, $this->_primaryKey());
    $this->row = $this->row_orig = $row;
    if (! static::$myobj_lazy_load_params || is_array($this->myobj_params)) $this->_LoadParams();
    if (method_exists($this, '_post_load')) $this->_post_load();
  }
  
  function Destroy() {
    if (!$this->row || ($this->_primaryKey() === null)) return false;
    
    if (method_exists($this, '_pre_destroy')) $this->_pre_destroy();
    DB::query("DELETE FROM %b WHERE %b=%i", static::_getTableName(), static::$myobj_primarykey, $this->_primaryKey());
    if (static::$myobj_haveparams) $this->unsetallparams();
    if (method_exists($this, '_post_destroy')) $this->_post_destroy();
    
    return true;
  }
  
  public static function Create() {
    $name = get_called_class();
    $obj = new $name(array());
    if (method_exists($obj, '_post_create')) $obj->_post_create();
    if (static::$myobj_haveparams) $obj->myobj_params = array();
    return $obj;
  }
  
  function __call($func, $args) {
    if ($this->row_orig && !array_key_exists($func, $this->row_orig)) {
      die("Can't access undefined parameter $func on " . get_called_class());
    }
    
    if (count($args) === 1) {
      $value = $args[0];
      if (method_exists($this, '_pre_set')) $this->_pre_set($func, $value);
      $this->row[$func] = $value;
    }
    return $this->row[$func];
  }
  
  protected static function _getParamTableName() {
    if (static::$myobj_param_tablename) {
      if (static::$myobj_param_dbname) return static::$myobj_param_dbname . '.' . static::$myobj_param_tablename;
      else return static::$myobj_param_tablename;
    } else {
      return static::_getTableName() . '_params';
    }
  }
  
  protected static function _getTableName() {
    if (static::$myobj_dbname) return static::$myobj_dbname . '.' .  static::$myobj_tablename;
    else if (static::$myobj_tablename) return static::$myobj_tablename;
    else return strtolower(get_called_class());
  }
  
  public static function ConstructFromList($list) {
    $A = array();
    $name = get_called_class();
    foreach ($list as $item) {
      $A[] = new $name($item);
    }
    return $A;
  }
  
  public static function Load($id) {
    $name = get_called_class();
    $row = DB::queryOneRow("SELECT * FROM %l WHERE %b=%i", static::_getTableName(), static::$myobj_primarykey, $id);
    if (! $row) return null;
    $obj = new $name($row);
    return $obj;
  }
  
  public static function SearchOne() {
    $args = func_get_args();
    array_unshift($args, true);
    return call_user_func_array('self::SearchHelper', $args);
  }
  
  public static function Search($params) {
    $args = func_get_args();
    array_unshift($args, false);
    return call_user_func_array('self::SearchHelper', $args);
  }
  
  public static function SearchHelper() {
    $name = get_called_class();
    $args = func_get_args();
    $onlyOne = array_shift($args);
    
    if (is_array($args[0]) && count($args[0])) {
      $where = new WhereClause('and');
      foreach ($args[0] as $key => $value) {
        if (is_int($value)) $where->add("$key = %i", $value);
        else $where->add("$key = %s", $value);
      }
      $where = $where->text(true);
      $args = array($where);
    }
    
    if ($onlyOne) {
      $query = array_shift($args);
      $query = "SELECT * FROM %b WHERE $query LIMIT 1";
      array_unshift($args, static::_getTableName());
      array_unshift($args, $query);
      $row = call_user_func_array('DB::queryOneRow', $args);
      if (! $row) return null;
      $obj = new $name($row);
      return $obj;
    } else {
      $R = array();
      $query = array_shift($args);
      $query = "SELECT * FROM %b WHERE $query";
      array_unshift($args, static::_getTableName());
      array_unshift($args, $query);
      $rows = call_user_func_array('DB::queryAllRows', $args);
      foreach ($rows as $row) {
        $R[] = new $name($row);
      }
      return $R;
    }
  }
  
  public function Save() {
    if (! $this->row) return;
    if (method_exists($this, '_pre_save')) $this->_pre_save();
    
    if (! $this->row_orig || ($this->_primaryKey() === null)) {
      DB::replace(static::_getTableName(), $this->row);
      $this->_insertId = DB::insertId();
      $this->reload();
      
    } else {
      $save = array();
      foreach ($this->row_orig as $key => $value) {
        if ($this->row_orig[$key] !== $this->row[$key]) $save[$key] = $this->row[$key];
      }
      $this->row_orig = $this->row;
      
      if ($save) DB::update(static::_getTableName(), $save, static::$myobj_primarykey . '=%i', $this->_primaryKey());
    }
    
    if (method_exists($this, '_post_save')) $this->_post_save();
    
    return true;
  }
  
  protected function _LoadParams() {
    if (! static::$myobj_haveparams || ($this->_primaryKey() === null)) return; 
    
    $this->myobj_params = array();
    $rows = DB::query("SELECT * FROM %b WHERE %b=%i", 
      static::_getParamTableName(), 'id', $this->_primaryKey());
    
    foreach ($rows as $row) {
      $this->myobj_params[strval($row['key'])] = unserialize($row['value']);
    }
  }
  
  public function param($key) {
    if (! static::$myobj_haveparams) return;
    if (! is_array($this->myobj_params)) $this->_LoadParams();
    
    return $this->myobj_params[$key];
  }
  
  public function setparam($key, $value) {
    if (! static::$myobj_haveparams) return;
    if ($this->_primaryKey() === null) die("Can't set " . get_called_class() . " params while primary key is unknown!");
    if (! is_array($this->myobj_params)) $this->_LoadParams();
    
    $this->myobj_params[$key] = $value;
    
    DB::replace(static::_getParamTableName(), array(
      'id' => $this->_primaryKey(),
      'key' => strval($key),
      'value' => serialize($value),
    ));
  }
  
  public function unsetparam($key) {
    if (! static::$myobj_haveparams) return;
    if (! is_array($this->myobj_params)) $this->_LoadParams();
    
    unset($this->myobj_params[$key]);
    
    DB::query("DELETE FROM %b WHERE id=%i AND key=%s", 
      static::_getParamTableName(), $this->_primaryKey(), $key);
    
  }
  
  public function unsetallparams() {
    if (! static::$myobj_haveparams) return;
    if (! is_array($this->myobj_params)) $this->_LoadParams();
    
    $this->myobj_params = array();
    
    DB::query("DELETE FROM %b WHERE id=%i", 
      static::_getParamTableName(), $this->_primaryKey());
  }
  
  final public function rowList($objects) {
    return array_map(function($x) {
      if (method_exists($x, '_getArray')) return $x->_getArray();
      else return array_merge(get_object_vars($x), $x->row); 
    }, $objects);
  }
  
  

}

?>
