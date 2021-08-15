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
Class IssabelController extends \PACMEC\System\ControladorBase
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

  public function CallIn($data)
  {
    if(is_array($data)) $data = (object) $data;
    $this->details = $data;
  	return $this->goReturn();
  }

}
