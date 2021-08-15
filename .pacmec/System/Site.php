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
#use PHPExcel\PHPExcel_IOFactory;
class Site extends \PACMEC\System\BaseRecords
{
  const TABLE_NAME = 'sites';
  const COLUMNS_AUTO_T  = [
  ];
	public $id                    = 0;
	public $host                  = null;
	public $name                  = null;
	public $description           = null;
  public $siteurl               = null;
	public $homeurl               = null;
	public $ip_server             = null;
	public $is_active             = false;
	public $ssl                   = false;
	public $logo                  = false;
	public $logo_alt              = false;
	public $favicon               = null;
	public $lang                  = null;
  public $theme                 = null;
  public $keywords              = [];
  public $owner                 = null;
	public $plugins               = [];
	public $socials_links          = [];
	# public $routes                = [];
	#public $dictionary              = [];
	public $team                  = [];
  public $settings              = [];

  public function __construct($opts=null)
  {
    Parent::__construct(false);
    $opts = (object) $opts;
    if(is_object($opts) && isset($opts->id)) $this->get_by_id($opts->id);
    else if(is_object($opts) && isset($opts->domain)) $this->get_by('domain', $opts->domain);
    else if(is_object($opts) && isset($opts->host)) $this->get_by('domain', $opts->host);
  }

  public function set_all($obj)
  {
    $obj = (object) $obj;
    if(isset($obj->id)){
      $this->id = $obj->id;
      $this->host = $obj->domain;
      $this->ip_server = $obj->ip_server;
      $this->is_active = (boolean) $obj->active;
      $this->load_settings();
      $this->load_team();
      $this->description = $this->get_option('sitedescr');
      $this->ssl = $this->get_option('enable_ssl');
      $this->theme = $this->get_option('theme_default');
      $this->keywords = explode(',', $this->get_option('sitekeywords'));
      $this->name = $this->get_option('sitename');
      $this->siteurl = $this->get_option('siteurl');
      $this->homeurl = $this->siteurl.$this->get_option('homeurl');
      $this->lang = $this->get_option('lang_default');
      $this->logo = $this->get_option('sitelogo');
      $this->logo_alt = $this->get_option('sitelogo_alt');
      $this->favicon = $this->get_option('sitefavicon');
      $this->owner = new \PACMEC\System\User(["id" => $obj->created_by]);
      $this->plugins = array_filter(explode(',', $this->get_option('plugins_activated')));
      $this->socials_links = unserialize($this->get_option('socials_links'));

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

  private function load_settings()
  {
    try {
      $sql = "Select * from `{$GLOBALS['PACMEC']['DB']->getTableName('options')}` WHERE `host` IN (?,?) ORDER BY `host` ASC, `option_name` ASC";
			$result = Self::link()->FetchAllObject($sql, ['*', $this->host]);
      if($result !== false){
        #$result
        #$PACMEC['settings'][$option->option_name] = Self::pacmec_parse_value($option->option_value);
        foreach($result as $option){
          $this->settings[$option->option_name] = \PACMEC\System\Run::pacmec_parse_value($option->option_value);
        }
      }

    } catch (\Exception $e) {
      echo $e->getMessage();
      return [];
    }
  }

  private function load_team()
  {
    try {
      $sql = "Select * from `{$GLOBALS['PACMEC']['DB']->getTableName('users_sites')}` WHERE `host` IN (?) ORDER BY `user` ASC";
			$result = Self::link()->FetchAllObject($sql, [$this->host]);
      if($result !== false){
        foreach($result as $site_in_user){
          $site_in_user->user = new \PACMEC\System\User(['id'=>$site_in_user->user]);
          $site_in_user->role = new \PACMEC\System\Role(['id'=>$site_in_user->role]);
          $this->team[] = $site_in_user;
        }
      }

    } catch (\Exception $e) {
      echo $e->getMessage();
      return [];
    }
  }

  public function get_option($key)
  {
    return isset($this->settings[$key]) ? $this->settings[$key] : null;
  }

  public function isActive(){
    return $this->is_active;
  }

  public function getTotalRows() : Array
  {
    $r = [];
    foreach ($GLOBALS['PACMEC']['DB']->get_tables_info() as $tbl => $data) {
      $r[$tbl] = 0;
    }
    return $r;
  }
}
