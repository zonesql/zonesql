<?php
/** 
 * ZoneSQL - Web based database SQL interface
 *
 * @author Adam Tandowski <info@zonesql.com>
 * @link http://www.zonesql.com
 * @version 1.0.0
 * @package ZoneSQL
 */

ini_set('display_errors', 0);
error_reporting(E_ERROR);

define('ROOT_DIR', '/var/www/zonesql/html/');
define('ROOT_WEB', '/');

require_once 'common.php';

$cfg = array(

// ---------- CONFIGURABLE SECTION: START ----------

// ADODB or PDO
//'db_interface'			=> 'ADODB',
'db_interface'				=> 'PDO',

// Set the authentication method to access zonesql. 
// config - username and password are specified in the config file
// none - no security is required (not recommended)
'authentication'		=> 'config',
'username'				=> 'test',
'password'				=> 'user',

// Options for setting the connection method. csv list
// config - the connections are hardcoded in the config file.
// ui - user interface, user connects to the database via entering in the settings dialog
'connection_methods'	=> 'config,ui',

// Set up Connections as follows (or leave empty to use Dialog/UI):
/*
EXAMPLE CONNECTION:
'connections' => array (
	array(
		'title'			=> 'My Connection', 
		'type'			=> 'mysql',			// Options: mssql, mysql or sqlite3
		'host'			=> 'dbhost',		// Server Hostname or IP
		'port'			=> 3306,			// Database port. Can omit if it is default.
		'database'		=> 'tando',	// The database to connect to
		'username'		=> 'dbuser',		// Database Username
		'password'		=> 'dbpass'		// Database Password
	)
),
*/
'connections' => array (
	array(
		'title'			=> 'My Connection', 
		'type'			=> 'mysql',			// Options: mssql, mysql or sqlite3
		'host'			=> '',		// Server Hostname or IP
		'port'			=> 3306,			// Database port. Can omit if it is default.
		'database'		=> '',	// The database to connect to
		'username'		=> '',		// Database Username
		'password'		=> ''		// Database Password
	)
),
	
// Are grid columns sortable? Default is false as sorting/ordering should be
// specified by sql as necessary
'columns_sortable'		=> false,

// Automatically resizes results grid column widths according to data.
'column_autosize'		=> true,
		
// development or production. Determines which javascript library/path ZoneSQL 
// uses.
// development: will use the /src/ directory (full source, unbuilt, unminified). 
// Slow performance, better for debugging and development
// production: will use the /dist/ directory (after a build dist is created). 
// Much faster performance. 
'environment'			=> 'development'
	
);

// ---------- CONFIGURABLE SECTION: FINISH ----------

if($cfg['environment'] == 'development') {
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}

return $cfg;
