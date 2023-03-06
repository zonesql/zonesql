<?php
/** 
 * ZoneSQL - Web based database SQL interface
 *
 * @author Adam Tandowski <info@zonesql.com>
 * @link http://www.zonesql.com
 * @version 1.0.0
 * @package ZoneSQL
 */
namespace ZoneSQL;

ini_set('display_errors', 1);
error_reporting(E_ALL);

class Conn {

    public $dbInterface; 
    public $type;
    public $host;
    public $port;
    public $database;
    private $db;
    public $username;
    public $error = null;
	public $cfg;
	
	public static $meta = array(
		'mysql' => array(
			'databases' => 'SHOW DATABASES',
			'tables'	=> 'SHOW TABLES FROM ?database',
			'columns'	=> 'SHOW COLUMNS FROM ?table IN ?database',
			'ignoredbs'	=> array('information_schema', 'performance_schema', 'mysql'),
			'defaultport' => 3306
		),
		'mssql' => array(
			'databases' => 'SELECT name FROM master..sysdatabases',
			'tables'	=> "SELECT table_name FROM ?database.information_schema.tables WHERE table_type = 'BASE TABLE' AND table_catalog = '?database'",
			'columns'	=> "SELECT column_name, data_type + '(' + CAST(COALESCE(character_maximum_length, numeric_precision) AS VARCHAR(10)) + ')' FROM information_schema.columns WHERE table_name = '?table'",
			'ignoredbs'	=> array(),
			'defaultport' => 1433
		),
		'sqlite3' => array(
			'databases' => "SELECT 'SQLite' AS database_name",
			'tables'	=> "SELECT name FROM sqlite_master WHERE type='table'",
			'columns'	=> "SELECT 'unable_to_list' AS column_name, 'VARCHAR(11)' AS data_type", //TODO: handle this differently.
			'ignoredbs'	=> array()
		)
	);

    /**
     * Constructor
     */
    public function __construct($database=null) {

        $this->cfg = include(dirname(__FILE__) . '/../../config.php');

		$conn = Conn::GetConnection($this->cfg, $database);
		if(!$conn) 
			throw new Exception("Disconnected");

		//TODO are these used? can be private?
        $this->host = getFromArray($conn, 'host');
        $this->type = getFromArray($conn, 'type');
        $this->username = getFromArray($conn, 'username');
		$this->database = getFromArray($conn, 'database');
		$this->port = getFromArray($conn, 'port');

		switch($cfg['db_interface']) {
			case 'ADODB':
				$this->db = new DbAdodb($conn);
				break;
			case 'PDO':
			default:
				//require('DbPdo.php');
				$this->db = new DbPdo($conn);
				break;
		}
			
    }

    /**
     * Interrogates the current connection for a list of the available databases
     * 
     * @return Array List of Databases
     */
    public function GetDatabases() {
		$ignore = self::$meta[$this->type]['ignoredbs']; // ignore dbs like information_schema etc
		$sql = self::$meta[$this->type]['databases'];
		
        $data = array();

		try{

			$this->db->query($sql);
            while($row = $this->db->fetch()) {
                $allVals = array_values($row);
                $val = $allVals[0];
                if(!in_array($val, $ignore)) 
                    $data[] = $val;
            }
        } catch ( Exception $e ) {
            error_log('Exception: ' . var_export($e, true));
            $this->error = $e->getMessage();
        }

        return $data;
    }

    /**
     * Interrogates the current database for a list of tables
     * 
     * @return Array List of Tables
     */
    public function GetTables($db) {

		$sql = self::$meta[$this->type]['tables'];
		$sql = str_replace(
		  '?database', $db, 
		  $sql
		);		

		$data = array();
        
        try{

			$this->db->query($sql);

			while($row = $this->db->fetch()) {
				$allVals = array_values($row);
                $data[] = $allVals[0];
            }
			
        } catch ( Exception $e ) {
            //error_log('Exception: ' . var_export($e, true));
            $this->error = $e->getMessage();
        }

		return $data;
    }

    /**
     * Show all columns (name and data type) for a given table.
     * 
     * @return Array List of Columns
     */
    public function GetColumns($db, $table) {
		$sql = self::$meta[$this->type]['columns'];
		$sql = str_replace(
		  array('?database', '?table'), 
		  array($db, $table), 
		  $sql
		);		
		
        $data = array();
        try{
			
            $this->db->query($sql);
            while($row = $this->db->fetch()) {
                $allVals = array_values($row);
                $data[] = array($allVals[0], $allVals[1]);
            }
        } catch ( Exception $e ) {
            error_log('Exception: ' . var_export($e, true));
            $this->error = $e->getMessage();
        }

        return $data;
    }       

    /**
     * Handle actual SQL request
     * 
     * @return Result Set
     */
    public function ProcessRequest($sql, $type="data", $start=null, $end=null, $total=0) {
		
		//$rs = $this->db->SelectLimit($sql, $limit, $offset);

        $data = array();
        $columns = array();

        try{
			$this->db->query($sql);
			if($this->error)
				throw new Exception(var_export($error, true));

			$i = 0;
			
			if($type == "init" && !count($columns)) {
				
				// If type is init, we just get the columns on the first iteration 
				// then we're done. data will come in subsequent request.
				$columns = $this->ExtractColumnData();
				
			} else {
				
				while($row = $this->db->fetch()) {

					// Only capture rows within the dgrid requested range 
					if($i >= $start) 
						$data[] = $row;

					// Once we have what we need, return
					if($i > $end) 
						break;

					$i++;
				}
				if($data && !$total) 
					$total = $this->getRowCount($sql);
			}
			
		} catch ( Exception $e ) {	
            error_log('Exception thrown: ' . var_export($e, true));
            $this->error = $e->getMessage();
        }
		
        $ret = '';
        if($this->error)
            $ret = array('error' => $this->error);
        else {         
            $ret = array('columns' => $columns, 'items' => $data, 'total' => $total);

			// Check to see if the command was special case 'use database'
			$sql = trim($sql);
			if(strpos($sql, 'use ') == 0) {
				$database = substr($sql, 4);
				$ret['database'] = $database;
			}
			
		}

        return $ret;
    }
	
	protected function GetRowCount($sql) {
		$row = array();
		$sql = "SELECT COUNT(*) total FROM (" . $sql . ") sq";
		try {
			$this->db->query($sql);
			$row = $this->db->fetch();
		} catch ( Exception $e ) {	
            error_log('Error obtaining total in GetRowCount: ' . var_export($e, true));
        }
			
		return getFromArray($row, 'total', 0);
	}
	
	/**
	 * Extract column data from dataset.
	 * 
	 * @return Array columns
	 */
	protected function ExtractColumnData() {
		
		$columns = array();
		
		if($row = $this->db->fetch()) {
			
			$cols = array_keys($row);

			foreach($cols as $col) {

				$columns[($col)] = array('label' => $col);//, 'sortable' => $this->cfg['columns_sortable']);

				// TODO: add this on client side, rather than passing
				// down the extra server load in ajax request?
				if(!$this->cfg['columns_sortable'])
					$columns[($col)]['sortable'] = false;
			}
		}
		
		return $columns;
	}	


	public static function GetConnection($cfg, $database=null) {
		
		$conn = Conn::GetSessionConnection();
		//error_log('conn: ' . var_export($conn, true));
		if($database)
			$conn['database'] = $database;
		//error_log('database: ' . $database);
		if(!$conn && strpos($cfg['connection_methods'], 'config') !== false) {
			
			$conn = isset($cfg['connections'][0]) ? $cfg['connections'][0] : null;
			if($database)
				$conn['database'] = $database;
			
			Conn::RegisterSessionConnection($conn);
			
		}
		
		return $conn;
		
	}
	
	public static function GetSessionConnection() {

		return isset($_SESSION['conn']) ? $_SESSION['conn'] : null;
		
	}
	
	public static function RegisterSessionConnection($params) {
		
		if(!$params)
			return;
		
		// If no port is set, use the default ofr that db type.
		if(!getFromArray($params, 'port')) 
				
			$params['port'] = getFromArray(self::$meta[($params['type'])], 'defaultport'); 
		
		$_SESSION['conn'] = $params;
		
	}
}
