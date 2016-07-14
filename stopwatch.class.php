<?php
class Stopwatch {
  private $runtime = 0;
  private $starttime = 0;
  
  
  function start() {
    if ($this->status()) throw new Exception("Unable to start stopwatch -- already running");
    
    $this->starttime = microtime(true);
  }
  
  function stop() {
    if (! $this->status()) throw new Exception("Unable to stop stopwatch -- already stopped");
    
    $this->runtime += $this->laptime_s();
    $this->starttime = 0;
  }
  
  function reset() {
    $this->runtime = 0;
    if ($this->status()) $this->starttime = microtime(true);
    else $this->starttime = 0;
  }
  
  function status() { return $this->starttime ? true : false; }
  
  // time since we last started it
  function laptime_s() {
    if ($this->starttime) return microtime(true) - $this->starttime;
    else return 0;
  }
  
  function runtime_s() { return $this->runtime + $this->laptime_s(); }
  
  // 1 second = 1,000 milliseconds
  function runtime_ms() { return sprintf('%f', $this->runtime_us() * 1000); }
  

}
?>
