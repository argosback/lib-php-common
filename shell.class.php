<?
class Shell {
  public static function kill($pids, $force = false) {
    $pids = (array) $pids;
    $options = '';
    
    if ($force) $options = '-9';
    
    foreach ($pids as $pid) {
      if (is_array($pid)) $pid = $pid['pid'];
      if (!is_numeric($pid)) continue;
      
      //echo "kill $options $pid\n";
      shell_exec("kill $options $pid");
    }
  }
  
  public static function hardKill($pslist) {
    $pidlist = array();
    foreach ($pslist as $pid) {
      if (is_array($pid)) $pid = $pid['pid'];
      if (!is_numeric($pid)) continue;
      
      $pidlist[] = $pid;
    }
    
    self::kill($pidlist);
    $stopwatch = new Stopwatch();
    $stopwatch->start();
    
    do {
      $still_running = self::processSearch(array('pid' => $pidlist));
      $pidlist = Arr::verticalSlice($still_running, 'pid');
      
      if ($stopwatch->laptime_s() > 20) { 
        self::kill($pidlist, true);
        $stopwatch->stop();
        $stopwatch->start();
      }
      
      // failed to kill the process
      if ($stopwatch->runtime_s() > 180) return false;
      
    } while (count($pidlist) > 0);
    
    return true;
  }
    
  
  public static function checkPid($pid) {
    $result = shell_exec('ps ux');
    $lines = preg_split("/[\n\r]+/", $result);
    foreach ($lines as $line) {
      $word = preg_split("/[\t ]+/", $line, 11);
      $word[10] = rtrim($word[10]);
      if ($word[1] == $pid) return $word[10];
    }
    return null;
  }
  
  public static function checkName($name) {
    $result = shell_exec('ps ux');
    $lines = preg_split("/[\n\r]+/", $result);
    foreach ($lines as $line) {
      $word = preg_split("/[\t ]+/", $line, 11);
      $word[10] = rtrim($word[10]);
      if ($word[10] == $name) return $word[1];
    }
    return null;
  }
  
  public static function processList() {
    $result = shell_exec('ps ux');
    $lines = preg_split("/[\n\r]+/", trim($result));
    $first = true;
    foreach ($lines as $line) {
      $items = preg_split("/[\t ]+/", $line, 11);
      if ($first) {
        foreach ($items as $item) $keys[] = strtolower($item);
        $first = false;
        continue;
      }
      
      $row = array();
      foreach ($items as $i => $item) {
        if (! $keys[$i]) continue;
        $row[$keys[$i]] = $item;
      }
      $res[] = $row;
      
      
    }
    
    return $res;
  }
  
  public static function processSearch($info, $exact=false) {
    if (! is_array($info)) $info = array('command' => $info);
    $res = array();
    
    foreach (self::processList() as $process) {
      $found = false;
      
      foreach ($info as $key => $values) {
        $values = (array) $values;
        
        foreach ($values as $value) {
          if ($exact && $process[$key] == $value) $found = true;
          if (!$exact && substr_count($process[$key], $value)) $found = true;
        }
          
      }
      
      if ($found) $res[] = $process;
    }
    
    return $res;
  }
  
  public static function whoami($key=null) {
    $userinfo = posix_getpwuid(posix_geteuid());
    if (! $key) return $userinfo;
    else return $userinfo[$key];
  }
  
  public static function su($username) {
    $userinfo = posix_getpwnam($username);    
    if (! $userinfo) throw new Exception("Unable to su to $username -- no such user!");
    
    if ($userinfo['uid'] == posix_getuid() && $userinfo['uid'] == posix_geteuid()) return true;
    if (posix_geteuid() != 0) throw new Exception("Unable to su to $username -- not root!");
    
    posix_setuid($userinfo['uid']);
    posix_seteuid($userinfo['uid']);
  
    if (!($userinfo['uid'] == posix_getuid() && $userinfo['uid'] == posix_geteuid())) {
      throw new Exception("Unable to su to $username -- unknown su failure!");
    }
  }
  
  
  public static function exec($command, $params=array()) {
    $stdall = $stdout = $stderr = '';
    
    /*if (! array_key_exists('echo', $params) && php_sapi_name() === 'cli') {
      $params['echo'] = 'all';
    }*/
    
    if ($params['echo'] == 'all' || $params['echo'] == 'command') echo "CMD: $command\n";
    
    $stdout_cb = (array) $params['stdout_cb'];
    $stderr_cb = (array) $params['stderr_cb'];
    
    if ($params['echo'] == 'all' || $params['echo'] == 'output') { 
      $stdout_cb[] = function($output) { echo $output; };
      $stderr_cb[] = function($output) { echo $output; };
    }
    
    if ($logfile = $params['logfile']) {
      @file_put_contents($logfile, "CMD: $command\n", FILE_APPEND);
      $stdout_cb[] = function($output) use ($logfile) { @file_put_contents($logfile, $output, FILE_APPEND); };
      $stderr_cb[] = function($output) use ($logfile) { @file_put_contents($logfile, $output, FILE_APPEND); };
    }
    
    $stdout_cb[] = function($output) use (&$stdall, &$stdout) { $stdall .= $output; $stdout .= $output; };
    $stderr_cb[] = function($output) use (&$stdall, &$stderr) { $stdall .= $output; $stderr .= $output; };
    
    $process = proc_open($command, array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
      ), $pipes);
    
    if (! is_resource($process)) throw new Exception("Unable to execute command: $command");
    
    do  {
      $read_streams = array($pipes[1], $pipes[2]);
      $write_streams = $except_streams = null;
      stream_select($read_streams, $write_streams, $except_streams, 1);
      
      $buf_out = $buf_err = null;
      if (is_array($read_streams) && in_array($pipes[1], $read_streams, true)) $buf_out = fread($pipes[1], 4096);
      if (is_array($read_streams) && in_array($pipes[2], $read_streams, true)) $buf_err = fread($pipes[2], 4096);
      
      if (strlen($buf_out)) foreach ($stdout_cb as $cb) call_user_func($cb, $buf_out);
      if (strlen($buf_err)) foreach ($stderr_cb as $cb) call_user_func($cb, $buf_err);
      
      $procstat = proc_get_status($process);
      if (!$procstat['running'] && !isset($exitcode)) $exitcode = $procstat['exitcode'];
      
    } while ($procstat['running'] || strlen($buf_out) || strlen($buf_err));
    
    fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
    
    if ($params['exception_exitcode'] && $exitcode != 0)
      throw new Exception("Command [$command] returned error code $exitcode");
    
    if ($params['return_exitcode']) {
      return ($exitcode == 0) ? true : false;
    } else if ($params['return_all']) {
      return array(
        'command' => $command,
        'stdout' => $stdout,
        'stderr' => $stderr,
        'output' => $stdall,
        'exitcode' => $exitcode,
      );
    } else {
      return $stdall;
      
    }
  }
  
  public static function autobuild($params) {
    $cmd_list = array();
    
    $precmd = (array) $params['precmd'];
    $postcmd = (array) $params['postcmd'];
    $cmd_list = array_merge($precmd, $cmd_list);
    
    if ($path = $params['wget']) {
      $name = pathinfo($path, PATHINFO_BASENAME);
      $cmd_list[] = "rm -fr $name"; 
      $cmd_list[] = "wget $path"; 
      
      if (substr_count($name, '.tar.gz')) {
        $dir = str_replace('.tar.gz', '', $name);
        
        $cmd_list[] = "rm -fr $dir";
        $cmd_list[] = "tar -zxvf $name";
        $cmd_list[] = "cd $dir";
      } else if (substr_count($name, '.tar.bz2')) {
        $dir = str_replace('.tar.gz', '', $name);
        
        $cmd_list[] = "rm -fr $dir";
        $cmd_list[] = "tar -jxvf $name";
        $cmd_list[] = "cd $dir";
      } else {
        die("Don't know what directory to cd to\n");
      }
      
    } else if ($path = $params['git']) {
      $name = pathinfo($path, PATHINFO_BASENAME);
      $cmd_list[] = "rm -fr $name"; 
      $cmd_list[] = "git clone --depth 1 $path $name";
      $cmd_list[] = "cd $name";
      
      if ($params['git_branch']) $cmd_list[] = "git checkout {$params['git_branch']}"; 
    } else {
      die("don't know what to do!\n");
    }
    
    if (is_string($params['configure'])) $cmd_list[] = "./configure {$params['configure']}";
    else if (!$params['no_configure']) $cmd_list[] = "./configure";
    
    $cmd_list[] = "make -j 8";
    $cmd_list[] = "make install";
    
    $cmd_list = array_merge($cmd_list, $postcmd);
    
    self::exec(implode(' && ', $cmd_list), array('exception_exitcode' => true));
    
  }
  
  public static function createPidFile($pidfile) {
    $pid = @intval(file_get_contents($pidfile));
    if ($pid) {
      list($pinfo) = self::processSearch(array('pid' => $pid), true);
      if ($pinfo) throw new Exception("Process is already running ($pidfile)");
    }
    
    if (! file_put_contents($pidfile, posix_getpid())) throw new Exception("Unable to create pidfile: $pidfile");
    
    register_shutdown_function(function() use ($pidfile) { @unlink($pidfile); });
  }
  
  public static function redirectOutput($stdout_path, $stderr_path) {
    global $STDOUT, $STDERR;
    
    fclose(STDOUT);
    fclose(STDERR);
    $STDOUT = fopen($stdout_path, 'wb');
    $STDERR = fopen($stderr_path, 'wb');
  }
  
}

?>