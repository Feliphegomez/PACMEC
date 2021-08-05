<?php
/**
 *
 * @author     FelipheGomez <feliphegomez@gmail.com>
 * @package    PACMEC
 * @category   Controllers
 * @license    license.txt
 * @version    Release: @package_version@
 * @version    1.0.1
 */
Class PacmecController extends \PACMEC\System\ControladorBase
{
  public $error    = true;
  public $details  = null;
  public $response = null;

	public function __construct()
  {
		parent::__construct();
		header('Content-Type: application/json');
	}

  private function goReturn()
  {
		echo json_encode($this);
		return json_encode($this);
  }

  public function index()
  {
		return $this->goReturn();
  }

  public function counters($data)
  {
    if(is_array($data)) $data = (object) $data;
    if(
      isset($data->domain)
    ){
      $limit = isset($data->limit) && !empty((int) $data->limit) ? (int) $data->limit : 25;
      $stock_limit = isset($data->stock_limit) && !empty((int) $data->stock_limit) ? (int) $data->stock_limit : 5;
      $response = (object) [
        "stock_alert" => [],
        "orders" => (object) [
          "opened" => 0,
          "closed" => 0,
          "omits" => 0,
        ],
        "tasks" => (object) [
          "opened" => 0,
          "closed" => 0,
          "omits" => 0,
        ],
        "totals" => (object) [
          "clients" => 0,
          "comments" => 0,
          "orders" => 0,
          "tasks" => 0,
          "tasks_activity" => 0,
          "payments" => 0,
          "posts" => 0,
          "products" => 0,
          "services" => 0,
        ],
        "last" => (object) [
          "clients" => [],
          "comments" => [],
          "orders" => [],
          "tasks" => [],
          "tasks_activity" => [],
          "payments" => [],
          "posts" => [],
          "products" => [],
          "services" => [],
        ],
      ];

      $response->orders->opened = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` WHERE `host` IN (?) AND `closed` IS NULL", [$data->domain])->total;
      $response->orders->closed = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` WHERE `host` IN (?) AND `closed` IS NOT NULL AND `sales_omit` IN (0)", [$data->domain])->total;
      $response->orders->omits = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` WHERE `host` IN (?) AND `closed` IS NOT NULL AND `sales_omit` IN (1)", [$data->domain])->total;

      $response->tasks->opened = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks')}` WHERE `host` IN (?) AND `closed` IS NULL", [$data->domain])->total;
      $response->tasks->closed = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks')}` WHERE `host` IN (?) AND `closed` IS NOT NULL AND `omit` IN (0)", [$data->domain])->total;
      $response->tasks->omits = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks')}` WHERE `host` IN (?) AND `closed` IS NOT NULL AND `omit` IN (1)", [$data->domain])->total;

      $response->totals->clients = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('clients')}` WHERE `host` IN (?)", [$data->domain])->total;
      $response->totals->comments = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('comments')}` WHERE `host` IN (?)", [$data->domain])->total;
      $response->totals->orders = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` WHERE `host` IN (?)", [$data->domain])->total;
      $response->totals->tasks = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks')}` WHERE `host` IN (?)", [$data->domain])->total;
      $response->totals->tasks_activity = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks_activity')}` WHERE `host` IN (?)", [$data->domain])->total;
      $response->totals->payments = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('payments')}` WHERE `host` IN (?)", [$data->domain])->total;
      $response->totals->posts = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('posts')}` WHERE `host` IN (?)", [$data->domain])->total;
      $response->totals->products = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('products')}` WHERE `host` IN (?)", [$data->domain])->total;
      $response->totals->services = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT count(*) as 'total' FROM `{$GLOBALS['PACMEC']['DB']->getTableName('services')}` WHERE `host` IN (?)", [$data->domain])->total;

      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('clients')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$data->domain]
        ) as $i => $item
      ) $response->last->clients[] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('comments')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$data->domain]
        ) as $i => $item
      ) $response->last->comments[] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$data->domain]
        ) as $i => $item
      ) $response->last->orders[] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$data->domain]
        ) as $i => $item
      ) $response->last->tasks[] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('tasks_activity')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$data->domain]
        ) as $i => $item
      ) $response->last->tasks_activity[] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('payments')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$data->domain]
        ) as $i => $item
      ) $response->last->payments[] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('posts')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$data->domain]
        ) as $i => $item
      ) $response->last->posts[] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('products')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$data->domain]
        ) as $i => $item
      ) $response->last->products[] = $item->id;
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('services')}` WHERE `host` IN (?) GROUP BY `id` ORDER BY `created` DESC LIMIT {$limit}"
          , [$data->domain]
        ) as $i => $item
      ) $response->last->services[] = $item->id;

      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject(
          "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('products')}` WHERE `host` IN (?) AND `available` <= '{$stock_limit}' GROUP BY `id` ORDER BY `created` DESC"
          , [$data->domain]
        ) as $i => $item
      ) $response->stock_alert[] = $item->id;

      $this->response = $response;
    }
  	return $this->goReturn();
  }

  public function createShop($data)
  {
    if(is_array($data)) $data = (object) $data;
    if(\isGuest()) return $this->goReturn();
    if(
      isset($data->name)
      && isset($data->subdomain)
      && isset($data->description)
      && isset($data->keywords)
      && isset($data->plugins)
      && isset($data->theme)
      && isset($data->business_address)
      && isset($data->business_email)
      && isset($data->business_hours)
      && isset($data->business_phone_number)
    ) {
      $domain = $data->subdomain . ".pacmec.com.co";
      $users_sites = $GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT * FROM `{$GLOBALS['PACMEC']['DB']->getTableName('sites')}` WHERE `domain` IN (?)", [$domain]);
      if($users_sites == false){
        $sql = "INSERT INTO `{$GLOBALS['PACMEC']['DB']->getTableName('sites')}` (`domain`, `ip_server`, `created_by`, `modifiered_by`) VALUES (?,?,?,?)";
        $this->response = "Creando sitio {$data->name}";
        $this->details = $domain;
        $site_id = $GLOBALS['PACMEC']['DB']->FetchObject($sql, [$domain, $_SERVER['SERVER_ADDR'], \userID(), \userID()]);
        $add_in_user = $GLOBALS['PACMEC']['DB']->FetchObject(
          "INSERT INTO `{$GLOBALS['PACMEC']['DB']->getTableName('users_sites')}` (`user`, `host`, `role`) VALUES (?,?,?)"
          , [\userID(), $domain, 3]
        );
        if($site_id !== false && $add_in_user !== false){
          foreach ([
            'business_address' => $data->business_address,
            'business_email' => $data->business_email,
            'business_hours' => $data->business_hours,
            'business_phone_number' => $data->business_phone_number,
            'homeurl' => '/',
            'plugins_activated' => implode(',', $data->plugins),
            'sitedescr' => $data->description,
            'sitekeywords' => implode(',', $data->keywords),
            'sitename' => $data->name,
            'siteurl' => 'https://' . $domain,
            'theme_default' => $data->theme,
          ] as $key => $value) {
            $GLOBALS['PACMEC']['DB']->FetchObject(
              "INSERT INTO `{$GLOBALS['PACMEC']['DB']->getTableName('options')}` (`host`, `option_name`, `option_value`) VALUES (?,?,?)"
              , [$domain, $key, $value]
            );
          }

          $this->error = false;
          $this->details = "Sitio creado con éxito";
          $this->response = "El sitio {$data->name} fue creado con éxito";
        }
      } else {
        $this->details = "Ups";
        $this->response = "Ya existe un sitio con este subdominio.";
      }
    } else {
      $this->details = "Completa los datos";
      $this->response = "Faltan datos para crear tu tienda.";
    }
		echo json_encode($this);
		return json_encode($this);
  }

  public function notifications_change_status_fast($data)
  {
    // $this->$data
    if(isset($data['notification_id']) && is_numeric($data['notification_id'])){
      $notification = new \PACMEC\System\Notifications((object) ['id'=>$data['notification_id']]);
      if($notification->is_read==1){
        $this->error = !($notification->unread());
      } else {
        $this->error = !($notification->read());
      }

      switch ($notification->is_read) {
        case 1:
          $this->data = "fa fa-check-circle";
          break;
        case 0:
          $this->data = "fa fa-dot-circle-o";
          break;
        default:
          $this->data = "fa fa-circle-o";
          break;
      }
    }
    return $this->goReturn();
  }
}
