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

function pacmec_meta_head(){
 $a = "";
 foreach($GLOBALS['PACMEC']['website']['meta'] as $meta){
   $a .= \PACMEC\Util\Html::tag($meta['tag'], $meta['content'], [], $meta['attrs'], (in_array($meta['tag'], ['title'])?false:true))."\t";
 }
 echo $a;
 #echo json_encode($GLOBALS['PACMEC']['website']['meta'], JSON_PRETTY_PRINT);
}
add_action('meta_head', 'pacmec_meta_head');

function pacmec_debug_box(){
 $content = \PACMEC\Util\Html::tag('pre', json_encode($GLOBALS['PACMEC'], JSON_PRETTY_PRINT));
 return \PACMEC\Util\Html::tag('debug', \PACMEC\Util\Html::tag('code', $content));
}
add_shortcode('pacmec-debug', 'pacmec_debug_box');
