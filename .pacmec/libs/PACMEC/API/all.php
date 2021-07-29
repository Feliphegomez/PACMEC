<?php
/**
 *
 * @package    PACMEC
 * @category   API
 * @copyright  2020-2021 FelipheGomez
 * @author     FelipheGomez <feliphegomez@pm.me>
 * @license    license.txt
 * @version    0.0.1
 */
namespace PACMEC
{
  /**
   *
   */
  class Link
  {
    public $status = "no_start";

    function __construct()
    {
      global $PACMEC;
      try {
        if(isset($PACMEC['settings']['domain'])){
          $params=['name'=>'John', 'surname'=>'Doe', 'age'=>36)
        }
        else {
          throw new \Exception("Dominio no detectado", 1);
        }
      } catch (\Exception $e) {
        echo $e->getMessage();
        exit;
      }
      /*
      $this->status = "starting";
      // $this->status = "connecting";
      $params=['name'=>'John', 'surname'=>'Doe', 'age'=>36)
      $defaults = array(
        CURLOPT_URL => 'http://myremoteservice/',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $params,
      );
      $ch = curl_init();
      curl_setopt_array($ch, ($options + $defaults));
      */
    }
  }

  /**
   * API
   */
  class API extends \PACMEC\Link
  {
  }
}
