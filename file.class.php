<?php
class File {
  const KB = 1024;
  const MB = 1048576;
  const GB = 1073741824; 
  
  public static function append( $file, $stuff ) {
    $tempfile = fopen( $file, 'a' );
    fwrite( $tempfile, $stuff );
    fclose( $tempfile );
  }
  
  public static function read( $file ) {
    if (! file_exists($file)) return null;
    return file_get_contents($file);
  }
  
  public static function replace( $file, $stuff ) {
    $tempfile = fopen( $file, 'w' );
    fwrite( $tempfile, $stuff );
    fclose( $tempfile );
  }
  
  public static function md5($filename) {
    $filename = escapeshellcmd($filename);
    $result = shell_exec("md5sum '$filename'");
    list($md5sum, $shortfile) = explode(" ", $result, 2);
    return $md5sum;
  }
  
  public static function delete($filename) { @unlink($filename); }
  public static function rename($old, $new) { @rename($old, $new); }
  public static function mkdir($dir) { @mkdir($dir); }
  
  public static function listfiles($dir, $opts=array()) {
    $A = array();
    $dir = realpath($dir);
    if (! $dir_handler = @opendir($dir)) return $A;

    $type = ($opts['type'] ? $opts['type'] : 'files');
    if ($opts['smallpath'] && !$opts['basedir']) $opts['basedir'] = $dir;

    while (false !== ($filename = @readdir($dir_handler))) {
      if ($filename == '.' || $filename == '..') continue;

      if ($opts['rec'] && is_dir("$dir/$filename")) {
        $A = array_merge($A, File::listfiles("$dir/$filename", $opts));
      }

      if ($opts['regex'] && !preg_match($opts['regex'], $filename)) continue;
      if ($type == 'files' && !is_file("$dir/$filename")) continue;
      if ($type == 'dirs' && !is_dir("$dir/$filename")) continue;
      if ($type == 'symlinks' && !is_link("$dir/$filename")) continue;

      if ($opts['basedir']) $A[] = substr("$dir/$filename", strlen($opts['basedir']) + 1);
      else $A[] = "$dir/$filename";
    }

    @closedir($dir_handler);
    return $A;
  }
}

?>
