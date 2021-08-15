<?php
/**
 *
 * @package    PACMEC
 * @category   PIM
 * @copyright  2020-2021 FelipheGomez & FelipheGomez CO
 * @author     FelipheGomez <feliphegomez@gmail.com>
 * @license    license.txt
 * @version    0.0.1
 */
namespace PACMEC\System;
class Role extends \PACMEC\System\BaseRecords
{
  const TABLE_NAME = 'permissions_group';
  const COLUMNS_AUTO_T  = [
  ];
	public $id                    = 0;
	public $name                  = null;
  public $permissions           = [];

  public function __construct($opts=null)
  {
    Parent::__construct(false);
    $opts = (object) $opts;
    if(is_object($opts) && isset($opts->id)) $this->get_by_id($opts->id);
  }

  public function set_all($obj)
  {
    $obj = (object) $obj;
    if(isset($obj->id)){
      $this->id = $obj->id;
      $this->name = $obj->name;

      $permissions_tmp = [];
      $sql = "SELECT `group`, `permission` from `{$GLOBALS['PACMEC']['DB']->getTableName('permissions')}` WHERE `group` IN (?) ORDER BY `permission` ASC";
      $result = Self::link()->FetchAllObject($sql, [$this->id]);
      if($result !== false){
        foreach($result as $permission){
          $permissions_tmp[] = $permission->permission;
        }

        foreach (Self::link()->FetchAllObject("SELECT
          `tag`, `name`
        from `{$GLOBALS['PACMEC']['DB']->getTableName('permissions_items')}`
        WHERE `id` IN (?) ", [implode(',', $permissions_tmp)]) as $item) {
          $this->permissions[$item->tag] = $item->name;
        }
      }

      foreach (get_called_class()::COLUMNS_AUTO_T as $key => $atts) {
        $parts = [];
        if(property_exists($this, $key)){
          foreach ($atts["parts"] as $x) {
            if (property_exists($this, $x)) $x = $this->{$x};
            elseif (isset(${$x})) $x = ${$x};
            elseif (isset($$x)) $x = $$x;
            $parts[] = $x;
          }
          $s = ($atts["autoT"] == true) ? __a(implode($atts["s"], $parts)) : implode($atts["s"], $parts);;
          $this->{$key} = $s;
        }
      }
    }
  }

}
