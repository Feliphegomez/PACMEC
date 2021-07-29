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

require_once PACMEC_PATH . '/libs/PACMEC/API/all.php';

$PACMEC_API = new \PACMEC\API();
echo "\n";
echo json_encode($PACMEC_API);
