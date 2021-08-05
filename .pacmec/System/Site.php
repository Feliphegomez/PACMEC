<?php
/**
 *
 * @package    PACMEC
 * @category   Site
 * @copyright  2020-2021 FelipheGomez & FelipheGomez CO
 * @author     FelipheGomez <feliphegomez@gmail.com>
 * @license    license.txt
 * @version    0.0.1
 */
namespace PACMEC\System;

class Site extends \PACMEC\System\BaseRecords
{
  const TABLE_NAME = 'sites';
  const COLUMNS_AUTO_T  = [
  ];
  public $id               = "";
  public $name             = "";
  public $domain           = "";
  public $hash             = "";
  public $role_id          = null;
  public $role             = null;
  public $ip_server        = "0.0.0.0";
  public $link_site        = "#";
  public $logo             = "#";
  public $logo_alt         = "#";
  public $stock_alert  = [];
  public $orders  = [
    "opened" => 0,
    "closed" => 0,
    "omits" => 0,
  ];
  public $tasks  = [
    "opened" => 0,
    "closed" => 0,
    "omits" => 0,
  ];
  public $totals  = [
    "clients" => 0,
    "comments" => 0,
    "orders" => 0,
    "tasks" => 0,
    "tasks_activity" => 0,
    "payments" => 0,
    "posts" => 0,
    "products" => 0,
    "services" => 0,
  ];
  public $last  = [
    "clients" => [],
    "comments" => [],
    "orders" => [],
    "tasks" => [],
    "tasks_activity" => [],
    "payments" => [],
    "posts" => [],
    "products" => [],
    "services" => [],
  ];
  public $permissions      = [];
  public $plugins          = [];
  public $orders_status    = [];
  // public $tasks            = [];
  // public $tasks_activity   = [];
  // public $payments         = [];

  public function __construct($opts=null)
  {
    Parent::__construct();
    if(is_array($opts)) $opts = (object) $opts;
    if(is_object($opts) && isset($opts->host)) $this->get_by('domain', $opts->host);
    // if(is_object($opts) && isset($opts->host)) $this->get_by_host_role('host', $opts->host);
    #else if(is_object($opts) && isset($opts->ref)) $this->get_by('sku', $opts->ref);
  }

  public function set_all($site)
  {
    Parent::set_all($site);
    if($this->isValid()){
      $this->role_id = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT * FROM `{$GLOBALS['PACMEC']['DB']->getTableName('users_sites')}` WHERE `user` IN (?) AND `host` IN (?)", [\userID(), $this->domain])->role;
      $this->role = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT * FROM `{$GLOBALS['PACMEC']['DB']->getTableName('permissions_group')}` WHERE `id` IN (?)", [$this->role_id]);
      $permissions = $GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT E.*
        FROM `{$GLOBALS['PACMEC']['DB']->getTableName('permissions')}` D
        JOIN `{$GLOBALS['PACMEC']['DB']->getTableName('permissions_items')}` E
        ON E.`id` = D.`permission`
        WHERE D.`group` IN (?)", [$this->role_id]);
      if($permissions !== false && count($permissions) > 0) foreach($permissions as $perm) $this->permissions[] = $perm;

      $options = $GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT `O`.* FROM `{$GLOBALS['PACMEC']['DB']->getTableName('options')}` `O` WHERE `O`.`host` IN ('*', ?)", [$this->domain]);
      if($options !== false){
        foreach ($options as $option) $this->options[$option->option_name] = $option->option_value;
      }
      $this->plugins = explode(',', $this->options['plugins_activated']);
      $this->link_site = $this->getOption('siteurl') . $this->getOption('homeurl');
      $this->name = $this->getOption('sitename');
      $this->logo = $this->getOption('sitelogo');
      $this->logo_alt = $this->getOption('sitelogo_alt');

      $orders_statuses = $GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT `O`.`id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders_status')}` `O` WHERE `O`.`host` IN ('*', ?)", [$this->domain]);
      if($orders_statuses !== false){
        foreach ($orders_statuses as $orders_status) {
          $status = new \PACMEC\System\OrdersStatus((object )["id" => $orders_status->id]);
          $status->total = 0;
          $status->orders = [];
          $count = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT COUNT(`O`.`id`) as 'count', GROUP_CONCAT(`O`.`id`) as 'ids' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` `O` WHERE `O`.`host` IN (?) AND `O`.`status` IN (?)", [$this->domain, $orders_status->id]);
          if($count !== false) {
            $status->total = $count->count;
            foreach (explode(',', $count->ids) as $order_id) if(!empty($order_id)) $status->orders[] = $order_id;
          };
          $this->orders_status[] = $status;
        }
      }

      $limit = 15;
      $stock_limit = 5;

      $this->orders['opened'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` WHERE `host` IN (?) AND `closed` IS NULL", [$this->domain])->total;
      $this->orders['closed'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` WHERE `host` IN (?) AND `closed` IS NOT NULL AND `sales_omit` IN (0)", [$this->domain])->total;
      $this->orders['omits'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` WHERE `host` IN (?) AND `closed` IS NOT NULL AND `sales_omit` IN (1)", [$this->domain])->total;

      $this->tasks['opened'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks')}` WHERE `host` IN (?) AND `closed` IS NULL", [$this->domain])->total;
      $this->tasks['closed'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks')}` WHERE `host` IN (?) AND `closed` IS NOT NULL AND `omit` IN (0)", [$this->domain])->total;
      $this->tasks['omits'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks')}` WHERE `host` IN (?) AND `closed` IS NOT NULL AND `omit` IN (1)", [$this->domain])->total;

      $this->totals['clients'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('clients')}` WHERE `host` IN (?)", [$this->domain])->total;
      $this->totals['comments'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('comments')}` WHERE `host` IN (?)", [$this->domain])->total;
      $this->totals['orders'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` WHERE `host` IN (?)", [$this->domain])->total;
      $this->totals['tasks'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks')}` WHERE `host` IN (?)", [$this->domain])->total;
      $this->totals['tasks_activity'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks_activity')}` WHERE `host` IN (?)", [$this->domain])->total;
      $this->totals['payments'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('payments')}` WHERE `host` IN (?)", [$this->domain])->total;
      $this->totals['posts'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('posts')}` WHERE `host` IN (?)", [$this->domain])->total;
      $this->totals['products'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('products')}` WHERE `host` IN (?)", [$this->domain])->total;
      $this->totals['services'] = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('services')}` WHERE `host` IN (?)", [$this->domain])->total;

      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('clients')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$this->domain]
        ) as $i => $item
      ) $this->last['clients'][] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('comments')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$this->domain]
        ) as $i => $item
      ) $this->last['comments'][] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$this->domain]
        ) as $i => $item
      ) $this->last['orders'][] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$this->domain]
        ) as $i => $item
      ) $this->last['tasks'][] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks_activity')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$this->domain]
        ) as $i => $item
      ) $this->last['tasks_activity'][] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('payments')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$this->domain]
        ) as $i => $item
      ) $this->last['payments'][] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('posts')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$this->domain]
        ) as $i => $item
      ) $this->last['posts'][] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('products')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$this->domain]
        ) as $i => $item
      ) $this->last['products'][] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('services')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$this->domain]
        ) as $i => $item
      ) $this->last['services'][] = $item->id;

      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('products')}` WHERE `host` IN (?) AND `available` <= '{$stock_limit}' GROUP BY `id` ORDER BY `created` DESC"
          , [$this->domain]
        ) as $i => $item
      ) $this->stock_alert[] = $item->id;


      /*
      $orders_pendings = $GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT `O`.`id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` `O` WHERE `O`.`host` IN (?) AND `O`.`closed` IS NULL", [$this->domain]);
      if($orders_pendings !== false){
        foreach ($orders_pendings as $order) {
          $this->orders_pendings[] = $order->id;
        }
      }
      */

      /*
      $orders = $GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT `O`.`id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` `O` WHERE `O`.`host` IN (?)", [$this->domain]);
      if($orders !== false){
        foreach ($orders as $order) {
          $this->orders[] = $order->id;
        }
      }*/
    }
  }

  public function getOption($option_name){
    return !isset($this->options[$option_name]) ? null : $this->options[$option_name];
  }

  public static function getByMe(){
    try {
      $sites = [];
      $users_sites = $GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT * FROM `{$GLOBALS['PACMEC']['DB']->getTableName('users_sites')}` WHERE `user` IN (?)", [\userID()]);
      if($users_sites !== false){
        foreach ($users_sites as $ai => $site) $sites[] = new Self($site);
      }
      /*



      $tasks = $GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT `T`.`id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders_tasks')}` `T` WHERE `T`.`order_id` IN (".implode(',', $siteObj->orders).")", []);
      if($tasks !== false){
        foreach ($tasks as $task) {
          $siteObj->tasks[] = $task->id;
        }
      }

      $tasks_activity = $GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT `OTA`.`id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders_tasks_activity')}` `OTA` WHERE `OTA`.`task` IN (".implode(',', $siteObj->tasks).")", []);
      if($tasks_activity !== false){
        foreach ($tasks_activity as $task_act) {
          $siteObj->tasks_activity[] = $task_act->id;
        }
      }

      $payments = $GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT `P`.`id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('payments')}` `P` WHERE `P`.`host` IN (?)", [$siteObj->domain]);
      if($payments !== false){
        foreach ($payments as $payment) {
          $siteObj->payments[] = $payment->id;
        }
      }
      */
      return $sites;
    } catch (\Exception $e) {
      return [];
    }
  }

}
