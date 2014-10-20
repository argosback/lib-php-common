<?
class Validator {
  
  public static function length($data, $minLength, $maxLength) {
    $length = strlen($data);
    if ($length >= $minLength && $length <= $maxLength) return true;
    else return false;
  }

  public static function email($email, $checkResolve = false) {
    $email = strtolower(trim($email));
    
    // general checks
    if (strlen($email) < 1 || strlen($email) >= 255) return false;
    if (substr_count($email, '@') != 1) return false;
    
    list($user, $host) = explode('@', $email, 2);
    
    // verify user part
    if (strlen($user) < 1) return false;
    if (preg_match('/[^\w\-\.\+]/', $host)) return false;
    
    // verify host part
    if (strlen($host) < 3) return false;
    if (substr_count($host, '.') < 1) return false;
    if (preg_match('/[^\w\-\.]/', $host)) return false;
    
    
    if ($checkResolve) {
      $ip = @gethostbyname($host);
      if (!$ip || $ip == $host) return false;
    }
    
    return $email;
  }
  
  public static function USPhone($number) {
    $number = preg_replace('/\D/', '', $number);
    if (strlen($number) != 10) return null;
    else return $number;
  }

  public static function domain($domain) {
    list($name, $tld) = explode('.', $domain, 2);

    if (! preg_match('/\A[a-z]{1,60}\z/i', $name)) return false;
    if (! preg_match('/\A[a-z]{1,15}\z/i', $tld)) return false;

    return true;
  }

  public static function bitcoin($address) {
    if (preg_match("/\A(1|3)[a-z0-9]{26,33}\z/i", $address)) return true;
    return false;
  }
}


?>
