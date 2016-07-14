<?php
class RandomOrg {
  public static function randInts($min, $max, $count) {
    $r = Browser::fetch('http://www.random.org/integers/', array(
      'num' => $count,
      'min' => $min,
      'max' => $max,
      'col' => 1,
      'base' => 10,
      'format' => 'plain',
      'rnd' => 'new'
    ), array(
      'method' => 'get'
    ));
    
    $r = Common::line_split(trim($r));
    if (count($r) != $count) throw new Exception("Unable to get random numbers");
    return $r;
  }
  
  public static function randInt($min, $max) {
    $numbers = Cache::get("randomorg_intcache_$min_$max");
    
    while (! $numbers) {
      try { $numbers = self::randInts($min, $max, 100); } 
      catch (Exception $e) { }
    }
    
    $number = array_shift($numbers);
    Cache::set("randomorg_intcache_$min_$max", $numbers, false, Time::DAY);
    return $number;
  }
  
  


}
?>
