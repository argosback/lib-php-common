<?php

class BigNumber {
  private $number = '0';
  private $precision;

  public static $default_precision = 16;

  static function min() {
    $list = func_get_args();
    usort($list, function($a, $b) { return $a->gt($b) ? 1 : -1; });
    return $list[0];
  }

  static function max() {
    $list = func_get_args();
    usort($list, function($a, $b) { return $a->gt($b) ? -1 : 1; });
    return $list[0];
  }

  function __construct($number, $precision = null) {
    if ($number instanceof BigNumber) {
      if (!$precision) $precision = $number->precision;
      $number = $number->number;
    } else {
      $number = strval($number);
    }

    $this->precision = $precision;
    if (! $this->precision) $this->precision = self::$default_precision;

    // prevent PHP's scientific notation, which breaks bcmath
    if (substr_count($number, 'E')) {
      list($base, $exp) = explode('E', $number, 2);
      $expcalc = bcpow('10', $exp, $this->precision);
      $number = bcmul($base, $expcalc, $this->precision);
    }
    
    $this->number = bcadd($number, '0', $this->precision);
  }

  function precision() { return $this->precision; }
  function doubleval() { return doubleval($this->strval()); }
  function strval() { return $this->number; }
  function shortstrval() {
    $number = $this->strval();
    if (!substr_count($number, '.')) return $number;
    else return rtrim(rtrim($number, '0'), '.');
  }
  function __toString() { return $this->shortstrval(); }

  function abs() {
    $new = clone $this;
    return $new->lt(0) ? $new->mul(-1) : $new;
  }

  function rounddown($precision) { return new BigNumber(bcadd($this->strval(), '0', $precision), $this->precision); }
  function roundup($precision) {
    $smallest_unit = pow(10, -1 * $precision);

    $floor = $this->rounddown($precision);
    if ($floor->abs()->lt($this->abs())) {
      if ($floor->ge(0)) $floor = $floor->add($smallest_unit);
      else $floor = $floor->sub($smallest_unit);
    }

    return $floor;
  }

  private function gt_internal(BigNumber $Other) { return bccomp($this->strval(), $Other->strval(), $this->precision) == 1; }
  private function lt_internal(BigNumber $Other) { return bccomp($this->strval(), $Other->strval(), $this->precision) == -1; }
  private function eq_internal(BigNumber $Other) { return bccomp($this->strval(), $Other->strval(), $this->precision) == 0; }
  private function ne_internal(BigNumber $Other) { return bccomp($this->strval(), $Other->strval(), $this->precision) != 0; }
  private function ge_internal(BigNumber $Other) { return bccomp($this->strval(), $Other->strval(), $this->precision) != -1; }
  private function le_internal(BigNumber $Other) { return bccomp($this->strval(), $Other->strval(), $this->precision) != 1; }

  function __call($name, $arguments) {
    $internal_method = "{$name}_internal";
    $bc_func = "bc$name";

    if (method_exists($this, $internal_method)) $func = array($this, $internal_method);
    else if (function_exists($bc_func)) $func = $bc_func;
    else throw new Exception("Invalid function: BigNumber::$name");

    $other_number = $arguments[0];
    if ($other_number instanceof BigNumber) {
      $new_precision = max($this->precision(), $other_number->precision());
      $other_number = $other_number->number;
    } else {
      $new_precision = $this->precision();
    }

    if ($func == $bc_func) {
      $result = call_user_func($func, $this->strval(), $other_number, $new_precision);
      $result = new BigNumber($result, $new_precision);
    } else {
      $result = call_user_func($func, new BigNumber($other_number, $new_precision));
    }

    return $result;
  }

  
}
