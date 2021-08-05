<?php
/**
 *
 * @package    PACMEC
 * @category   OrdersStatus
 * @copyright  2020-2021 FelipheGomez
 * @author     FelipheGomez <feliphegomez@pm.me>
 * @license    license.txt
 * @version    1.0.1
 */

namespace PACMEC\System;

class OrdersStatus extends \PACMEC\System\BaseRecords
{
  const TABLE_NAME            = 'orders_status';
  const COLUMNS_AUTO_T        = [];

  public function __construct($opts=null)
  {
    Parent::__construct();
    if(is_object($opts) && isset($opts->id)) $this->get_by_id($opts->id);
  }

  public function set_all($obj)
  {
    global $PACMEC;
    Parent::set_all($obj);
    if($this->isValid()){

    }
  }

  public function __toString() : string
  {
    return isset($this->name) ? $this->name : 'undefined';
  }
}
