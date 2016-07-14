<?php
class Google {
  public static function SearchAndCount($searchterm) {
    $result = Browser::fetch('http://www.google.com/search?' . 
      http_build_query(array('q' => $searchterm)), 
      null, array('userAgent' => 'Links (2.2; Linux 2.6.32-gentoo-r6 x86_64; 129x42)'));
    
    if (substr_count($result, 'did not match any documents')) { 
      return 0;
    } else {
      if (! preg_match('/about.*?([\d,]+).*?result/i', $result, $match)) return null;
      return str_replace(',', '', $match[1]);
    }
    
  }

}



?>
