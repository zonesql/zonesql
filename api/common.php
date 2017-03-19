<?php
/** 
 * ZoneSQL - Web based database SQL interface
 *
 * @author Adam Tandowski <info@zonesql.com>
 * @link http://www.zonesql.com
 * @version 1.0.0
 * @package ZoneSQL
 */

// Set up Autoloader using PSR-4 standard.
require_once('lib/ZoneSQL/Psr4Autoloader.php');
$loader = new Psr4AutoloaderClass();
$loader->register();
$loader->addNamespace('ZoneSQL', dirname(__FILE__) . '/lib/ZoneSQL');
$loader->addNamespace('Slim', dirname(__FILE__) . '/lib/Slim');

function getFromArray($array, $element, $default=null){
	
	return isset($array[$element]) && $array[$element] ? $array[$element] : $default;
	
}
