<?php
/**
 *
 * @package    PACMEC
 * @category   System
 * @copyright  2020-2021 FelipheGomez
 * @author     FelipheGomez <feliphegomez@pm.me>
 * @license    license.txt
 * @version    0.0.1
 */
try {
  // PACMEC VAR Global
  global $PACMEC;
  // Path PACMEC
  if (!defined('PACMEC_PATH')) define('PACMEC_PATH', __DIR__);
  // Archivo de configuraciones
  $file_settings = (is_file(PACMEC_PATH . '/.prv/settings.php') && file_exists(PACMEC_PATH . '/.prv/settings.php')) ? PACMEC_PATH . '/.prv/settings.php' : PACMEC_PATH . '/autosettings.php';
  // configuraciones principales del sitio
  require_once $file_settings;
  require_once PACMEC_PATH . '/includes.php';
  $pacmec = \PACMEC\System\Run::exec();
  # echo json_encode($pacmec, JSON_PRETTY_PRINT);
} catch (\Exception $e) {
  echo "Error: \n";
  echo $e->getMessage();
  exit;
}
#echo json_encode($GLOBALS['PACMEC'], JSON_PRETTY_PRINT);
exit;
