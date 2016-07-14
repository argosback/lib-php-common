<?php
require_once 'simple_html_dom.php';

class Browser extends WebFetch {
  public $dom;
  public $html;
  public $pace_requests = false;
  public $last_request_ts = array();

  function __construct($url=null) {
    parent::__construct();
    if ($url) $this->go($url);
  }

  function __destruct() {
    parent::__destruct();
    if ($this->dom) $this->dom->clear();
  }

  static function get() {
    static $browser;
    if (! $browser) $browser = new Browser();
    return $browser;
  }

  public function go($url, $post=null, $options=array()) {
    $hostname = parse_url($url, PHP_URL_HOST);

    if ($this->pace_requests) {
      $time_between_requests = rand(2,4);
      $last_request_ts = intval($this->last_request_ts[$hostname]);
      $time_to_wait = $time_between_requests - (time() - $last_request_ts);

      if ($time_to_wait > 0) {
        echo "Sleeping {$time_to_wait}s before next request to $hostname..\n";
        sleep($time_to_wait);
      }
    }

    if ($this->dom) {
      $this->dom->clear();
      $this->dom = null;
    }
    $this->last_request_ts[$hostname] = time();

    if ($post) $post_str = '(' . implode(', ', Arr::kvmap(function($val, $key) { return "$key -> $val"; }, $post)) . ')';
    // echo "Requesting $url $post_str..\n";
    $this->html = parent::go($url, $post, $options);
    return $this->html;
  }

  public function dom() {
    if ($this->dom) return $this->dom;

    if ($this->html && strlen($this->html) < MAX_FILE_SIZE) {
      $lowercase = true;
      $forceTagsClosed = true;
      $target_charset = DEFAULT_TARGET_CHARSET;
      $stripRN = true;
      $defaultBRText = DEFAULT_BR_TEXT;
      $defaultSpanText = DEFAULT_SPAN_TEXT;

      
      $this->dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
      $this->dom->load($this->html, $lowercase, $stripRN);
    }

    return $this->dom;
  }
}




?>
