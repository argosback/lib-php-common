<?
class WebFetch {
  function __construct() {
    $this->crl = curl_init();
    $this->crl_optsset = array();
  }
  
  function __destruct() {
    curl_close($this->crl);
    $this->crl = null;
  }
  
  public function go($url, $post=null, $options=array()) {
    $options['crl'] = $this->crl;
    $options['crl_optsset'] =& $this->crl_optsset;
    return self::fetch($url, $post, $options);
  }
  
  public static function fetch($url, $post=null, $options=array()) {
    $retry_times = isset($options['retry']) ? intval($options['retry']) : 5;
    $options['timeout'] = isset($options['timeout']) ? $options['timeout'] : 30;
    
    if (isset($options['curlopts']) && is_array($options['curlopts'])) $curlopts = $options['curlopts'];
    $curlopts[CURLOPT_RETURNTRANSFER] = 1;
    $curlopts[CURLOPT_FOLLOWLOCATION] = 1;
    $curlopts[CURLOPT_DNS_USE_GLOBAL_CACHE] = 0;
    $curlopts[CURLOPT_MAXREDIRS] = 10;
    
    // carry temporary cookies by default
    if (! isset($curlopts[CURLOPT_COOKIEFILE])) $curlopts[CURLOPT_COOKIEFILE] = ''; 
    
    if (! (isset($options['strongSSL']) && $options['strongSSL'])) {
      $curlopts[CURLOPT_SSL_VERIFYHOST] = 0;
      $curlopts[CURLOPT_SSL_VERIFYPEER] = 0;  
    }
    
    if (isset($options['sourceIP'])) $curlopts[CURLOPT_INTERFACE] = $options['sourceIP'];
    if (isset($options['proxy'])) $curlopts[CURLOPT_PROXY] = $options['proxy'];
    if (isset($options['referrer'])) $curlopts[CURLOPT_REFERER] = $options['referrer'];
    if (isset($options['verbose']) && $options['verbose']) $curlopts[CURLOPT_VERBOSE] = true;
    if (isset($options['userAgent'])) {
      if ($options['userAgent'] == 'random') $curlopts[CURLOPT_USERAGENT] = self::randomUserAgent();
      else if ($options['userAgent']) $curlopts[CURLOPT_USERAGENT] = $options['userAgent'];
    }
    
    if (isset($options['getCookies']) && $options['getCookies']) {
      $curlopts[CURLOPT_HEADER] = true;
    }
    
    if (is_array($post) && !count($post)) {
      $post = null;
    }
    
    if (isset($options['method']) && $options['method'] == 'get' && is_array($post)) {
      $url .= '?' . http_build_query($post);
      $post = null;
    }
    
    $curlopts[CURLOPT_URL] = $url;
    $curlopts[CURLOPT_CONNECTTIMEOUT] = $curlopts[CURLOPT_TIMEOUT] = $options['timeout'];
    
    if (! (isset($options['minheaders']) && $options['minheaders'])) {
      $curlopts[CURLOPT_HTTPHEADER][] = 'Connection: keep-alive';
      $curlopts[CURLOPT_HTTPHEADER][] = 'Keep-Alive: 300';
      $curlopts[CURLOPT_HTTPHEADER][] = 'Cache-Control: max-age=0';
    }
    
    if (isset($options['headers'])) {
      if (! is_array($options['headers'])) $options['headers'] = array($options['headers']);
      foreach ($options['headers'] as $header) {
        $curlopts[CURLOPT_HTTPHEADER][] = $header;
      }
    }
    
    if (is_array($post)) $post = http_build_query($post);
    if ($post !== null) {
      $curlopts[CURLOPT_POSTFIELDS] = $post;
      $curlopts[CURLOPT_POST] = true;
    }
    
    if (! ($crl = $options['crl'])) {
      $crl = curl_init();
      $will_close_crl = true;
    } else {
      $will_close_crl = false;
    }
    
    // Since curl remembers opts, we have to reset them
    if (isset($options['crl_optsset']) && is_array($options['crl_optsset'])) {
      $options['crl_optsset'] = array_unique(array_merge(array_keys($curlopts), $options['crl_optsset']));
      foreach (array_diff($options['crl_optsset'], array_keys($curlopts)) as $key) {
        $curlopts[$key] = '';
      }
    }
    
    curl_setopt_array($crl, $curlopts);
    $ct = 0;
    
    do {
      if ($ct > 0) sleep(pow($ct, 2));
      
      $output = curl_exec($crl);
      
      if (curl_error($crl)) {
        throw new Exception("CURL_ERROR " . curl_error($crl));
      } else if (! $output) {
        echo "CURL got empty output -- will retry!";
        $output = null;
      }
      
    } while ($output === null && $ct++ < $retry_times);
    
    if ($will_close_crl) curl_close($crl);
    
    if (isset($options['getCookies']) && $options['getCookies']) {
      list($headers, $body) = explode("\r\n\r\n", $output, 2);
      $output = $body;
      
      $cookies = array();
      if (preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $match)) {
        foreach ($match[1] as $cookiestr) {
          list($key, $val) = explode('=', $cookiestr, 2);
          $cookies[$key] = $val;
        }
      }
      
      return array(
        'output' => $output, 
        'cookies' => $cookies
      );
    }
    
    return $output;
  }
  
  protected static function randomUserAgent() {
    $useragents = array(
      'Mozilla/5.0 (Windows; U; Windows NT 5.2; en-US; rv:1.9.2.9) Gecko/20100824 Firefox/3.6.9',
      'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.2.9) Gecko/20100824 Firefox/3.6.9 ( .NET CLR 3.5.30729; .NET CLR 4.0.20506)',
      'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.16 (KHTML, like Gecko) Chrome/10.0.648.134 Safari/534.16',
      'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/534.16 (KHTML, like Gecko) Chrome/10.0.648.134 Safari/534.16',
      'Opera/9.80 (Windows NT 6.1; U; en-US) Presto/2.7.62 Version/11.01',
      'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; Media Center PC 6.0; InfoPath.2; MS-RTC LM 8)',
      'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27',
    );
    shuffle($useragents);
    return $useragents[0];
  }

}