<?php
/** 
 * ZoneSQL - Web based database SQL interface
 *
 * @author Adam Tandowski <info@zonesql.com>
 * @link http://www.zonesql.com
 * @version 1.0.0
 * @package ZoneSQL
 */

//define('ROOT_DIR', str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/'));
//define('ROOT_WEB', '/' . trim(str_replace(ROOT_DIR, '', str_replace('\\', '/', dirname(dirname(__FILE__)))), '/') . '/');

// Set up Autoloader using PSR-4 standard.
require_once('lib/ZoneSQL/Psr4Autoloader.php');
$loader = new Psr4AutoloaderClass();
$loader->register();
$loader->addNamespace('ZoneSQL', dirname(__FILE__) . '/lib/ZoneSQL');
$loader->addNamespace('Leaf', dirname(__FILE__) . '/lib/Leaf');
$loader->addNamespace('\\Leaf\\Http\\Request', dirname(__FILE__) . '/lib/Leaf');
$loader->addNamespace('Pimple', dirname(__FILE__) . '/lib/Pimple');

function getFromArray($array, $element, $default=null){
	
	return isset($array[$element]) && $array[$element] ? $array[$element] : $default;
	
}

function checkAuthentication($cfg) {
	// Is user logged in?
	if(getFromArray($cfg, 'authentication') == 'config' && !getFromArray($_SESSION, 'user_authenticated')) {
		session_destroy();
		header('Location: ' . ROOT_WEB . 'login/');
		exit;
	}
}
