<?php
class Common {
  public static function callingFile() {
    $callers = debug_backtrace();
    
    foreach ($callers as $caller) {
      $file = $caller['file'];
      if (strpos($file, 'lib-php-common') !== false) continue;
      if ($file == '') continue;
      return $file; 
    }
  }
  
  static $autoload_pathlist = array();
  
  public static function autoload($class_name) {
    $list = self::$autoload_pathlist;
    $class_name = strtolower($class_name);
    
    if ($list[$class_name]) { 
      require_once $list[$class_name];
      return;
    } else {
      foreach ($list as $path) {
        if (! is_dir($path)) continue;
        
        $fullpath = $path . '/' . $class_name . '.class.php';
        if (file_exists($fullpath)) { require_once $fullpath; return; }

        if (substr($class_name, -10) == 'controller') {
          $fullpath = $path . '/' . substr($class_name, 0, -10) . '.controller.php';
          if (file_exists($fullpath)) { require_once $fullpath; return; }
        }
        
      }
    }
  }
  
  public static function errorLog($filename, $loglevel=null) {
    error_reporting(0);
    if ($loglevel === null) $loglevel = E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_STRICT;
    
    set_error_handler(function($errno, $errstr, $errfile, $errline) use ($filename) {
        if (! error_reporting()) return;

        list($errtype, $fatal) = Common::errno2name($errno);
        
        $timestamp = date('Y-m-d h:i:s a');
        $logLine = "[$timestamp]: $errtype - $errstr - $errfile - line $errline\n";
        file_put_contents($filename, $logLine, FILE_APPEND); 
        die("This website encountered an error!");
    }, $loglevel);

  }

  public static function errno2name($errno) {
    $errtype = '';
    $fatal = false;

    if ($errno & E_ERROR) { $errtype = 'E_ERROR'; $fatal = true; }
    else if ($errno & E_PARSE) { $errtype = 'E_PARSE'; $fatal = true; }
    else if ($errno & E_CORE_ERROR) { $errtype = 'E_CORE_ERROR'; $fatal = true; }
    else if ($errno & E_CORE_WARNING) { $errtype = 'E_CORE_WARNING'; $fatal = true; }
    else if ($errno & E_COMPILE_ERROR) { $errtype = 'E_COMPILE_ERROR'; $fatal = true; }
    else if ($errno & E_COMPILE_WARNING) { $errtype = 'E_COMPILE_WARNING'; $fatal = true; }
    
    else if ($errno & E_WARNING) { $errtype = 'E_WARNING'; }
    else if ($errno & E_NOTICE) { $errtype = 'E_NOTICE'; }
    else if ($errno & E_USER_ERROR) { $errtype = 'E_USER_ERROR'; }
    else if ($errno & E_USER_WARNING) { $errtype = 'E_USER_WARNING'; }
    else if ($errno & E_USER_NOTICE) { $errtype = 'E_USER_NOTICE'; }
    else if ($errno & E_STRICT) { $errtype = 'E_STRICT'; }
    else if ($errno & E_RECOVERABLE_ERROR) { $errtype = 'E_RECOVERABLE_ERROR'; }
    else if ($errno & E_DEPRECATED) { $errtype = 'E_DEPRECATED'; }
    else if ($errno & E_USER_DEPRECATED) { $errtype = 'E_USER_DEPRECATED'; }

    return [$errtype, $fatal];
  }

  public static function signal2name($signal) {
    $constants = get_defined_constants(true)['pcntl'];

    foreach ($constants as $name => $number) {
      if (! preg_match('/\ASIG[A-Z]{2,6}\z/', $name)) continue;
      if ($signal == $number) return $name;
    }
  }
  
  public static function setautoload($pathlist) {
    self::$autoload_pathlist = $pathlist;
    spl_autoload_register('Common::autoload');
  }

  public static function addautoload($path) {
    self::$autoload_pathlist[] = $path;
  }
  
  
  public static function hex2str($hex) {
    for($i=0;$i<strlen($hex);$i+=2) $str.=chr(hexdec(substr($hex,$i,2)));
    return $str;
  }
  
  
  
  public static function shiftCount(&$string, $chars, $plusOne = false) {
    if ($chars === false) $chars = strlen($string);
    else if ($plusOne) $chars++;
    
    $ret = substr($string, 0, $chars);
    $string = substr($string, $chars);
    return $ret;
  }
  
  public static function shiftToChar(&$string, $target, $plusOne = false) {
    $target_pos = 1000000;
    if (is_array($target)) {
      foreach ($target as $one_target) {
        if (stripos($string, $one_target) === false) continue;
        else $target_pos = min($target_pos, stripos($string, $one_target));
      }
      if ($target_pos == 1000000) $target_pos = false;
    } else {
      $target_pos = stripos($string, $target);
    }
    
    return self::shiftCount($string, $target_pos, $plusOne);
  }
  
  public static function myOr() {
    foreach (func_get_args() as $arg) {
      if ($arg) return $arg;
    }
    return array_pop(func_get_args());
  }
  
  public static function myOrNotEmpty() {
    foreach (func_get_args() as $arg) {
      if (strval($arg) != '') return $arg;
    }
    return array_pop(func_get_args());
  }
  
  function gzdecode($data){
    $g=tempnam('/tmp','ff');
    @file_put_contents($g,$data);
    ob_start();
    readgzfile($g);
    $d=ob_get_clean();
    @unlink($g);
    return $d;
  }
  

  public static function inRange() { return call_user_func_array('Math::inRange', func_get_args()); }

  function wildcardMatch($wildcard_str, $haystack, $options=array()) {
    $chunks = preg_split("/([\*\?])/", $wildcard_str, -1, PREG_SPLIT_DELIM_CAPTURE);
    $preg_str = '';
    foreach ($chunks as $chunk) {
      if ($chunk == '') continue;
      
      if ($chunk == '*') $preg_str .= '(.*?)';
      else if ($chunk == '?') $preg_str .= '(.)';
      else $preg_str .= '(' . preg_quote($chunk, '/') . ')';
    }
    
    $preg_str = '/^' . $preg_str . '$/i';
    echo "$preg_str\n";
    if (! preg_match($preg_str, $haystack, $match)) return false;
    else return $match;
  }
  
  function line_split($text) {
    return preg_split('/[\n\r]+/', $text);
  }
  
  public static function numberStripDouble($str) {
    return doubleval(preg_replace('/[^0-9\.]/', '', $str));
  }
  
  public static function readlinedefault($question, $default) {
    $res = readline("$question [$default] ");
    if (! $res) $res = $default;
    return $res;
  }
  
  public static function is_webpage() {
    if (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) return false;
    else return true;
  }
  
  public static function filesize_human($size) {
    $mod = 1024;
 
    $units = explode(' ','B KB MB GB TB PB');
    for ($i = 0; $size > $mod; $i++) {
        $size /= $mod;
    }
 
    return round($size, 2) . ' ' . $units[$i];
  }

  public static function auto_retry($fn, array $params, $max_tries) {
    $tries = 0;

    while (true) {
      try {
        $r = call_user_func_array($fn, $params);
        return $r;
      } catch (Exception $e) {
        $tries++;
        if ($tries >= $max_tries) throw $e;
        else sleep(pow(2, $tries-1));
      }
    }
  }

  public static function parse_exception(Exception $e) {
    $type = get_class($e);
    $message = $e->getMessage();

    foreach ($e->getTrace() as $traceline) {
      if (substr_count($traceline['file'], '/vendor/')) continue;
      $file = $traceline['file'];
      $line = $traceline['line'];
      break;
    }

    if (! $file) {
      $file = $e->getFile();
      $line = $e->getLine();
    }
    
    return "$type on line $line of $file: $message";
  }

  public static function strStartsWith() { return call_user_func_array('Str::startsWith', func_get_args()); }
  public static function strEndsWith() { return call_user_func_array('Str::endsWith', func_get_args()); }
  public static function strcontains() { return call_user_func_array('Str::contains', func_get_args()); }
  public static function randomString() { return call_user_func_array('Str::random', func_get_args()); }
  
  public static function verticalSlice() { return call_user_func_array('Arr::verticalSlice', func_get_args()); }
  public static function reIndex() { return call_user_func_array('Arr::reIndex', func_get_args()); }
  public static function arrayify($obj) { return call_user_func_array('Arr::arrayify', func_get_args()); }
  public static function arrayAverage($arr) { return call_user_func_array('Arr::average', func_get_args()); }
  public static function arrayValuesAsKeys($arr) { return call_user_func_array('Arr::valuesAsKeys', func_get_args()); }
  public static function arrayUnset($arr, $unset) { return call_user_func_array('Arr::unsetList', func_get_args()); }
  public static function arrayLast($arr) { return call_user_func_array('Arr::last', func_get_args()); }
  public static function arrayFirst($arr) { return call_user_func_array('Arr::first', func_get_args()); }
  public static function arrayUnsetKey(& $arr, $key) { return call_user_func_array('Arr::unsetKey', func_get_args()); }
  public static function inArrayCallback($needle, $arr, $fn) { return call_user_func_array('Arr::inArrayCallback', func_get_args()); }
  public static function arrayUniqueCallback($arr, $fn) { return call_user_func_array('Arr::arrayUniqueCallback', func_get_args()); }
  
}


?>
