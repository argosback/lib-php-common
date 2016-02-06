<?php
class tempfile {
  public static $debug = false;

  public $file;
  public static $tmp_dir = null;

  function __construct($prefix, $content=null, $suffix='tmp') {
    if (self::$tmp_dir) $tmp = self::$tmp_dir;
    else if (defined('TMP_DIR')) $tmp = TMP_DIR;
    else $tmp = '/tmp';
    if (! is_dir($tmp)) mkdir($tmp, 0777, true);

    do {
      $this->file = $tmp . '/' . $prefix . self::uniqid() . '.' . $suffix;
    } while (file_exists($this->file));

    touch($this->file);
    chmod($this->file, 0666);
    $this->file = realpath($this->file);

    if ($content !== null) file_put_contents($this->file, $content);

    if (self::$debug) echo "Created tempfile {$this->file}\n";
  }

  static function uniqid() {
    static $hostname;
    static $pid;
    if (! $hostname) $hostname = gethostname();
    if (! $pid) $pid = getmypid();

    return sha1($hostname . $pid . mt_rand());
  }

  function __toString() {
    return $this->file;
  }

  function __destruct() {
    if (! self::$debug) @unlink($this->file);
  }

  function exists() {
    return file_exists($this->file);
  }
}

