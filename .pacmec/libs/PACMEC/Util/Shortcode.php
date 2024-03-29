<?php
/**
 *
 * @package    PACMEC
 * @category   Shortcode
 * @copyright  2020-2021 FelipheGomez
 * @author     FelipheGomez <feliphegomez@pm.me>
 * @license    license.txt
 * @version    1.0.1
 */

namespace PACMEC\Util;

class Shortcode
{

	/** Crea etiqueta [shortcode][/shortcode]
	 * @param string $tag_name nombre de la etiqueta (por ej. div)
	 * @param string $content contenido de la etiqueta
	 * @param array $estilos estilos de la etiqueta, por defecto array vacio
	 * @param array $attributes array|object asociativo con elementos y su valor, por ejemplo array('id' => 'mi-tag')
	 * @return string
	 */
	public static function tag($tag_name, $content, $styles = array(), $attributes, $tag_single = false){
		$attributes_str = "";
		foreach ($attributes as $key => $val) {
			/*if(!is_object($val) && !is_array($val)) {

			}
			else {
				$attributes_str .= ' ' . $key . '="' . ($val) . '"';
				exit;
			}*/
			$attributes_str .= ' ' . $key . '="' . $val . '"';
		}
		return '[' . $tag_name . Self::tag_class($styles) . $attributes_str . (($tag_single == false) ? ']' : '/]') . (($tag_single == false) ? $content . '[/' . $tag_name . ']' : '') . "\n";
	}

	/**
	 * @param array $estilos
	 * @return string con el class de una etiqueta (o un string vacio si no hay estilos)
	 */
	private static function tag_class($styles = array()){
		if(!is_array($styles)){
			$styles = array($styles);
		}
		return (!empty($styles)) ? ' class="' . implode(" ", $styles) . '"' : "";
	}
}

?>
