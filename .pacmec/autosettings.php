<?php
/**
 *
 * @package    PACMEC
 * @category   System
 * @copyright  2020-2021 FelipheGomez
 * @author     FelipheGomez <feliphegomez@pm.me>
 * @license    license.txt
 * @version    1.0.1
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('DB_port', '3306');                                   // Base de datos: Puerto de conexion (Def: 3306)
define('DB_driver', 'mysql');                                // Base de datos: Controlador de la conexion (Def: mysql)
define('DB_host', 'localhost');                              // Base de datos: Servidor/Host de conexion (Def: localhost)
define('DB_user', 'pacmec_us');                              // Base de datos: Usuario de conexion
define('DB_pass', 'KHGBnVdX6fZluUssg3wl');                   // Base de datos: Contraseña del usuario
define('DB_database', 'pacmec_db');                          // Base de datos: Nombre de la base de datos
define('DB_charset', 'utf8mb4');                             // Base de datos: Caracteres def
define('DB_prefix', 'px_');                                  // Base de datos: Prefijo de las tablas (Opcional)

define('PACMEC_LANG_DEF', 'es');
