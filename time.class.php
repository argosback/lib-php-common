<?php
class Time {
  const MYSQL_TIME_FORMAT = 'Y-m-d H:i:s';
  const SECOND = 1;
  const MINUTE = 60;
  const HOUR = 3600;
  const DAY = 86400;
  const WEEK = 604800;
  const MONTH = 2629743;
  const YEAR = 31556926;
  const ZERO = -62169984000;
  
  public static $timezones = array(
    '-12' => 'Baker Island Time',
    '-11' => 'Samoa Standard Time',
    '-10' => 'Hawaii Standard Time',
    '-9.5' => 'Marquesas Islands Time',
    '-9' => 'Alaska Time',
    '-8' => 'Pacific Standard Time',
    '-7' => 'Mountain Standard Time',
    '-6' => 'Central Standard Time',
    '-5' => 'Eastern Standard Time',
    '-4.5' => 'Venezuelan Standard Time',
    '-4' => 'Atlantic Standard Time',
    '-3.5' => 'Newfoundland Standard Time',
    '-3' => 'Brasilia Time',
    '-2.5' => 'Newfoundland Standard Time',
    '-2' => 'South Georgia Time',
    '-1' => 'Cape Verde Time',
    '0' => 'Greenwich Mean Time',
    '1' => 'Central European Time',
    '2' => 'Eastern European Time',
    '3' => 'East Africa Time',
    '3.5' => 'Iran Standard Time',
    '4' => 'Gulf Standard Time',
    '4.5' => 'Afghanistan Time',
    '5' => 'Pakistan Standard Time',
    '5.5' => 'Indian Standard Time',
    '5.75' => 'Nepal Time',
    '6' => 'Bangladesh Standard Time',
    '6.5' => 'Myanmar Standard Time',
    '7' => 'Thailand Standard Time',
    '8' => 'Hong Kong Time',
    '8.75' => 'Western Australia Time',
    '9' => 'Japan Standard Time',
    '9.5' => 'Australian Central Standard Time',
    '10' => 'Australian Eastern Standard Time',
    '10.5' => 'Lord Howe Standard Time',
    '11' => 'Solomon Islands Time',
    '11.5' => 'Norfolk Time',
    '12' => 'New Zealand Standard Time',
    '12.75' => 'Chatham Standard Time',
    '13' => 'Phoenix Island Time',
    '13.75' => 'Chatham Daylight Time',
    '14' => 'Line Islands Time',
  );
  
  public static function human_duration($secs, $howmany=1) {
    if (is_numeric($secs)) $secs = intval($secs);
    else $secs = abs(time() - strtotime($secs));
    
    $time_metrics = array(
      'year' => 31536000,
      'month' => 2592000,
      'week' => 604800,
      'day' => 86400,
      'hour' => 3600,
      'minute' => 60,
      'second' => 1);
    
    if ($secs == 0) return '0 seconds';
    
    $parts = array();
    foreach ($time_metrics as $metric => $duration) {
      if ($duration > $secs) continue;
      if ($howmany-- < 1) break;
      $number = intval($secs / $duration);
      $s = "$number $metric";
      if ($number > 1) $s .= 's';
      $parts[] = $s;
      $secs = $secs % $duration;
    }
    return implode(' ', $parts);
  }

  public static function mysql_time($when) { return date(Time::MYSQL_TIME_FORMAT, strtotime($when)); }
  public static function mysql_now() { return self::mysql_time('now'); }
  
  public static function lastChecked($key, $duration) {
    static $A = array();
    
    $time_passed = time() - $A[$key];
    
    if ($time_passed > $duration) {
      $A[$key] = time();
      return true;
    }
    
    return false;
  }
  
  public static function nextOccurance($weekday, $hour=0, $minute=0, $second=0) {
    $weekday = strtolower($weekday);
    $weekday_now = strtolower(date('l'));
    
    if ($weekday == $weekday_now) {
      $justtime = strtotime("$hour:$minute:$second");
      if ($justtime < time()) $justtime += Time::WEEK;
      return $justtime;
    }
    
    return strtotime("next $weekday $hour:$minute:$second");
  }
  
  public static function convert_tz($time_str, $source_tz, $target_tz) {
    $dateTime = new DateTime($time_str, new DateTimeZone($source_tz));
    $dateTime->setTimezone($target_tz);
    return $dateTime->format('Y-m-d H:i:s');
  }
  
  public static function date_component($time_str) { return date('Y-m-d', strtotime($time_str)); }
  public static function time_component($time_str) { return date('H:i:s', strtotime($time_str)); }
  
  function human_duration_hms($sec, $padHours = false, $alwaysShowHours = false) {
    $hms = "";
    $hours = intval(intval($sec) / 3600); 
    
    if ($hours || $alwaysShowHours) {
      $hms .= ($padHours) 
            ? str_pad($hours, 2, "0", STR_PAD_LEFT). ":"
            : $hours. ":";
    }
    
    $minutes = intval(($sec / 60) % 60); 
    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ":";
    $seconds = intval($sec % 60); 
    $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
    
    return $hms;
    
  }
  
}

?>
