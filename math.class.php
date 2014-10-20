<?
class Math {
  
  function median($list, $point = 50, $noSplit = false) {
    if (!is_array($list) || !count($list)) return 0;
    $list = array_values($list);
    sort($list);
    
    $topIndex = count($list) - 1;
    $targetIndex = $topIndex * ($point / 100);
    if ($noSplit) $targetIndex = intval($targetIndex);
    
    return self::mean(array($list[ceil($targetIndex)], $list[floor($targetIndex)]));
  }
  
  function mean($list) {
    if (!is_array($list) || !count($list)) return 0;
    return array_sum($list) / count($list);
  }
  
  // http://en.wikipedia.org/wiki/Algorithms_for_calculating_variance#III._On-line_algorithm
  function running_stats($list, $mean=0, $n=0, $m2=0) {
    foreach ($list as $x) {
      $n++;
      $delta = $x - $mean;
      $mean += $delta/$n;
      $m2 += $delta*($x - $mean);
    }
    
    return array(
      'variance' => $m2/$n,
      'stddev' => sqrt($m2/$n),
      'mean' => $mean,
      'count' => $n,
      'm2' => $m2
    );
  }
  
  function variance($list) {
    $st = self::running_stats($list);
    return $st['variance'];
  }
  
  function stddev($list) {
    $st = self::running_stats($list);
    return $st['stddev'];
  }
  
  function inRange($number, $min, $max) {
    if ($number >= $min && $number <= $max) return true;
    else return false;
  }
  
  public static function rangesCross($start1, $end1, $start2, $end2) {
    if (Math::inRange($end1, $start2, $end2)) return true;
    if (Math::inRange($end2, $start1, $end1)) return true;
    return false;
  }
  
}

?>
