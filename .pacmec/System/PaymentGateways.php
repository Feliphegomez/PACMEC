<?php
/**
 *
 * @package    PACMEC
 * @category   System
 * @copyright  2020-2021 FelipheGomez
 * @author     FelipheGomez <feliphegomez@pm.me>
 * @license    license.txt
 * @version    1.0.1
 */

namespace PACMEC\System;

Class PaymentGateways
{
  public $providers    = [];
  public $gateways     = [];


  public function __construct()
  {
  }

  public function add_provider($provider)
  {
    if(
      isset($provider->name)
    ){
      $this->providers[] = $provider->name;
      $this->gateways[$provider->name] = $provider;
    }
  }
}
