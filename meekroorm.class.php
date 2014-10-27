<?
/*
  CREATE TABLE `users_params` (
   `id` bigint(20) unsigned NOT NULL,
   `key` varchar(255) NOT NULL,
   `value` varchar(255) NOT NULL,
   `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
   PRIMARY KEY (`id`,`key`),
   KEY `expires_at` (`expires_at`)
  ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
*/

class MeekroORM implements ArrayAccess {
  // INTERNAL -- DO NOT TOUCH
  protected $_orm_row = array();
  protected $_orm_row_orig = array();
  protected $_orm_cache = array();
  protected static $_orm_inferred_tablestruct = array();

  // (OPTIONAL) SET IN INHERITING CLASS
  // static so they apply to all instances
  protected static $_orm_tablename = null;
  protected static $_orm_tablename_params = null;
  protected static $_orm_tablestruct = array(); // cache tablestruct
  protected static $_orm_associations = array();


  // -------------- INFER TABLE STRUCTURE
  public static function _orm_infer_tablestruct() {
    $table = static::_orm_meekrodb()->query("DESCRIBE %b", static::_orm_tablename());
    $struct = array();
    
    foreach ($table as $row) {
      $row['Type'] = preg_split('/\W+/', $row['Type'], -1, PREG_SPLIT_NO_EMPTY);
      $struct[$row['Field']] = $row;
    }

    static::$_orm_inferred_tablestruct[static::_orm_tablename()] = $struct;
  }

  // -------------- SIMPLE HELPER FUNCTIONS
  public static function _orm_tablename() {
    if (static::$_orm_tablename) return static::$_orm_tablename;
    else return strtolower(get_called_class());
  }

  public static function _orm_tablename_params() {
    if (static::$_orm_tablename_params) return static::$_orm_tablename_params;
    else return static::_orm_tablename() . '_params';
  }

  public static function _orm_meekrodb() { return DB::getMDB(); }
  public static function _orm_primary_key() { $keys = static::_orm_primary_keys(); return $keys[0]; }
  public function _orm_primary_key_value() { return $this->_orm_row[static::_orm_primary_key()]; }

  public static function _orm_primary_keys() {
    $data = array_filter(static::_orm_tablestruct(), function($x) { return $x['Key'] == 'PRI'; });
    if (! $data) throw new Exception(static::_orm_tablename() . " doesn't seem to have any primary keys!");

    return array_keys($data);
  }

  public static function _orm_is_primary_key($key) {
    $struct = static::_orm_tablestruct();
    return ($struct[$key]['Key'] === 'PRI');
  }

  public static function _orm_tablestruct() {
    if (static::$_orm_tablestruct) return static::$_orm_tablestruct;
    if (! static::$_orm_inferred_tablestruct[static::_orm_tablename()]) static::_orm_infer_tablestruct();

    return static::$_orm_inferred_tablestruct[static::_orm_tablename()];
  }

  public static function _orm_auto_increment_field() {
    $data = array_filter(static::_orm_tablestruct(), function($x) { return $x['Extra'] == 'auto_increment'; });
    if (! $data) return null;
    $data = array_values($data);
    return $data[0]['Field'];
  }

  public static function _orm_format_value($column, $value) {
    $struct = static::_orm_tablestruct();
    $type = strval($struct[$column]['Type'][0]);
    
    if (is_null($value)) return null;
    if (substr($type, -3) == 'int') return intval($value);
    if ($type == 'float' || $type == 'double' || $type == 'decimal') return doubleval($value);

    return strval($value);
  }

  protected function _orm_dirty_fields() {
    $dirty = array();
    foreach ($this->_orm_row as $key => $value) {
      if ($value !== $this->_orm_row_orig[$key]) $dirty[] = $key;
    }
    return $dirty;
  }

  protected function _where() {
    $where = new WhereClause('and');

    foreach (static::_orm_primary_keys() as $key) {
      $where->add('%b = %?', $key, $this->_attribute_get($key));
    }
    
    return $where;
  }

  protected function _orm_run_callback() {
    $args = func_get_args();
    $func_name = array_shift($args);
    $func_call = array($this, $func_name);
    if (is_callable($func_call)) return call_user_func_array($func_call, $args);
    return false;
  }


  public function _orm_is_fresh() { return !$this->_orm_row_orig; }

  public function _attribute_set($key, $value) {
    if ($this->_attribute_exists($key)) {
      $value = $this->_orm_format_value($key, $value);
      return $this->_orm_row[$key] = $value;
    } else {
      return $this->$key = $value;
    }
  }

  public function _attribute_get($key) {
    if ($this->_attribute_exists($key)) {
      return $this->_orm_row[$key];
    } else {
      return $this->$key;
    }
  }
  
  public function _attribute_exists($key) { return array_key_exists($key, static::_orm_tablestruct()); }

  // -------------- ASSOCIATIONS
  protected function _cache_set($key, $value) { return $this->_orm_cache[$key] = $value; }
  protected function _cache_get($key) { return $this->_orm_cache[$key]; }

  protected static function _is_association($name) { return array_key_exists($name, static::$_orm_associations); }

  protected static function _get_association($name) {
    $assoc = static::$_orm_associations[$name];
    if (! $assoc) throw new Exception("The association $name doesn't exist!");

    if (! $assoc['class_name']) $assoc['class_name'] = $name;
    if (! $assoc['foreign_key']) $assoc['foreign_key'] = strtolower($name) . '_id';
    $assoc['primary_key'] = call_user_func(array($assoc['class_name'], '_orm_primary_key'));
    $assoc['table_name'] = call_user_func(array($assoc['class_name'], '_orm_tablename'));

    return $assoc;
  }

  protected function _load_association($name) {
    if ($this->_cache_get($name)) return $this->_cache_get($name);

    $assoc = static::_get_association($name);
    $class_name = $assoc['class_name'];
    $foreign_key = $assoc['foreign_key'];

    if ($assoc['type'] == 'belongs_to') {
      $result = $class_name::Search(array(
        $assoc['primary_key'] => $this->$foreign_key,
      ));
      $this->_cache_set($name, $result);

    } else if ($assoc['type'] == 'has_one') {
      $result = $class_name::Search(array(
        $assoc['foreign_key'] => $this->_orm_primary_key_value(),
      ));
      $this->_cache_set($name, $result);

    } else if ($assoc['type'] == 'has_many') {
      $result = $class_name::SearchMany(array(
        $assoc['foreign_key'] => $this->_orm_primary_key_value(),
      ));
      $this->_cache_set($name, $result);

    } else {
      throw new Exception("Invalid type for $name association");
    }

    return $this->_cache_get($name);
  }


  // -------------- ARRAY ACCESS
  public function offsetGet($offset) { return $this->__get($offset); }
  public function offsetSet($offset, $value) { $this->__set($offset, $value); }
  public function offsetExists($offset) { return ($this->offsetGet($offset) !== null); }
  public function offsetUnset($offset) { $this->__set($offset, null); }

  // -------------- CONSTRUCTORS
  public function __construct(array $row = array(), $loaded = false) {
    foreach ($row as $key => $value) {
      if (! $loaded) $this->$key = $value; // run any setters and getters
      else $this->_attribute_set($key, $value); // merely run strval,doubleval,etc as needed
    }
    
    if ($loaded) $this->_orm_row_orig = $this->_orm_row;
  }

  // alias for "new $class", kept for backwards compatibility
  public static function Build(array $row = array(), $loaded = false) {
    $name = get_called_class();
    return new $name($row, $loaded);
  }

  public static function BuildMany(array $rows, $loaded = false) {
    $many = array();
    foreach ($rows as $row) {
      $many[] = static::Build($row, $loaded);
    }
    return $many;
  }

  public static function Load() {
    $keys = static::_orm_primary_keys();
    $values = func_get_args();
    if (count($values) != count($keys)) throw new Exception("Load on " . static::_orm_tablename() . " must be called with " . count($keys) . " parameters!");

    return static::Search(array_combine($keys, $values));
  }

  protected static function _orm_query_from_hash(array $hash, $one) {
    $where = new WhereClause('and');
    foreach ($hash as $key => $value) {
      if (is_array($value)) $where->add('%b IN %l?', $key, array_values($value));
      else $where->add('%b=%?', $key, $value);
    }

    $query = "SELECT * FROM %b WHERE %l";
    if ($one) $query .= " LIMIT 1";

    return array($query, static::_orm_tablename(), $where);
  }

  public static function Search() {
    static::_orm_tablestruct(); // infer the table structure first in case we run FOUND_ROWS()

    $args = func_get_args();
    if (is_array($args[0])) $args = static::_orm_query_from_hash($args[0], true);

    $row = call_user_func_array(array(static::_orm_meekrodb(), 'queryFirstRow'), $args);
    if (is_array($row)) return static::Build($row, true);
    else return null;
  }

  public static function SearchMany() {
    static::_orm_tablestruct(); // infer the table structure first in case we run FOUND_ROWS()

    $args = func_get_args();
    if (is_array($args[0])) $args = static::_orm_query_from_hash($args[0], false);

    $rows = call_user_func_array(array(static::_orm_meekrodb(), 'query'), $args);
    if (is_array($rows) && count($rows)) return static::BuildMany($rows, true);
    else return array();
  }


  // -------------- DYNAMIC METHODS
  public function __set($key, $value) {
    if (!$this->_orm_is_fresh() && $this->_orm_is_primary_key($key)) {
      throw new MeekroORMException("Can't update primary key!");
    } else if (array_key_exists($key, static::_orm_tablestruct())) {
      
      $callback = $this->_orm_run_callback("_set_$key", $value);
      if ($callback === false) $this->_attribute_set($key, $value);

    } else {
      $this->$key = $value;
    }
  }

  public function __get($key) {
    if ($this->_cache_get($key)) {
      return $this->_cache_get($key);

    } else if (static::_is_association($key)) {
      return $this->_load_association($key);

    } else if (array_key_exists($key, static::_orm_tablestruct())) {

      $callback = $this->_orm_run_callback("_get_$key");
      if ($callback !== false) return $callback;
      else return $this->_attribute_get($key);

    } else if (is_callable(array($this, $key))) {
      $result = call_user_func(array($this, $key));
      return $this->_cache_set($key, $result);

    } else {
      return $this->$key;
    }
  }

  public function save() {
    $is_fresh = $this->_orm_is_fresh(); // this will stop being true throughout, we need the original
    $dirty_fields = $this->_orm_dirty_fields();

    $this->_orm_run_callback('_pre_save', $dirty_fields);
    if ($is_fresh) $this->_orm_run_callback('_pre_create', $dirty_fields);
    else $this->_orm_run_callback('_pre_update', $dirty_fields);

    // dirty fields list might change during _pre_* and must be re-calculated
    $dirty_fields = $this->_orm_dirty_fields();

    $replace = array();
    foreach ($dirty_fields as $field) {
      $replace[$field] = $this->_attribute_get($field);
    }

    if ($is_fresh) {
      static::_orm_meekrodb()->insert(static::_orm_tablename(), $replace);

      if ($aifield = static::_orm_auto_increment_field()) {
        $this->_orm_row[$aifield] = static::_orm_meekrodb()->insertId();
      }
      
    } else if (count($replace) > 0) {
      static::_orm_meekrodb()->update(static::_orm_tablename(), $replace, "%l", $this->_where());

    }
    
    $this->_orm_row_orig = $this->_orm_row;
    if ($is_fresh) $this->reload(); // for INSERTs, pick up any default values that MySQL may have set

    if ($is_fresh) $this->_orm_run_callback('_post_create', $dirty_fields);
    else $this->_orm_run_callback('_post_update', $dirty_fields);
    $this->_orm_run_callback('_post_save', $dirty_fields);
  }

  public function reload() {
    if ($this->_orm_is_fresh()) throw new MeekroORMException("Can't reload unsaved record!");

    $primary_values = array();
    foreach (static::_orm_primary_keys() as $key) {
      $primary_values[] = $this->_attribute_get($key);
    }

    $new = call_user_func_array(array(get_called_class(), "Load"), $primary_values);
    $this->_orm_row = $this->_orm_row_orig = $new->_orm_row;
  }

  public function update($key, $value=null) {
    if (is_array($key)) $hash = $key;
    else $hash = array($key => $value);
    //$dirty_fields = array_keys($hash);

    $this->_orm_row = array_merge($this->_orm_row, $hash);

    if (! $this->_orm_is_fresh()) {
      //$this->_orm_run_callback('_pre_save', $dirty_fields);
      //$this->_orm_run_callback('_pre_update', $dirty_fields);

      static::_orm_meekrodb()->update(static::_orm_tablename(), $hash, "%l", $this->_where());
      $this->_orm_row_orig = array_merge($this->_orm_row_orig, $hash);

      //$this->_orm_run_callback('_post_update', $dirty_fields);
      //$this->_orm_run_callback('_post_save', $dirty_fields);
    }
  }

  public function destroy() {
    $this->_orm_run_callback('_pre_destroy');
    static::_orm_meekrodb()->query("DELETE FROM %b WHERE %l LIMIT 1", static::_orm_tablename(), $this->_where());
    $this->_orm_run_callback('_post_destroy');
  }

  // must already be in transaction
  public function lock() {
    static::_orm_meekrodb()->query("SELECT %b FROM %b WHERE %l LIMIT 1 FOR UPDATE", 
      static::_orm_primary_key(), static::_orm_tablename(), $this->_where());

    $this->reload();
  }

  public function toHash() {
    return $this->_orm_row;
  }



  // -------------- PARAMS
  public function setparam($key, $value, $ttl=0) {
    static::_orm_meekrodb()->replace(static::_orm_tablename_params(), array(
      'id' => $this->_orm_primary_key_value(),
      'key' => strval($key),
      'value' => strval($value),
      'expires_at' => $ttl ? static::_orm_meekrodb()->sqleval('DATE_ADD(NOW(), INTERVAL %i SECOND)', $ttl) : 0
    ));
  }

  public function param($key) {
    return static::_orm_meekrodb()->queryFirstField("SELECT value FROM %b WHERE id=%i AND `key`=%s AND (expires_at=0 OR expires_at > NOW())", 
      static::_orm_tablename_params(), $this->_orm_primary_key_value(), $key);
  }

  public function unsetparam($key) {
    return static::_orm_meekrodb()->query("DELETE FROM %b WHERE id=%i AND `key`=%s", 
      static::_orm_tablename_params(), $this->_orm_primary_key_value(), $key);
  }

  public function unsetallparams() {
    return static::_orm_meekrodb()->query("DELETE FROM %b WHERE id=%i", 
      static::_orm_tablename_params(), $this->_orm_primary_key_value());
  }
  
  

}

class MeekroORMException extends Exception { }

?>
