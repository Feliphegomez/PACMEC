<?php
/**
 *
 * @package    PACMEC
 * @category   Stocktaking
 * @copyright  2020-2021 FelipheGomez & FelipheGomez CO
 * @author     FelipheGomez <feliphegomez@gmail.com>
 * @license    license.txt
 * @version    1.0.1
 */
namespace PACMEC\System;
class Stocktaking extends \PACMEC\System\BaseRecords
{
  const TABLE_NAME = 'stocktaking';
  const COLUMNS_AUTO_T  = [
  ];

  public function __construct($opts=null)
  {
    Parent::__construct();
    if(is_object($opts) && isset($opts->id)) $this->get_by_id($opts->id);
  }

  public function set_all($obj)
  {
    Parent::set_all($obj);
    if($this->isValid()){
    }
  }

  public function remove_this()
  {
    $sql = "DELETE FROM `{$GLOBALS['PACMEC']['DB']->getTableName(SELF::TABLE_NAME)}` WHERE  `id`=?";
    return $GLOBALS['PACMEC']['DB']->FetchObject($sql, [$this->id]);
  }

  public static function prices_min_max()
  {
    $sql = "SELECT MIN(`price_normal`) AS `price_min`, MAX(`price_normal`) AS `price_max` FROM `{$GLOBALS['PACMEC']['DB']->getTableName(SELF::TABLE_NAME)}` WHERE `host` IN ".(\siteinfo('domain_estrict')==false ? "('*', ?)" : "(?)");
    return $GLOBALS['PACMEC']['DB']->FetchObject($sql, [$GLOBALS['PACMEC']['host']]);
  }

  public static function table_list_html(array $items) : String
  {
    global $PACMEC;
    $table = new \PACMEC\Table([],0,0,['pacmec-table-all'], []);
    $table->setStylesHeader(["thead-light"]);
    $table->addHeaderRow([
      \PACMEC\Util\Html::tag('a', \PACMEC\Util\Html::tag('i', '', ['fa fa-plus']), ['btn btn-sm btn-outline-success btn-hover-success'], [
        'href'=>__url_s("/%admin_products_slug%?create_item=true")
      ])
      , __a('sku_ref')
      , __a('name')
      , __a('common_names')
      // , __a('description')
      , __a('unid')
      , __a('is_active')
      , __a('available')
      , __a('price_normal')
      , __a('price_promo')
      #, __a('created')
      #, __a('updated')
      , \PACMEC\Util\Html::tag('a', \PACMEC\Util\Html::tag('i', '', ['fa fa-database']), ['btn btn-sm btn-outline-teal btn-hover-teal'], [
        'href'=>__url_s("/%admin_products_slug%?export=true")
      ])
      . \PACMEC\Util\Html::tag('a', \PACMEC\Util\Html::tag('i', '', ['fa fa-cloud-upload']), ['btn btn-sm btn-outline-teal btn-hover-teal'], [
        'href'=>__url_s("/%admin_products_slug%?import=true")
      ])
    ]);
    foreach ($items as $item) {
      $tags = "";
      foreach ($item->common_names as $tag) $tags .= \PACMEC\Util\Html::tag('span', $tag, ['pacmec-tag pacmec-small pacmec-green pacmec-round-large']);
      $btns = "";
      $btns .= \PACMEC\Util\Html::tag('a', \PACMEC\Util\Html::tag('i', '', ['fa fa-eye']), ['pacmec-button pacmec-padding-small pacmec-circle pacmec-white pacmec-border pacmec-border-teal'], [ 'href'=>$item->link_view ]);
      $btns .= \PACMEC\Util\Html::tag('a', \PACMEC\Util\Html::tag('i', '', ['fa fa-edit']), ['pacmec-button pacmec-padding-small pacmec-circle pacmec-white pacmec-border pacmec-border-orange'], [ 'href'=>$item->link_edit."&redirect=".urlencode(infosite('siteurl').$PACMEC['path']) ]);
      $btns .= \PACMEC\Util\Html::tag('a', \PACMEC\Util\Html::tag('i', '', ['fa fa-trash']), ['pacmec-button pacmec-padding-small pacmec-circle pacmec-white pacmec-border pacmec-border-red'], [ 'href'=>$item->link_remove ]);
      $table->addRow([
        // $item->id
        \PACMEC\Util\Html::tag('img', '', [], ['src'=>$item->thumb, 'width'=>"75px"], true)
        , $item->sku
        , $item->name
          . "<br><a href=\"{$item->link_view}\" target=\"_blank\"><small>{$item->link_view}</small></a>"
        , $tags
        # , __a($item->status)
        // , $item->description
        , $item->unid
        , json_encode($item->is_active)
        , $item->available
        , formatMoney($item->price_normal)
        , formatMoney($item->price_promo)
        #, $item->created
        #, $item->updated
        , $btns
      /*
      */
      ]);
    }
    return \PACMEC\Util\Html::tag('div', $table, ['pacmec-responsive'], []);
  }

  public static function table_list_html_pagination(array $items, $total_result, $page=1, $limit=25) : String
  {
    global $PACMEC;
    # echo json_encode([$items, $total_result, $page, $limit]);
    $max_pages_float = (float) ($total_result/$limit);
    $max_pages = (int) ($total_result/$limit);
    if($max_pages<$max_pages_float) $max_pages += 1;
    $_url_pagination = $PACMEC['fullData'];
    if(isset($_url_pagination['page'])) unset($_url_pagination['page']);
    $url_pagination = $PACMEC['path'].http_build_query($_url_pagination);
    $table = Self::table_list_html($items);
    $table .= '
      <nav>
        <ul class="pagination pagination-lg">
          <li class="page-item">
            <a class="page-link" href="'.$PACMEC['path'].'?'.http_build_query($_url_pagination).'" aria-label="Previous">
              <span aria-hidden="true">&laquo;</span>
            </a>
          </li>';
          if (($page-4)>0) $table .= '<li class="page-item"><a class="page-link" href="'.$PACMEC['path'].'?'.http_build_query(array_merge($_url_pagination, ['page'=>($page-4)])).'">'.($page-4).'</a></li>';
          if (($page-3)>0) $table .= '<li class="page-item"><a class="page-link" href="'.$PACMEC['path'].'?'.http_build_query(array_merge($_url_pagination, ['page'=>($page-3)])).'">'.($page-3).'</a></li>';
          if (($page-2)>0) $table .= '<li class="page-item"><a class="page-link" href="'.$PACMEC['path'].'?'.http_build_query(array_merge($_url_pagination, ['page'=>($page-2)])).'">'.($page-2).'</a></li>';
          if (($page-1)>0) $table .= '<li class="page-item"><a class="page-link" href="'.$PACMEC['path'].'?'.http_build_query(array_merge($_url_pagination, ['page'=>($page-1)])).'">'.($page-1).'</a></li>';
          $table .= '<li class="page-item"><a class="page-link active" href="#">'.$page.'</a></li>';
          if ($max_pages>=($page+1)) $table .= '<li class="page-item"><a class="page-link" href="'.$PACMEC['path'].'?'.http_build_query(array_merge($_url_pagination, ["page"=>($page+1)])).'">'.($page+1).'</a></li>';
          if ($max_pages>=($page+2)) $table .= '<li class="page-item"><a class="page-link" href="'.$PACMEC['path'].'?'.http_build_query(array_merge($_url_pagination, ["page"=>($page+2)])).'">'.($page+2).'</a></li>';
          if ($max_pages>=($page+3)) $table .= '<li class="page-item"><a class="page-link" href="'.$PACMEC['path'].'?'.http_build_query(array_merge($_url_pagination, ["page"=>($page+3)])).'">'.($page+3).'</a></li>';
          if ($max_pages>=($page+4)) $table .= '<li class="page-item"><a class="page-link" href="'.$PACMEC['path'].'?'.http_build_query(array_merge($_url_pagination, ["page"=>($page+4)])).'">'.($page+4).'</a></li>';
    $table .= '
        </ul>
      </nav>
    </div>';
    return $table;
  }

  public function create($columns_save=null)
  {
    $columns = $this->getColumns();
  	$columns_save = $columns_save==null ? $this->getColumns() : $columns_save;
  	$columns_a = [];
  	$columns_f = [];
  	$items_send = [];
  	try {
    	foreach($columns as $i){
    		if(
          isset($this->{$i})
          && $i!=='id'
          && in_array($i, $columns_save)
        ){
    			$columns_f[] = $i;
    			$columns_a[] = "?";
    			$columns_b[] = " `{$i}`=? ";
    			$items_send[] = $this->{$i};
    		}
    	}
      $sql = "INSERT INTO `{$GLOBALS['PACMEC']['DB']->getTableName(SELF::TABLE_NAME)}` (".implode(',', $columns_f).")
        SELECT ".implode(",", $columns_a)."
        WHERE NOT EXISTS(SELECT 1 FROM `{$GLOBALS['PACMEC']['DB']->getTableName(SELF::TABLE_NAME)}` WHERE ".implode(" AND ", $columns_b).")";
      $insert = $GLOBALS['PACMEC']['DB']->FetchObject($sql, array_merge($items_send, $items_send));
      // sleep(1);
      $select = $GLOBALS['PACMEC']['DB']->FetchObject(
        "SELECT `id` FROM `{$GLOBALS['PACMEC']['DB']->getTableName(SELF::TABLE_NAME)}` WHERE ".implode(" AND ", $columns_b)
        , $items_send
      );
      if($insert!==false){
        $this->get_by_id($insert);
        return true;
      }
      return false;
  	}catch (Exception $e){
  		return false;
  	}
  }

  public function save($columns_save=null)
  {
  	$columns = $columns_save==null ? $this->getColumns() : $columns_save;
  	$columns_a = [];
  	$columns_f = [];
  	$items_send = [];
  	try {
    	foreach($columns as $i){
        if(isset($this->{$i}) && $i!=='id' && !empty($this->{$i}) && $this->{$i} !== null){
          $columns_f[] = $i;
          $columns_a[] = "?";
          $columns_b[] = " `{$i}`=? ";
          $items_send[] = $this->{$i};
        }
    	}
      $sql = "UPDATE `{$GLOBALS['PACMEC']['DB']->getTableName(SELF::TABLE_NAME)}` SET ".implode(",", $columns_b)." WHERE id=?";
      $s = array_merge($items_send, [$this->id]);
      return (bool) $GLOBALS['PACMEC']['DB']->FetchObject($sql, $s);
  	}catch (Exception $e){
  		return 0;
  	}
  }

}
