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

class DbAdodb implements iDb {

    public $db;
    public $rs;
	public $error;

    /**
     * Constructor
     */
    public function __construct($conn) {

        require_once(dirname(__FILE__) . '/../adodb/adodb.inc.php');

		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$database = isset($conn['database']) && $conn['database'] ? $conn['database'] : '';

		$dsn = $conn['type'] . '://' . 
					(isset($conn['username']) && isset($conn['password']) ? $conn['username'] . ':' . $conn['password'] . '@' : '') .
					$conn['host'] . 
					(isset($conn['port']) && $conn['port'] ? ':' . $conn['port'] : '') . 
					($database ? '/' . $database : '');
					
        //error_log('zonesql dsn: ' . $dsn);
        $this->db = ADONewConnection($dsn);        
		
		if(!$this->db)
			throw new Exception('Unable to connect to' . ($database ? " '" . $conn['database'] . "'" : '') . ' database on ' . $conn['host']. (isset($conn['username']) ? " with user '" . $conn['username'] . "'" : ""));

    }

    public function query($sql=null) {

		$this->rs = $this->db->Execute($sql);

		if($this->rs === false) {
			throw new Exception("Error: " . $this->db->ErrorMsg());
		}
		
    }

    public function fetch() {

        if(!$this->rs->EOF) {
            $row = $this->rs->fields;
            $this->rs->MoveNext();
            return $row;
        } else {
            return false;
        }

    }

}

?>