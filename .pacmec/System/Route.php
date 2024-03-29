<?php
/**
 *
 * @package    PACMEC
 * @category   Route
 * @copyright  2020-2021 FelipheGomez
 * @author     FelipheGomez <feliphegomez@pm.me>
 * @license    license.txt
 * @version    1.0.1
 */

namespace PACMEC\System;

class Route extends \PACMEC\System\BaseRecords
{
  const TABLE_NAME = 'routes';
  const COLUMNS_AUTO_T  = [
  ];
	public $id = -1;
	public $is_actived = 1;
	public $parent = null;
	public $permission_access = null;
	public $title = 'no_found';
	public $theme = null;
	public $comments_enabled = false;
	public $description = 'No Found';
	public $content = '';
	public $request_uri = '/404';
	public $request_host = '/404';
	public $layout = 'pages-error';
	public $keywords = [];
	public $meta = [];
	public $rating_number = 0;
	public $rating_porcen = 0;
	public $comments = [];

	public function __construct($args=[])
	{
    Parent::__construct(false);
		$args = (object) $args;
		parent::__construct("routes", false);
		if(isset($args->id)){ $this->get_by('id', $args->id); }
		else if(isset($args->request_uri)){ $this->get_by('request_uri', __url_s_($args->request_uri)); }
	}

	public static function encodeURIautoT(string $page_slug) : string
	{
		$url_explode = explode('/', $page_slug);
		if(!isset($url_explode[1]) || empty($url_explode[1])) return $page_slug;
		switch ($url_explode[1]) {
			case ('%autot_services%'):
				$url_explode[1] = _autoT('%autot_services%');
				break;
			default:
				break;
		}
		return implode('/', $url_explode);
	}

	public static function decodeURIautoT(string $page_slug) : string
	{
		$url_explode = explode('/', $page_slug);
		switch ($url_explode[1]) {
			case _autoT('%autot_services%'):
				$url_explode[1] = '%autot_services%';
				break;
			default:
				break;
		}
		return implode('/', $url_explode);
	}

	public static function allLoad()
	{
		$r = [];
		if(!isset($GLOBALS['PACMEC']['DB'])){ return $r; }
		foreach($GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT * FROM {$this->getTable()}", []) as $menu){
			$r[] = new Self($menu);
		}
		return $r;
	}

	public function get_id($a)
	{
		return $this->get_by('id',$a);
	}

	public function get_by($column='id', $val="")
	{
		try {
			global $PACMEC;
			$this->set_all(Self::link()->FetchObject(
				"SELECT * FROM `{$GLOBALS['PACMEC']['DB']->getTableName(SELF::TABLE_NAME)}`
					WHERE `{$column}`=?
					AND `host` IN ('*', ?) ORDER BY `host` desc
					"
				, [
					$val,
					$PACMEC['host']
				]
			));
			return $this;
		}
		catch(\Exception $e){
			return $this;
		}
	}

	function set_all($arg=null)
	{
		global $PACMEC;
		$redirect = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : infosite("siteurl").$GLOBALS['PACMEC']['path'];
		$url_login = infosite("siteurl").__url_s("/%pacmec_signin%");
		if($arg !== null){
			if(\is_object($arg) || \is_array($arg)){
				$arg = (array) $arg;
				switch ($arg['permission_access']) {
					case null:
					break;
					default:
					$check = \validate_permission($arg['permission_access']);
					if($check == false){
						//if(\isGuest()){ $arg['layout'] = 'pages-signin'; } else { $arg['layout'] = 'pages-error'; }
						//$arg['layout'] = 'pages-error';
						$this->layout = 'pages-signin';
						if(isUser()) $arg['content'] = "[pacmec-errors title=\"route_no_access_title\" content=\"route_no_access_content\"][/pacmec-errors]";
						else $arg['content'] = ('[pacmec-form-signin redirect="'.infosite('siteurl').$PACMEC['path'].'"][/pacmec-form-signin]');
					}
					break;
				}
				foreach($arg as $k=>$v){
					switch ($k) {
						case 'page_slug':
						$this->{$k} = \__url_s(SELF::encodeURIautoT($v));
						break;
						default:
						$this->{$k} = ($v);
						break;
					}
				}
			}

			if(!$this->isValid()){
				$_explo = explode('/', $GLOBALS['PACMEC']['path']);
				$_exploder = [];
				foreach ($_explo as $key => $value) if(!empty($value)) $_exploder[] = $value;
				if (isset($_exploder[0]) && count($_exploder)==1 && $_exploder[0] === $GLOBALS['PACMEC']['permanents_links']['%pacmec_signin%']) {
					$this->id = 1;
					$this->request_uri = $GLOBALS['PACMEC']['path'];
					$this->theme = null;
					$this->layout = 'pages-signin';
					$this->title = __a('signin');
					//$this->setAll($PACMEC['route']);
					if(\isUser()) header("Location: ".$redirect);
		      //echo "<meta http-equiv=\"refresh\" content=\"0; url={$redirect}\">";
				}
				elseif (isset($_exploder[0]) && count($_exploder)==1 && $_exploder[0] === $GLOBALS['PACMEC']['permanents_links']['%forgotten_password_slug%']) {
					$this->id = 1;
					$this->request_uri = $GLOBALS['PACMEC']['path'];
					$this->theme = null;
					$this->layout = 'pages-forgotten-password';
					$this->title = __a('pacmec_forgotten_password');
					//$this->setAll($PACMEC['route']);
					if(\isUser()){
						header("Location: ".$redirect);
					}
		      //echo "<meta http-equiv=\"refresh\" content=\"0; url={$redirect}\">";
				}
				else if (isset($_exploder[0]) && count($_exploder)==1 && $_exploder[0] === $GLOBALS['PACMEC']['permanents_links']['%pacmec_meaccount%']) {
					if(!\isUser()){
						//$redirect = infosite("siteurl").infosite("homeurl");
						header("Location: ".$url_login."?&redirect=".urlencode($redirect));
					}
					$this->id = 1;
					$this->request_uri = $GLOBALS['PACMEC']['path'];
					$this->theme = null;
					$this->layout = 'me-account';
					$this->title = __a('me_account');
					$this->description = __a('me_account_descr');
					$this->user = $GLOBALS['PACMEC']['session'];
		      //echo "<meta http-equiv=\"refresh\" content=\"0; url={$redirect}\">";
				}
				else {
					$this->layout = 'pages-error';
					$this->content = "[pacmec-errors title=\"route_no_actived_title\" content=\"route_no_actived_content\"][/pacmec-errors]";
				}
			}
		}
		if(is_null($this->theme)) $this->theme = $GLOBALS['PACMEC']['site']->theme;
		if(\validate_theme($this->theme)==false) $GLOBALS['PACMEC']['site']->theme;
		$acti = \activation_theme($this->theme);
		if($this->id <= 0){
			$this->layout = 'pages-error';
			$this->content = "[pacmec-errors title=\"error_404_title\" content=\"error_404_content\"][/pacmec-errors]";
		} else {
			#$this->keywords = ;
			$this->keywords = array_merge($PACMEC['site']->keywords, explode(',', $this->keywords));
			$this->title = __a($this->title);
		}
		$this->load_ratings();
	}

	private function load_ratings()
	{
		global $PACMEC;
		$rating = \PACMEC\System\Ratign::get_all_uri($PACMEC['path']);
		$this->rating_number = $rating->rating_number;
		$this->rating_porcen = $rating->rating_porcen;
		$this->comments = $rating->votes;
	}

  public function getMeta()
  {
    try {
      if($this->id>0){
        $result = $GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT * FROM `{$this->getTable()}_meta` WHERE `route_id`=? ORDER BY `ordering` DESC", [$this->id]);
        if(is_array($result)) {
          $this->meta = [];
          foreach ($result as $meta) {
            $meta->attrs = json_decode($meta->attrs);
            $this->meta[] = $meta;
          }
        }
        return [];
      }
    }
    catch(\Exception $e){
      return [];
    }
  }
}
