<?
class JobQueue {
  public static function SimpleQueue($jobs, $parallel = 4) {
    $forks = array();
    
    while (count($jobs) || count($forks)) {
      
      while (count($forks) < $parallel && $job = array_shift($jobs)) {
        if (($forks[] = pcntl_fork()) === 0) { // we are the new child
          DB::$internal_mysql = null;
          call_user_func($job);
          exit(0);
        }
      }
      
      
      do {
        if ($pid = pcntl_wait($status)) { // job has finished
          $id = array_search($pid, $forks);
          unset($forks[$id]);
        }
      } while (count($forks) >= $parallel);
      
    }
    
  }
  
  public static function ForkOff($function, $pre_cleanup=null) {
    if (($pid = pcntl_fork()) === 0) { // we are the new child
      if (is_callable($pre_cleanup)) call_user_func($pre_cleanup);
      
      call_user_func($function);
      exit(0);
    }
    
    return $pid;
  }
  
  public static function catchTerm($fn) {
    declare(ticks = 1);
    pcntl_signal(SIGTERM, $fn);
    pcntl_signal(SIGINT, $fn);
  }

}



?>
