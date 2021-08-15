<?php
/**
 * Plugin Name: ERP
 * Text Domain: ERP
 * Description: Muchas empresas empresariales utilizan soluciones de recursos para administrar sus operaciones diarias, manejando datos como registros de clientes, precios de productos e inventario. Lo que no siempre está claro es cómo estos datos se pueden integrar sin problemas en su sitio web. En muchos casos, los sitios web de comercio electrónico crean y cumplen pedidos web a través de las propias herramientas de comercio electrónico. Sin embargo, esto generalmente requiere una entrada.
 *
 * Plugin URI: https://github.com/PACMEC/PACMEC-ERP
 * Version: 0.1
 * Author: FelipheGomez
 * Author URI: https://github.com/FelipheGomez
 * (email : feliphegomez@pm.me)
 */
function pacmec_ERP_activation()
{
  try {
    require_once 'includes/definitions.php';
    require_once 'includes/shortcodes.php';
    
  } catch (\Exception $e) {
    echo $e->getMessage();
    exit;
  }
}
register_activation_plugin('ERP', 'pacmec_ERP_activation');
