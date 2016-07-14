<?php
require_once 'jsonRPCClient.php';

class BitcoinClient {
  private $client = null;
  
  function __construct() {
    $this->client = new jsonRPCClient('http://bitcoin:bitcoin@127.0.0.1:8332/');
  }
  
  public static function mtgoxTicker() {
    $data = Browser::fetch('https://mtgox.com/api/0/data/ticker.php', null, array('userAgent' => 'random'));
    $data = json_decode($data, true);
    return $data['ticker'];
  }
  
  function getSource($txid) {
    try { $info = $this->gettransaction($txid); } catch (Exception $e) { }
    if (!$info) return null;
    $vin_hash = $info['vin'][0]['prevout']['hash'];
    $vin_n = $info['vin'][0]['prevout']['n'];
    if (!$vin_hash) return null;
    
    try { $srctrans = $this->gettransaction($vin_hash); } catch (Exception $e) { }
    if (!$srctrans) return null;
    
    $scriptkey = $srctrans['vout'][$vin_n]['scriptPubKey'];
    if (! preg_match('/ ([a-z0-9]{40}) /', $scriptkey, $match)) return null;
    else return BitCoin::hash160ToAddress($match[1]);
    
  }
  
  function __call($method, $params) {
    foreach ($params as &$param) {
      if ($param === 'true') $param = true;
      else if ($param === 'false') $param = false;
      else if (is_numeric($param)) $param = doubleval($param);
    }
    
    return call_user_func_array(array($this->client, $method), $params);
  }
  
  
}
?>
