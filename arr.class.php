<?
class Arr {
  /*
  verticalSlice
  1. For an array of hashes, return an array for a particular hash key
  2. if $keyfield is given, same as above buy use that hash key as the key in new array
  
  was added in PHP 5.5 as array_column()
  */
  
  public static function verticalSlice($array, $field, $keyfield = null) {
    $array = (array) $array;
    
    $R = array();
    foreach ($array as $obj) {
      if (! array_key_exists($field, $obj)) die("verticalSlice: array doesn't have requested field\n");
      
      if ($keyfield) {
        if (! array_key_exists($keyfield, $obj)) die("verticalSlice: array doesn't have requested field\n");  
        $R[$obj[$keyfield]] = $obj[$field];
      } else { 
        $R[] = $obj[$field];
      }
    }
    return $R;
  }
  
  /*
    reIndex
    For an array of assoc rays, return a new array of assoc rays using a certain field for keys
  */
  
  public static function reIndex() {
    $fields = func_get_args();
    $array = array_shift($fields);
    $array = (array) $array;
    
    $R = array();
    foreach ($array as $obj) {
      $target =& $R;
      
      foreach ($fields as $field) {
        if (! array_key_exists($field, $obj)) die("reIndex: array doesn't have requested field\n");
        
        $nextkey = $obj[$field];
        $target =& $target[$nextkey];
      }
      $target = $obj;
    }
    return $R;
  }

  // return only those parts of the hash where the key is in keyWhitelist
  public static function keyWhitelist(array $hash, array $keyWhitelist) {
    return array_intersect_key($hash, array_flip($keyWhitelist));
  }
  
  
  public static function arrayify($obj) {
    if (! is_array($obj)) return array($obj);
    else return $obj;
  }
  
  public static function average($arr) {
    if (count($arr) == 0) return 0;
    else return array_sum($arr) / count($arr);
  }
  
  public static function valuesAsKeys($arr) {
    $A = array();
    if (! is_array($arr)) return $A;
    foreach ($arr as $item) {
      $A[$item] = 1;
    }
    return $A;
  }
  
  public static function &popRef(&$arr) {
    end($arr);
    $key = key($arr);
    $val =& $arr[$key];
    unset($arr[$key]);
    return $val;
  }
  
  public static function last(array $arr) { return end($arr); }
  public static function first(array $arr) { return $arr[0]; }
  
  // search array for value, remove any array entries that match
  public static function unsetValue($arr, $value) {
    while (($key = array_search($value, $arr)) !== false) {
      unset($arr[$key]);
    }
    
    return $arr;
  }
  
  public static function unsetList($arr, $unset) {
    foreach ($unset as $key) {
      unset($arr[$key]);
    }
    return $arr;
  }
  
  public static function unsetKey(& $arr, $key) {
    unset($arr[$key]);
  }
  
  public static function inArrayCallback($needle, $arr, $fn) {
    foreach ($arr as $member) {
      if ($fn($member, $needle)) return true;
    }
    return false;
  }
  
  public static function arrayUniqueCallback($arr, $fn) {
    $R = array();
    foreach ($arr as $member) {
      if (self::inArrayCallback($member, $R, $fn)) continue;
      $R[] = $member;
    }
    return $R;
  }
  
  public static function randomValue($arr) {
    if (!$arr || !is_array($arr)) return null;
    $index = rand(0, count($arr) - 1);
    return $arr[$index];
  }
  
  public static function search($arr, $callback) {
    $r = array();
    if (! is_array($arr) || !is_callable($callback)) return $r;
    
    foreach($arr as $val) {
      if (call_user_func($callback, $val)) $r[] = $val;
    }
    
    return $r;
  }
  
  // sort array such that its keys will appear in the order that they do in the list
  // keys not appearing in the list will be kept in the same order
  public static function keyListSort(array &$arr) {
    $args = func_get_args();
    array_shift($args);
    
    if (is_array($args[0])) $list = $args[0];
    else $list = $args;
    if (! is_array($list) || !$list) return true;
    
    $arr_keys = array_keys($arr);
    uksort($arr, function($a, $b) use ($list, $arr_keys) {
        $a_pos = array_search($a, $list);
        $b_pos = array_search($b, $list);
        if ($a_pos === false) $a_pos = count($list) + array_search($a, $arr_keys);
        if ($b_pos === false) $b_pos = count($list) + array_search($b, $arr_keys);
        
        if ($a_pos > $b_pos) return 1;
        else if ($a_pos == $b_pos) return 0;
        else return -1;
    });
  }
  
  public static function is_hash(array &$arr) {
    return ($arr !== array_values($arr));
  }

  public static function kvmap($callback, $array) {
    $array = unserialize(serialize($array)); //break embedded references

    $my_callback = function(&$val, $key) use ($callback) { $val = $callback($val, $key); };

    array_walk($array, $my_callback);
    return $array;
  }
  
  public static function digGet(array $arr, $key) {
    foreach (explode('.', $key) as $subkey) {
      if (! isset($arr[$subkey])) $arr[$subkey] = array();
      $arr =& $arr[$subkey];
    }
    
    return $arr;
  }

  // return array of all keys, with deeper keys shown as key1_key2
  public static function keysRecursive(array $arr, $base = '') {
    $keys = [];

    foreach ($arr as $key => $value) {
      if (is_array($value)) $keys = array_merge($keys, self::keysRecursive($value, $base . $key . '_'));
      else $keys[] = $base . $key;
    }
    return $keys;
  }
  
}
?>
