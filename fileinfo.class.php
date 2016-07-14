<?php
class FileInfo {
  public static function fileCommand($path) {
    $result = Shell::exec("file " . escapeshellarg($path));
    $result = strtolower(trim($result));
    list($filename, $data) = explode(": ", $result, 2);
    return $data;
  }
  
  public static function imageIdentify($path) {
    if (! file_exists($path)) throw new Exception("FileInfo::imageIdentify called with non-existent path");
    
    $output = explode("\n", Shell::exec("identify " . escapeshellarg($path)));
    $line1 = $output[0];
    $words = explode(' ', $line1);
    
    $dimensions = $words[2];
    list($width, $height) = explode('x', $dimensions);
    
    if (! Str::startsWith($words[0], $path)) return null;
    
    if (preg_match('/^\S+\[\d+\]/', $line1)) $is_animated = true;
    else $is_animated = false;
    
    return array(
      'format' => strtolower($words[1]),
      'dimensions' => $dimensions,
      'width' => $width,
      'height' => $height,
      'is_animated' => $is_animated ? 1 : 0,
      'frames' => $is_animated ? count($output) - 1 : 1
    );
  }
  
  public static function getInfo($path) {
    if (! file_exists($path)) throw new Exception("Trying to get info on non-existant file");
    $info = $results['info_string'] = self::fileCommand($path);
    
    clearstatcache();
    $results['size'] = filesize($path);
    $results['size_human'] = Common::filesize_human($results['size']);
    
    $formatlist = array(
      array('type' => 'audio', 'format' => 'webm', 'mime' => 'audio/webm', 'ext' => 'weba'),
      array('type' => 'video', 'format' => 'webm', 'mime' => 'video/webm', 'ext' => 'webm'),
      
      array('type' => 'document', 'format' => 'pdf', 'regexp' => '/pdf document/', 'mime' => 'application/pdf'),
      array('type' => 'document', 'format' => 'office', 'regexp' => '/cdf v2 document/', 'ext' => 'doc', 'mime' => 'application/msword'),
      
      array('type' => 'archive', 'format' => 'zip', 'regexp' => '/zip archive data/', 'mime' => 'application/x-compressed'),
      array('type' => 'archive', 'format' => 'tar', 'regexp' => '/tar archive/', 'mime' => 'application/x-tar'),
      array('type' => 'archive', 'format' => 'rar', 'regexp' => '/rar archive/', 'mime' => 'application/x-compressed'),
      array('type' => 'archive', 'format' => 'gz', 'regexp' => '/gzip compressed data/', 'mime' => 'application/x-compressed'),
      array('type' => 'archive', 'format' => 'iso', 'regexp' => '/iso 9660/'),
      
      array('type' => 'text', 'format' => 'text', 'regexp' => '/text/', 'ext' => 'txt', 'mime' => 'text/plain'),
      array('type' => 'empty', 'regexp' => '/empty/'),
    );
    
    
    foreach ($formatlist as $filehash) {
      if (! $filehash['regexp']) continue;
      if (! preg_match($filehash['regexp'], $info)) continue;
      
      $results['type'] = $filehash['type'];
      $results['format'] = $filehash['format'];
      break;
    }
    
    if (!$results['type'] || $results['type'] == 'image') {
      if ($image_info = FileInfo::imageIdentify($path)) {
        $results = array_merge($results, $image_info, array('type' => 'image'));
        unset($results['info_string']);
      }
    }
    
    /*if (!$results['type'] || $results['type'] == 'audio' || $results['type'] == 'video') {
      if ($ffmpeg_info = ffmpeg::info($path)) {
        $results = array_merge($results, $ffmpeg_info);
        unset($results['info_string']);
      }
    }*/
    
    if ($results['width'] && $results['height']) {
      $results['dimensions'] = $results['width'] . 'x' . $results['height']; 
    }
    
    if ($results['type'] && $results['format']) {
      $type = $results['type'];
      $format = $results['format'];
      list($entry) = array_filter($formatlist, function($x) use ($type, $format) { 
        return ($x['type'] == $type && $x['format'] == $format); 
      });
      
      if ($entry['mime']) $results['mime'] = $entry['mime'];
      if ($entry['ext']) $results['ext'] = $entry['ext'];
      
      if (! $results['mime'] && ($type == 'image' || $type == 'audio' || $type == 'video')) {
        $results['mime'] = "$type/$format";
      }
      
      if (! $results['ext']) {
        if (strlen($format) == 2 || strlen($format) == 3) $results['ext'] = $format;
        else if (strlen($format) == 4) $results['ext'] = $format[0] . $format[1] . $format[3];
      }
    }

    $results['md5'] = File::md5($path);
    
    //if ($results['ext'] && $results['ext'][0] != '.') $results['ext'] = '.' . $results['ext'];
    
    if ($results['info_string'] == 'data' && !$results['type']) $results['type'] = 'binary';
    
    Arr::keyListSort($results, 'type', 'format', 'mime', 'ext', 'size', 'size_human', 'width', 'height', 'dimensions');
    return $results;
  }
  
  
}
?>
