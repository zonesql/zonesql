<?php
/** 
 * ZoneSQL - Web based database SQL interface
 *
 * @author Adam Tandowski <info@zonesql.com>
 * @link http://www.zonesql.com
 * @version 1.0.0
 * @package ZoneSQL
 */
require './lib/vendor/autoload.php';

session_start();

// Use Leaf Router for RESTful interface/routes.
use Leaf\Router;
use ZoneSQL\Conn;

$cfg = include('config.php');

checkAuthentication($cfg);

// MATCH example
Router::match("POST", "/connection", function () {

	if($_POST) {
		Conn::RegisterSessionConnection($_POST);	
	}

	header('Location: ..');
	exit;
});

Router::match("POST", "/query", function () {

	$sql = trim(getFromArray($_POST, 'sql'));
	$database = trim(getFromArray($_POST, 'database'));
	$type = trim(getFromArray($_POST, 'type'));
	$total = getFromArray($_POST, 'total');
	$total = $total ? trim($total) : 0;
	
	$start = 0;
	$end = 0;
	$limit = '';
	foreach($_GET as $param => $value){
		if(strpos($param, 'limit') === 0){
			$limit = $param;
			break;
		}
	}
	if($limit){
		preg_match('/(\d+),*(\d+)*/', $limit, $matches);
		if(count($matches) > 2){
			$start = $matches[2];
			$end = $matches[1] + $start;
		}else{
			$end = $matches[1];
		}
	}else{
		$range = '';
		if(isset($_SERVER['HTTP_RANGE'])){
			$range = $_SERVER['HTTP_RANGE'];
		}elseif(isset($_SERVER['HTTP_X_RANGE'])){
			$range = $_SERVER['HTTP_X_RANGE'];
		}
		if($range){
			preg_match('/(\d+)-(\d+)/', $range, $matches);
			$start = $matches[1];
			$end = $matches[2] + 1;
		}
	}
	if($sql) {
		$conn = new Conn($database);
		
		// Process the Request
		$ret = $conn->ProcessRequest($sql, $type, $start, $end, $total);
		
		// $ret has data, columns ,total
		if($type == "data") {
			header('Content-Range: ' . 'items '.$start.'-'.($end-1).'/'.$ret['total']);
		}
		echo json_encode($ret);
	}
});


// Tree getRoot seems to make 2 calls, one to / another to /server 
// so just centralising the function for now
function getRoot() {
	$ret = '';
	try {
		$conn = new Conn();
		$dbs = $conn->GetDatabases();
		$children = array();
		foreach($dbs as $db) {
			$children[] = array(
				'id'	=> strtolower($db),
				'name'	=> $db,
				'children' => 'true'
			);
		}

		// This is just for the sake of SQLite whose hostname is the full file path. //TODO handle more elegantly.
		$cleanHostName = urldecode($conn->host);
		$pos = strrpos($cleanHostName, DIRECTORY_SEPARATOR);
		if($pos) 
			$cleanHostName = substr($cleanHostName, ($pos + 1));

		$ret = array(
			'name'      => $cleanHostName,
			'id'        => 'server',
			'children'  => $children
		);	

	} catch ( Exception $e ) {			
		error_log('Exception: ' . var_export($e, true));
		$ret = array('error' => $e->getMessage());
	}

	return $ret;
}

Router::get("/", function () {
  echo json_encode(array(getRoot()));
});

// necessary??
Router::get("/server", function () {
  echo json_encode(array(getRoot()));
});

// Return list of columns for the given table
Router::get('/(\w+)/(\w+)', function($database, $table) {
	$conn = new Conn();
	$columns = $conn->GetColumns($database, $table);
	$children = array();

	foreach($columns as $column) {
		$name = $column[0];
		$type = $column[1];
		$children[] = array(
			'id'	=> $database . '/' . $table . '/' . strtolower($name),
			'name'	=> $name . ' <span class="type">' . $type . '</span>'
		);
	}

	$ret = array(
		'name'      => $table,
		'id'        => $database . '/' . $table,
		'children'  => $children
	);			

	echo json_encode($ret);
});

// Return list of tables for the given database
Router::get("/{database}", function ($database) {
	$conn = new Conn();
	$tables = $conn->GetTables($database);

	$children = array();

	foreach($tables as $table) {
		$children[] = array(
			'id'	=> $database . '/' . strtolower($table),
			'name'	=> $table,
			'children' => 'true'
		);
	}

	$ret = array(
		'name'      => $database,
		'id'        => $database,
		'children'  => $children
	);			

	echo json_encode($ret);
});


Router::run();