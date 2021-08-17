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
Class ProductsController extends \PACMEC\System\ControladorBase
{

	public function __construct()
  {
		parent::__construct();
		header('Content-Type: application/json');
	}

  private function goReturn()
  {
    if(
      !isset($this->id)
      && !isset($this->records)
    ) {
      $this->code = 0;
      $this->message = 'Invalid data';
    }
		echo json_encode($this);
		return json_encode($this);
  }

  public function index()
  {
		return $this->goReturn();
  }

  public function Single($data)
  {
    if(is_array($data)) $data = (object) $data;
    if(!isset($data->domain)) $data->domain = $GLOBALS['PACMEC']['host'];
    if(!isset($data->kardex)) $data->kardex = false;

    if(isset($data->id) && (int) $data->id > 0){
      $data->id = (int) $data->id;
      $product = new \PACMEC\System\Product((object) ['id' => $data->id]);
      if($product->isValid()) {
        foreach ($product as $key => $value) {
          $this->{$key} = $value;
        }

        if($data->kardex == true){
          $this->kardex = [];
          foreach ($GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT * FROM `{$GLOBALS['PACMEC']['DB']->getTableName('stocktaking')}` WHERE `product` IN (?) ", [$data->id]) as $stocktaking)
          {
            #$stocktaking->establishment = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT * FROM `{$GLOBALS['PACMEC']['DB']->getTableName('establishments')}` WHERE `id` IN (?) ", [$stocktaking->establishment]);
            $this->kardex[] = $stocktaking;
          }
        }
      };
    }
    return $this->goReturn();
  }

  public function CreateStocktaking($data)
  {
    if(is_array($data)) $data = (object) $data;
    if(!isset($data->domain)) $data->domain = $GLOBALS['PACMEC']['host'];
    if(!isset($data->product)) $data->product = null; else $data->product = (int) $data->product;
    if(!isset($data->type)) $data->type = null; else $data->type = (string) $data->type;
    if(!isset($data->description)) $data->description = null; else $data->description = (string) $data->description;
    if(!isset($data->quantity)) $data->quantity = null; else $data->quantity = (float) $data->quantity;
    if(!isset($data->amount)) $data->amount = null; else $data->amount = (float) $data->amount;
    if(!isset($data->price)) $data->price = null; else $data->price = (float) $data->price;

    if(
      $data->domain !== null
      && $data->product !== null
      && $data->type !== null
      && $data->description !== null
      && $data->quantity !== null
      && $data->amount !== null
    ){
      $product = new \PACMEC\System\Product((object) ['id' => $data->product]);
      if($product->isValid()) {
				$stocktaking = new \PACMEC\System\Stocktaking();
				$stocktaking->created_by = \userID();
				$stocktaking->host = $data->domain;
				$stocktaking->product = $data->product;
				$stocktaking->type = $data->type;
				$stocktaking->description = $data->description;
				$stocktaking->quantity = $data->quantity;
				$stocktaking->amount = $data->amount;
				$stocktaking->total = $data->quantity*$data->amount;

				$stocktaking->create([
          'host',
          'product',
          'type',
          'description',
          'quantity',
          'amount',
          'total',
          'created_by',
          'created',
        ]);

				if($stocktaking->isValid()){
					switch ($data->type) {
						case 'initial':
							$product->available = $stocktaking->quantity;
							break;
						case 'purchase':
						case 'return_sale':
							$product->available += $stocktaking->quantity;
							break;
						case 'sale':
						case 'return_purchase':
						$product->available -= $stocktaking->quantity;
						break;
					}
					$product->save([
						'available',
					]);
					$this->error = false;
					$this->id = $stocktaking->id;
				}


				/*
				$inv_inicial = $GLOBALS['PACMEC']['DB']->FetchObject("SELECT * FROM `{$GLOBALS['PACMEC']['DB']->getTableName('stocktaking')}` WHERE `product` IN (?) AND `type` IN ('initial') ORDER BY `id` DESC LIMIT 1", [$data->product]);
				if($inv_inicial !== false){
					$product->available = 0;
					$invs = $GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT
						SUM(CASE WHEN `type` IN ('initial','purchase','return_sale') THEN `quantity` END) AS `total_in`
						SUM(CASE WHEN `type` IN ('sale','return_purchase') THEN `quantity` END) AS `total_out`
					FROM `{$GLOBALS['PACMEC']['DB']->getTableName('stocktaking')}` WHERE `product` IN (?) AND `created` >= ? ORDER BY `id` ASC", [$data->product, $inv_inicial->created]);
				}
				*/
      };
    }
    return $this->goReturn();
  }
}
