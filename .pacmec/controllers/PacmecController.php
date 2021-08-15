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

  public function ProductsAddPicture($data){
    if(is_array($data)) $data = (object) $data;
    if(
      isset($_FILES['photo'])
      && isset($data->product_id)
      && isset($data->host)
      && isUser()
    ){
      if($_FILES['photo']['error'] === 0){
        $product = new \PACMEC\System\Product((object) ["id"=>$data->product_id]);
        if($product->isValid()){
          $folder_path = dirname(PACMEC_PATH) . "/public/galleries/{$data->host}/shop/{$product->id}/";
          if (!@is_dir($folder_path) || !@file_exists($folder_path)) @mkdir($folder_path, 0777, true);
          $hash = randString();

          $path_short = "/public/galleries/{$data->host}/shop/{$product->id}/" . $hash . '-' . basename($_FILES['photo']['name']);
          $path_full  = $folder_path . $hash . '-' . basename($_FILES['photo']['name']);
          if (
            move_uploaded_file($_FILES['photo']['tmp_name'], $path_full)
          ) {
            $ordering = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT
              count(*) as 'total'
              FROM `{$GLOBALS['PACMEC']['DB']->getTableName('products_pictures')}`
              WHERE `product` IN (?) ", [$data->product_id])->total;

            $photo = new \PACMEC\System\GalleriesShop();
            $photo->name    = $_FILES['photo']['name'];
            $photo->type    = $_FILES['photo']['type'];
            $photo->size    = $_FILES['photo']['size'];
            $photo->product = $data->product_id;
            $photo->path_full = $path_full;
            $photo->path_short = $path_short;
            $photo->created_by = \userID();
            $photo->ordering = $ordering;
            $result = $photo->create([
              "name",
              "type",
              "size",
              "product",
              "path_full",
              "path_short",
              "created_by",
              "ordering",
            ]);
            if($result == true){
              $this->error = false;
              $this->details = "agregado con exito.";
              $this->response = $photo;
            }
          } else {
            #echo "¡Posible ataque de subida de ficheros!\n";
            $this->details = "Ocurrio un error agregando la imagen.";
          }
        } else {
          $this->details = ("El producto no existe");
        }
      }
    }
  	return $this->goReturn();
  }

  public function ServicesAddPicture($data){
    if(is_array($data)) $data = (object) $data;
    if(
      isset($_FILES['photo'])
      && isset($data->service_id)
      && isset($data->host)
      && isUser()
    ){
      if($_FILES['photo']['error'] === 0){
        $service = new \PACMEC\System\Service((object) ["id"=>$data->service_id]);
        if($service->isValid()){
          $folder_path = dirname(PACMEC_PATH) . "/public/galleries/{$data->host}/briefcase/{$service->id}/";
          if (!@is_dir($folder_path) || !@file_exists($folder_path)) @mkdir($folder_path, 0777, true);
          $hash = randString();

          $path_short = "/public/galleries/{$data->host}/briefcase/{$service->id}/" . $hash . '-' . basename($_FILES['photo']['name']);
          $path_full  = $folder_path . $hash . '-' . basename($_FILES['photo']['name']);
          if (
            move_uploaded_file($_FILES['photo']['tmp_name'], $path_full)
          ) {
            $ordering = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT
              count(*) as 'total'
              FROM `{$GLOBALS['PACMEC']['DB']->getTableName('services_pictures')}`
              WHERE `service` IN (?) ", [$data->service_id])->total;

            $photo = new \PACMEC\System\GalleriesBriefcase();
            $photo->name    = $_FILES['photo']['name'];
            $photo->type    = $_FILES['photo']['type'];
            $photo->size    = $_FILES['photo']['size'];
            $photo->service = $data->service_id;
            $photo->path_full = $path_full;
            $photo->path_short = $path_short;
            $photo->created_by = \userID();
            $photo->ordering = $ordering;
            $result = $photo->create([
              "name",
              "type",
              "size",
              "service",
              "path_full",
              "path_short",
              "created_by",
              "ordering",
            ]);
            if($result == true){
              $this->error = false;
              $this->details = "agregado con exito.";
              $this->response = $photo;
            }
          } else {
            #echo "¡Posible ataque de subida de ficheros!\n";
            $this->details = "Ocurrio un error agregando la imagen.";
          }
        } else {
          $this->details = ("El servicio no existe");
        }
      }
    }
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

  public function infoProducts($data)
  {
    if(is_array($data)) $data = (object) $data;
    if(!isset($data->domain)) $data->domain = $GLOBALS['PACMEC']['host'];
    $this->details = "Revisando dominio: {$data->domain}";
    $this->response = (object) [
      "establishments_total" => 0,
      "products" => 0,
      "products_alert" => 0,
      "products_in_stock" => [],
      "products_in_shoppings_carts" => 0,
      "stock_in_shoppings_carts" => 0,
      # "products_sales" => 0,

      "stock" => 0,
      "stock_in" => 0,
      "stock_out" => 0,
      "stock_sales" => 0,
      "stock_return_sale" => 0,

      "total_in" => 0,
      "total_out" => 0,
      "total_sales" => 0,
      "total_return_sale" => 0,

      "benefit" => 0,

      # "products_ids" => [],
      "products_alert_ids" => [],
      "products_in_shoppings_carts_ids" => [],

      "establishments" => [],
    ];
    $sql = "SELECT
      COUNT(`P`.`id`) AS `products`
      , SUM(`P`.`available`) AS `stock`
      , GROUP_CONCAT(CASE WHEN `P`.`available`>0 THEN `P`.`id` END) AS `products_in_stock`
      , COUNT(CASE WHEN (`P`.`available`<=5) THEN `P`.`id` END) AS `products_alert`
      , COUNT(`SC`.`id`) AS `products_in_shoppings_carts`
      , SUM(`SC`.`quantity`) AS `stock_in_shoppings_carts`
      #, GROUP_CONCAT(`P`.`id`) AS `products_ids`
      , GROUP_CONCAT(CASE WHEN (`P`.`available`<=5) THEN `P`.`id` END) AS `products_alert_ids`
      , GROUP_CONCAT(`SC`.`ref_id`) AS `products_in_shoppings_carts_ids`
    FROM `{$GLOBALS['PACMEC']['DB']->getTableName('products')}` `P`
    LEFT JOIN `px_shoppings_carts` `SC` ON `SC`.`ref_id` = `P`.`id` AND `SC`.`type` = 'product'
    WHERE `P`.`host` IN (?)
    ";
    $products = $GLOBALS['PACMEC']['DB']->FetchObject($sql, [$data->domain]);
    if($products !== false) {
      $this->response->products = $products->products;
      // $this->response->stock = $products->stock;
      $this->response->products_in_stock = array_filter(explode(',', $products->products_in_stock));
      $this->response->products_alert = $products->products_alert;
      $this->response->products_in_shoppings_carts = $products->products_in_shoppings_carts;
      $this->response->stock_in_shoppings_carts = $products->stock_in_shoppings_carts;
      # $this->response->products_ids = explode(',', $products->products_ids);
      $this->response->products_alert_ids = array_filter(explode(',', $products->products_alert_ids));
      $this->response->products_in_shoppings_carts_ids = array_filter(explode(',', $products->products_in_shoppings_carts_ids));
      # foreach ($products as $k => $v)
        # $this->response->{$k} = (!empty($v) ? $v : $this->response->{$k})
      $this->error = false;
    };

    /*

    $sql2 = "SELECT GROUP_CONCAT(`OC`.`id`) as `ids`
    FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` `OC`
    WHERE `OC`.`host` IN (?) AND `OC`.`closed` IS NOT NULL AND `OC`.`sales_omit` IN (0)
    ";
    $orders_closed = $GLOBALS['PACMEC']['DB']->FetchObject($sql2, [$data->domain]);

    if($orders_closed !== false){
      $sql3 = "SELECT `OI`.*
        FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders_items')}` `OI`
        WHERE `OI`.`order_id` IN (?) AND `OI`.`type` IN ('product')
      ";
      $orders_items = $GLOBALS['PACMEC']['DB']->FetchAllObject($sql3, [$orders_closed->ids]);
      if($orders_items !== false){
        foreach ($orders_items as $item) {
          $this->response->products_sales += $item->quantity;
          $this->response->stock_sales += $item->quantity * $item->unit_price;
        }
      }
    }
    */

    $sql3 = "SELECT
    	`E`.*
    	#, COUNT(`S`.`id`) AS `products_count`
    	, GROUP_CONCAT(`S`.`product`) AS `products_ids`
    	, SUM(case when `S`.`type` IN ('initial','purchase','return_sale') THEN `S`.`quantity` END) AS 'stock_in'
    	, SUM(case when `S`.`type` IN ('sale','return_purchase') THEN `S`.`quantity` END) AS 'stock_out'
    	, SUM(case when `S`.`type` IN ('sale') THEN `S`.`quantity` END) AS 'stock_sales'
      , SUM(case when `S`.`type` IN ('return_sale') THEN `S`.`quantity` END) AS 'stock_return_sale'
      , SUM(case when `S`.`type` IN ('initial','purchase','return_sale') THEN `S`.`total` END) AS 'total_in'
      , SUM(case when `S`.`type` IN ('sale','return_purchase') THEN `S`.`total` END) AS 'total_out'
    	, SUM(case when `S`.`type` IN ('sale') THEN `S`.`total` END) AS 'total_sales'
    	, SUM(case when `S`.`type` IN ('return_sale') THEN `S`.`total` END) AS 'total_return_sale'
    FROM `{$GLOBALS['PACMEC']['DB']->getTableName('establishments')}` `E`
    LEFT JOIN `{$GLOBALS['PACMEC']['DB']->getTableName('stocktaking')}` `S`
    ON `S`.`establishment`=`E`.`id` WHERE `E`.`host`=? GROUP BY `E`.`id`, `S`.`product`";
    $stocktaking = $GLOBALS['PACMEC']['DB']->FetchAllObject($sql3, [$data->domain]);
    if($stocktaking !== false){
      foreach ($stocktaking as $staking) {
        $staking->products_ids = array_filter(explode(',', $staking->products_ids));
        $staking->products = 0;
        if(count($staking->products_ids)>0){
          $prods = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT COUNT(`id`) as `total`, GROUP_CONCAT(`id`) AS `products_ids` FROM `{$GLOBALS['PACMEC']['DB']->getTableName('products')}` WHERE `id` IN (?)", [implode(',', $staking->products_ids)]);
          if($prods !== false){
            $staking->products = $prods->total;
            $staking->products_ids = array_filter(explode(',', $prods->products_ids));
          }
        }
        $staking->stock = $staking->stock_in - $staking->stock_out;
        $staking->benefit = $staking->total_out - $staking->total_in;
        $staking->type = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT * FROM `{$GLOBALS['PACMEC']['DB']->getTableName('establishments_types')}` WHERE `id`=?", [$staking->type]);
        $staking->address = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT * FROM `{$GLOBALS['PACMEC']['DB']->getTableName('geo_addresses')}` WHERE `id`=?", [$staking->address]);
        $this->response->establishments[] = $staking;
        $this->response->stock += $staking->stock;
        $this->response->stock_in += $staking->stock_in;
        $this->response->stock_out += $staking->stock_out;
        $this->response->stock_sales += $staking->stock_sales;
        $this->response->stock_return_sale += $staking->stock_return_sale;
        $this->response->total_in += $staking->total_in;
        $this->response->total_out += $staking->total_out;
        $this->response->total_sales += $staking->total_sales;
        $this->response->total_return_sale += $staking->total_return_sale;
        $this->response->benefit += $staking->benefit;
        $this->response->establishments_total++;
      }
    }


    #if($this->response !== false) $this->error = false;
    return $this->goReturn();
  }

  public function infoServices($data)
  {
    if(is_array($data)) $data = (object) $data;
    if(!isset($data->domain)) $data->domain = $GLOBALS['PACMEC']['host'];
    $this->details = "Revisando dominio: {$data->domain}";
    $this->response = (object) [
      "services" => 0,
      "services_alert" => 0,
      "services_in_shoppings_carts" => 0,
      "services_sales" => 0,

      "stock" => 0,
      "stock_in_shoppings_carts" => 0,
      "stock_sales" => 0,

      # "services_ids" => [],
      "services_alert_ids" => [],
      "services_in_shoppings_carts_ids" => [],
    ];

    $sql = "SELECT
      COUNT(`S`.`id`) AS `services`
      , COUNT(CASE WHEN `S`.`is_active`=1 THEN `S`.`id` END) AS `stock`
      , COUNT(CASE WHEN `S`.`is_active`<>1 THEN `S`.`id` END) AS `services_alert`
      , COUNT(`SC`.`id`) AS `services_in_shoppings_carts`
      , COUNT(`SC`.`quantity`) AS `stock_in_shoppings_carts`
      #, GROUP_CONCAT(`S`.`id`) AS `services_ids`
      , GROUP_CONCAT(CASE WHEN `S`.`is_active`<>1 THEN `S`.`id` END) AS `services_alert_ids`
      , GROUP_CONCAT(`SC`.`ref_id`) AS `services_in_shoppings_carts_ids`
    FROM `{$GLOBALS['PACMEC']['DB']->getTableName('services')}` `S`
    LEFT JOIN `px_shoppings_carts` `SC` ON `SC`.`ref_id` = `S`.`id` AND `SC`.`type` = 'service'
    WHERE `S`.`host` IN (?)
    ";
    $services = $GLOBALS['PACMEC']['DB']->FetchObject($sql, [$data->domain]);
    if($services !== false) {
      $this->response->services = $services->services;
      $this->response->stock = $services->stock;
      $this->response->services_alert = $services->services_alert;
      $this->response->services_in_shoppings_carts = $services->services_in_shoppings_carts;
      $this->response->stock_in_shoppings_carts = $services->stock_in_shoppings_carts;
      # $this->response->services_ids = explode(',', $services->services_ids);
      $this->response->services_alert_ids = array_filter(explode(',', $services->services_alert_ids));
      # $this->response->services_in_shoppings_carts_ids = array_filter(explode(',', $services->services_in_shoppings_carts_ids));
      $this->error = false;
    };

    $sql2 = "SELECT GROUP_CONCAT(`OC`.`id`) as `ids`
    FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders')}` `OC`
    WHERE `OC`.`host` IN (?) AND `OC`.`closed` IS NOT NULL AND `OC`.`sales_omit` IN (0)
    ";
    $orders_closed = $GLOBALS['PACMEC']['DB']->FetchObject($sql2, [$data->domain]);

    if($orders_closed !== false){
      $sql3 = "SELECT `OI`.*
        FROM `{$GLOBALS['PACMEC']['DB']->getTableName('orders_items')}` `OI`
        WHERE `OI`.`order_id` IN (?) AND `OI`.`type` IN ('service')
      ";
      $orders_items = $GLOBALS['PACMEC']['DB']->FetchAllObject($sql3, [$orders_closed->ids]);
      if($orders_items !== false){
        foreach ($orders_items as $item) {
          $this->response->services_sales += $item->quantity;
          $this->response->stock_sales += $item->quantity * $item->unit_price;
        }
      }
    }
    #if($this->response !== false) $this->error = false;
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
