<?
class Str {
  public static function simpleName($name, $lowercase=true) {
    if ($lowercase) $name = strtolower($name);

    $name = preg_replace("/(\A\W+)|(\W+\z)|(\')/", "", $name);
    $name = preg_replace("/\W+/", "_", $name);
    
    return $name;
  }

  public static function startsWith($haystack, $needle) {
    return (substr($haystack, 0, strlen($needle)) == $needle);
  }
  
  public static function endsWith($haystack, $needle) {
    return (substr($haystack, -1 * strlen($needle)) == $needle);
  }

  public static function leftShift(&$string, $length) {
    $remainder = substr($string, 0, $length);
    $string = substr($string, $length);
    return $remainder;
  }

  public static function contains($haystack_list, $needle_list, $sensitive = true) {
    $needle_list = Arr::arrayify($needle_list);
    $haystack_list = Arr::arrayify($haystack_list);
    
    if (! $sensitive) {
      $needle_list = array_map('strtolower', $needle_list);
      $haystack_list = array_map('strtolower', $haystack_list);
    }
    
    foreach ($haystack_list as $haystack) {
      foreach ($needle_list as $needle) {
        if (substr_count($haystack, $needle) > 0) return true;
      }
    }
    
    return false;
  }

  public static function random($length = 7, $chars = "abcdefghijkmnopqrstuvwxyz023456789", $func = null) {
    if (! is_callable($func)) $func = function($x) { return true; };
    
    do {
      $pass = '';
      for ($i = 0; $i < $length; $i++) {
        $tmp = substr($chars, rand(0, strlen($chars) - 1), 1);
        $pass = $pass . $tmp;
      }
    } while (! $func($pass));
    
    return $pass;
  }
}
