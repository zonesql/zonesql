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

class DbPdo implements iDb {

    public $db;
    public $stmt;

    /**
     * Constructor
     */
    public function __construct($conn) {

        $this->db = new \PDO($conn['type'] . ':host=' . $conn['host'] . ';dbname=' . $conn['database'], $conn['username'], $conn['password'] );
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    }

    public function query($sql=null) {
        try {
            $this->stmt = $this->db->prepare($sql);

            $this->stmt->execute(); 
        } catch (\PDOException $e) {

            throw new Exception("Error: " . $e->getMessage());

        }

    }

    public function fetch() {

        $row = $this->stmt->fetch(\PDO::FETCH_ASSOC);
        return $row;

    }
	
}

?>