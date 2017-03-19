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

interface iDb {

    public function __construct($conn);
    public function query($sql=null);
    public function fetch();

}
