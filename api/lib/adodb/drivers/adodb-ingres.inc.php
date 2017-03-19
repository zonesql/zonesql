<?php

// Initial version of ingres driver
//
// Modified form of a mysql driver

// security - hide paths
if (!defined('ADODB_DIR')) die();

if (! defined("_ADODB_M_LAYER")) {
 define("_ADODB_ingres_LAYER", 1 );

function TTTRACE( $msg ) {
	//print "<br>$msg\n";
}


class ADODB_ingres extends ADOConnection {
	
	var $databaseType = 'ingres';
	var $dataProvider = 'ingres';
	var $hasInsertID = true;   //TODO: Consider
	var $hasAffectedRows = true; //TODO: Consider
	var $metaTablesSQL = "SHOW TABLES";	 //TODO: Consider
	var $metaColumnsSQL = "SHOW COLUMNS FROM `%s`"; //TODO: Consider
	var $fmtTimeStamp = "'d-m-Y H:i:s'"; //TODO: Consider
	var $hasLimit = true; //TODO: Consider
	var $hasMoveFirst = true; //TODO: Consider
	var $hasGenID = true; //TODO: Consider
	var $isoDates = true; // accepts dates in ISO format //TODO: Consider
	var $sysDate = "date('today')"; 
	var $sysTimeStamp = "date('now')"; 
	var $hasTransactions = false; //TODO: Consider
	var $forceNewConnect = false; //TODO: Consider
	var $poorAffectedRows = true; //TODO: Consider
	var $clientFlags = 0; //TODO: Consider
	var $substr = "substring"; //TODO: Consider
	var $nameQuote = '`';		/// string to use to quote identifiers and names //TODO: Consider
	var $_bindInputArray = false;
	
	function ADODB_ingres() 
	{			
		// About ADODB_EXTENSION, From: http://adodb.sourceforge.net/:
		// Adodb-ext-504.zip provides up to 100% speedup by replacing parts of ADOdb with C code. 
		//   ADOdb will auto-detect if this extension is installed and use it automatically. This 
		//   extension is compatible with ADOdb 3.32 or later, and PHP 4.3.*, 4.4.*, 5.0.*
		// 
		// This was not available on installation where initial cut was done....
		if (defined('ADODB_EXTENSION')) $this->rsPrefix .= 'ext_';
	}
	
	function ServerInfo()
	{
		//TODO: Validate
		$arr['description'] = ADOConnection::GetOne("select dbmsinfo('_version')");
		$arr['version'] = ADOConnection::_findvers($arr['description']);
		return $arr;
	}
	
	function IfNull( $field, $ifNull ) 
	{
		return " IFNULL($field, $ifNull) "; // if ingres
	}
	
	
	function &MetaTables($ttype=false,$showSchema=false,$mask=false) 
	{	
		//TODO: Consider
		$save = $this->metaTablesSQL;
		if ($showSchema && is_string($showSchema)) {
			$this->metaTablesSQL .= " from $showSchema";
		}
		
		if ($mask) {
			$mask = $this->qstr($mask);
			$this->metaTablesSQL .= " like $mask";
		}
		$ret =& ADOConnection::MetaTables($ttype,$showSchema);
		
		$this->metaTablesSQL = $save;
		return $ret;
	}
	
	
	function &MetaIndexes ($table, $primary = FALSE, $owner=false)
	{
		//TODO: Consider
		// save old fetch mode
        global $ADODB_FETCH_MODE;
        
		$false = false;
        $save = $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        if ($this->fetchMode !== FALSE) {
               $savem = $this->SetFetchMode(FALSE);
        }
        
        // get index details
        $rs = $this->Execute(sprintf('SHOW INDEX FROM %s',$table));
        
        // restore fetchmode
        if (isset($savem)) {
                $this->SetFetchMode($savem);
        }
        $ADODB_FETCH_MODE = $save;
        
        if (!is_object($rs)) {
                return $false;
        }
        
        $indexes = array ();
        
        // parse index data into array
        while ($row = $rs->FetchRow()) {
                if ($primary == FALSE AND $row[2] == 'PRIMARY') {
                        continue;
                }
                
                if (!isset($indexes[$row[2]])) {
                        $indexes[$row[2]] = array(
                                'unique' => ($row[1] == 0),
                                'columns' => array()
                        );
                }
                
                $indexes[$row[2]]['columns'][$row[3] - 1] = $row[4];
        }
        
        // sort columns by order in the index
        foreach ( array_keys ($indexes) as $index )
        {
                ksort ($indexes[$index]['columns']);
        }
        
        return $indexes;
	}

	
	// if magic quotes disabled, use ingres_real_escape_string()
	function qstr($s,$magic_quotes=false)
	{
		//TODO: Consider
		if (is_null($s)) return 'NULL';

		if (!$magic_quotes) {
		
#			if (ADODB_PHPVER >= 0x4300) {
#				if (is_resource($this->_connectionID))
#					return "'".ingres_real_escape_string($s,$this->_connectionID)."'";
#			}
			if ($this->replaceQuote[0] == '\\'){
				$s = adodb_str_replace(array('\\',"\0"),array('\\\\',"\\\0"),$s);
			}
			return  "'".str_replace("'",$this->replaceQuote,$s)."'"; 
		}
		
		// undo magic quotes for "
		$s = str_replace('\\"','"',$s);
		return "'$s'";
	}
	
	function _insertid()
	{
		//TODO: FIXME - this doesn't work
		return ADOConnection::GetOne('SELECT LAST_INSERT_ID()');
		//return ingres_insert_id($this->_connectionID);
	}
	
	function GetOne($sql,$inputarr=false)
	{
		//TODO: Consider
		if ($this->compat323 == false && strncasecmp($sql,'sele',4) == 0) {
			$rs =& $this->SelectLimit($sql,1,-1,$inputarr);
			if ($rs) {
				$rs->Close();
				if ($rs->EOF) return false;
				return reset($rs->fields);
			}
		} else {
			return ADOConnection::GetOne($sql,$inputarr);
		}
		return false;
	}
	
	function BeginTrans()
	{
		//TODO: Consider
		if ($this->debug) ADOConnection::outp("Transactions not supported in 'ingres' driver. Use 'ingrest' or 'ingresi' driver");
	}
	
	function _affectedrows()
	{
		//TODO: Consider
		return ingres_affected_rows($this->_connectionID);
	}
  
 	// See http://www.ingres.com/doc/M/i/Miscellaneous_functions.html
	// Reference on Last_Insert_ID on the recommended way to simulate sequences
 	var $_genIDSQL = "update %s set id=LAST_INSERT_ID(id+1);";
	var $_genSeqSQL = "create table %s (id int not null)";
	var $_genSeqCountSQL = "select count(*) from %s";
	var $_genSeq2SQL = "insert into %s values (%s)";
	var $_dropSeqSQL = "drop table %s";
	
	function CreateSequence($seqname='adodbseq',$startID=1)
	{
		//TODO: Consider
		if (empty($this->_genSeqSQL)) return false;
		$u = strtoupper($seqname);
		
		$ok = $this->Execute(sprintf($this->_genSeqSQL,$seqname));
		if (!$ok) return false;
		return $this->Execute(sprintf($this->_genSeq2SQL,$seqname,$startID-1));
	}
	

	function GenID($seqname='adodbseq',$startID=1)
	{
		//TODO: Consider
		// post-nuke sets hasGenID to false
		if (!$this->hasGenID) return false;
		
		$savelog = $this->_logsql;
		$this->_logsql = false;
		$getnext = sprintf($this->_genIDSQL,$seqname);
		$holdtransOK = $this->_transOK; // save the current status
		$rs = @$this->Execute($getnext);
		if (!$rs) {
			if ($holdtransOK) $this->_transOK = true; //if the status was ok before reset
			$u = strtoupper($seqname);
			$this->Execute(sprintf($this->_genSeqSQL,$seqname));
			$cnt = $this->GetOne(sprintf($this->_genSeqCountSQL,$seqname));
			if (!$cnt) $this->Execute(sprintf($this->_genSeq2SQL,$seqname,$startID-1));
			$rs = $this->Execute($getnext);
		}
		
		if ($rs) {
			$this->genID = ingres_insert_id($this->_connectionID);
			$rs->Close();
		} else
			$this->genID = 0;
		
		$this->_logsql = $savelog;
		return $this->genID;
	}
	
  	function &MetaDatabases()
	{
		//TODO: Consider
		$qid = ingres_list_dbs($this->_connectionID);
		$arr = array();
		$i = 0;
		$max = ingres_num_rows($qid);
		while ($i < $max) {
			$db = ingres_tablename($qid,$i);
			if ($db != 'ingres') $arr[] = $db;
			$i += 1;
		}
		return $arr;
	}
	
		
	// Format date column in sql string given an input format that understands Y M D
	function SQLDate($fmt, $col=false)
	{	
		//TODO: Consider
		if (!$col) $col = $this->sysTimeStamp;
		$s = 'DATE_FORMAT('.$col.",'";
		$concat = false;
		$len = strlen($fmt);
		for ($i=0; $i < $len; $i++) {
			$ch = $fmt[$i];
			switch($ch) {
				
			default:
				if ($ch == '\\') {
					$i++;
					$ch = substr($fmt,$i,1);
				}
				/** FALL THROUGH */
			case '-':
			case '/':
				$s .= $ch;
				break;
				
			case 'Y':
			case 'y':
				$s .= '%Y';
				break;
			case 'M':
				$s .= '%b';
				break;
				
			case 'm':
				$s .= '%m';
				break;
			case 'D':
			case 'd':
				$s .= '%d';
				break;
			
			case 'Q':
			case 'q':
				$s .= "'),Quarter($col)";
				
				if ($len > $i+1) $s .= ",DATE_FORMAT($col,'";
				else $s .= ",('";
				$concat = true;
				break;
			
			case 'H': 
				$s .= '%H';
				break;
				
			case 'h':
				$s .= '%I';
				break;
				
			case 'i':
				$s .= '%i';
				break;
				
			case 's':
				$s .= '%s';
				break;
				
			case 'a':
			case 'A':
				$s .= '%p';
				break;
				
			case 'w':
				$s .= '%w';
				break;
				
			 case 'W':
				$s .= '%U';
				break;
				
			case 'l':
				$s .= '%W';
				break;
			}
		}
		$s.="')";
		if ($concat) $s = "CONCAT($s)";
		return $s;
	}
	

	// returns concatenated string
	// much easier to run "ingresd --ansi" or "ingresd --sql-mode=PIPES_AS_CONCAT" and use || operator
	function Concat()
	{
		//TODO: Consider
		$s = "";
		$arr = func_get_args();
		
		// suggestion by andrew005@mnogo.ru
		$s = implode(',',$arr); 
		if (strlen($s) > 0) return "CONCAT($s)";
		else return '';
	}
	
	function OffsetDate($dayFraction,$date=false)
	{		
		//TODO: Consider
		if (!$date) $date = $this->sysDate;
		
		$fraction = $dayFraction * 24 * 3600;
		return $date . ' + INTERVAL ' .	 $fraction.' SECOND';
		
//		return "from_unixtime(unix_timestamp($date)+$fraction)";
	}
	
	// returns true or false
	function _connect($argHostname, $argUsername, $argPassword, $argDatabasename)
	{
		// TODO
		//   Do we need to handle ports here?
		//   if ( 	!empty($this->port)) ??????	 

		TTTRACE( "_connect");
		
		$db = "$argHostname::$argDatabasename";
		$this->_connectionID = ingres_connect($db,$argUsername,$argPassword );
	
		if ( !$this->_connectionID) 
		    return false;
		
		return true;	
	}
	
	// returns true or false
	function _pconnect($argHostname, $argUsername, $argPassword, $argDatabasename)
	{
		// TODO
		//   Do we need to handle ports here?
		//   if ( 	!empty($this->port)) ??????	 
				
		TTTRACE( "_pconnect");
		
		$db = "$argHostname::$argDatabasename";
		$this->_connectionID = ingres_pconnect($db,$argUsername,$argPassword );
					
		if ($this->_connectionID === false) return false;
		if ($this->autoRollback) $this->RollbackTrans();
		//if ($argDatabasename) return $this->SelectDB($argDatabasename);
		return true;	
	}
	
	function _nconnect($argHostname, $argUsername, $argPassword, $argDatabasename)
	{
		//TODO: Consider
		TTTRACE( "_nconnect");
		
		$this->forceNewConnect = true;
		return $this->_connect($argHostname, $argUsername, $argPassword, $argDatabasename);
	}
	
 	function &MetaColumns($table) 
	{
		//TODO: Consider
		$this->_findschema($table,$schema);
		if ($schema) {
			$dbName = $this->database;
			$this->SelectDB($schema);
		}
		global $ADODB_FETCH_MODE;
		$save = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		
		if ($this->fetchMode !== false) $savem = $this->SetFetchMode(false);
		$rs = $this->Execute(sprintf($this->metaColumnsSQL,$table));
		
		if ($schema) {
			$this->SelectDB($dbName);
		}
		
		if (isset($savem)) $this->SetFetchMode($savem);
		$ADODB_FETCH_MODE = $save;
		if (!is_object($rs)) {
			$false = false;
			return $false;
		}
			
		$retarr = array();
		while (!$rs->EOF){
			$fld = new ADOFieldObject();
			$fld->name = $rs->fields[0];
			$type = $rs->fields[1];
			
			// split type into type(length):
			$fld->scale = null;
			if (preg_match("/^(.+)\((\d+),(\d+)/", $type, $query_array)) {
				$fld->type = $query_array[1];
				$fld->max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
				$fld->scale = is_numeric($query_array[3]) ? $query_array[3] : -1;
			} elseif (preg_match("/^(.+)\((\d+)/", $type, $query_array)) {
				$fld->type = $query_array[1];
				$fld->max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
			} elseif (preg_match("/^(enum)\((.*)\)$/i", $type, $query_array)) {
				$fld->type = $query_array[1];
				$arr = explode(",",$query_array[2]);
				$fld->enums = $arr;
				$zlen = max(array_map("strlen",$arr)) - 2; // PHP >= 4.0.6
				$fld->max_length = ($zlen > 0) ? $zlen : 1;
			} else {
				$fld->type = $type;
				$fld->max_length = -1;
			}
			$fld->not_null = ($rs->fields[2] != 'YES');
			$fld->primary_key = ($rs->fields[3] == 'PRI');
			$fld->auto_increment = (strpos($rs->fields[5], 'auto_increment') !== false);
			$fld->binary = (strpos($type,'blob') !== false);
			$fld->unsigned = (strpos($type,'unsigned') !== false);	
			$fld->zerofill = (strpos($type,'zerofill') !== false);
			
			if (!$fld->binary) {
				$d = $rs->fields[4];
				if ($d != '' && $d != 'NULL') {
					$fld->has_default = true;
					$fld->default_value = $d;
				} else {
					$fld->has_default = false;
				}
			}
			
			if ($save == ADODB_FETCH_NUM) {
				$retarr[] = $fld;
			} else {
				$retarr[strtoupper($fld->name)] = $fld;
			}
				$rs->MoveNext();
			}
		
			$rs->Close();
			return $retarr;	
	}
		
	// returns true or false
	function SelectDB($dbName) 
	{
		//TODO: Consider
		$this->database = $dbName;
		$this->databaseName = $dbName; # obsolete, retained for compat with older adodb versions
		if ($this->_connectionID) {
			return @ingres_select_db($dbName,$this->_connectionID);		
		}
		else return false;	
	}
	
	// parameters use PostgreSQL convention, not ingres
	function &SelectLimit($sql,$nrows=-1,$offset=-1,$inputarr=false,$secs=0)
	{
		//TODO: Consider
		$offsetStr =($offset>=0) ? ((integer)$offset)."," : '';
		// jason judge, see http://phplens.com/lens/lensforum/msgs.php?id=9220
		if ($nrows < 0) $nrows = '18446744073709551615'; 
		
		if ($secs)
			$rs =& $this->CacheExecute($secs,$sql." LIMIT $offsetStr".((integer)$nrows),$inputarr);
		else
			$rs =& $this->Execute($sql." LIMIT $offsetStr".((integer)$nrows),$inputarr);
		return $rs;
	}
	
	// returns queryID or false
	function _query($sql,$inputarr)
	{
		TTTRACE( "_query,$sql");

		$rc=ingres_query( $this->_connectionID , $sql );
		
		// The thing that calls this expects one of 
		// 1) FALSE, for a failed query
		// 2) TRUE, for a successful but empty query
		// 3) A "query id" it can use to identify this query 
		
        if ( !is_resource($rc) ) {
			TTTRACE( "_queryres=FALSE");
        	return FALSE;
        }
        	

        // Get to here - it suceeded, so need to this is a select or
	    // a non results query.
		//
		// There are two possible ways to do this. Either
		// (a) Look at how many rows were returned
		// (b) Parse the query and see if select was specified
		//
		// Unfortunatley, running ingres_num_fields seems to stuff the 
		// whole thing - it seesm to set the pointer to the end 
		// of list which means the fetch functions retrun nothing - 
		// so have to search for Select.....
		//
		//  (Update: don't know for certain if that is the case in the new
		//      version of ingres_driver, howver stuck with text match as 
		//      a select with 0 rows is actually a slightly different beast 
		//      to some like an insert ) 
		// 
			
		$hasSelect = strtolower(substr( ltrim($sql),0,6)) == "select";
			
		if ( !$hasSelect ) {
			TTTRACE( "_queryres=TRUE");
			return TRUE;
		}
	
	   // If there are results, this expects to be returned something which 
	   // can be treated as a query id.
		TTTRACE( "_queryres=$rc");
		return $rc; 
	}

	/*	Returns: the last error message from previous database operation	*/	
	function ErrorMsg() 
	{
		if ($this->_logsql) return $this->_errorMsg;
		if (empty($this->_connectionID)) $this->_errorMsg = @ingres_error();
		else $this->_errorMsg = @ingres_error($this->_connectionID);
		return $this->_errorMsg;
	}
	
	/*	Returns: the last error number from previous database operation	*/	
	function ErrorNo() 
	{
		if ($this->_logsql) return $this->_errorCode;
		if (empty($this->_connectionID))  return @ingres_errno();
		else return @ingres_errno($this->_connectionID);
	}
	
	// returns true or false
	function _close()
	{
		//TODO: Consider
		@ingres_close($this->_connectionID);
		$this->_connectionID = false;
	}

	
	/*
	* Maximum size of C field
	*/
	function CharMax()
	{
		//TODO: Consider
		return 255; 
	}
	
	/*
	* Maximum size of X field
	*/
	function TextMax()
	{
		//TODO: Consider
		return 4294967295; 
	}
	
	// "Innox - Juan Carlos Gonzalez" <jgonzalez#innox.com.mx>
	function MetaForeignKeys( $table, $owner = FALSE, $upper = FALSE, $associative = FALSE )
    {
		//TODO: Consider
    	
    	global $ADODB_FETCH_MODE;
		if ($ADODB_FETCH_MODE == ADODB_FETCH_ASSOC || $this->fetchMode == ADODB_FETCH_ASSOC) $associative = true;

         if ( !empty($owner) ) {
            $table = "$owner.$table";
         }
         $a_create_table = $this->getRow(sprintf('SHOW CREATE TABLE %s', $table));
		 if ($associative) $create_sql = $a_create_table["Create Table"];
         else $create_sql  = $a_create_table[1];

         $matches = array();

         if (!preg_match_all("/FOREIGN KEY \(`(.*?)`\) REFERENCES `(.*?)` \(`(.*?)`\)/", $create_sql, $matches)) return false;
	     $foreign_keys = array();	 	 
         $num_keys = count($matches[0]);
         for ( $i = 0;  $i < $num_keys;  $i ++ ) {
             $my_field  = explode('`, `', $matches[1][$i]);
             $ref_table = $matches[2][$i];
             $ref_field = explode('`, `', $matches[3][$i]);

             if ( $upper ) {
                 $ref_table = strtoupper($ref_table);
             }

             $foreign_keys[$ref_table] = array();
             $num_fields = count($my_field);
             for ( $j = 0;  $j < $num_fields;  $j ++ ) {
                 if ( $associative ) {
                     $foreign_keys[$ref_table][$ref_field[$j]] = $my_field[$j];
                 } else {
                     $foreign_keys[$ref_table][] = "{$my_field[$j]}={$ref_field[$j]}";
                 }
             }
         }
         
         return  $foreign_keys;
     }
	 
	
}
	
/*--------------------------------------------------------------------------------------
	 Class Name: Recordset
--------------------------------------------------------------------------------------*/


class ADORecordSet_ingres extends ADORecordSet{	
	
	
	var $databaseType = "ingres";
	var $canSeek = FALSE; // Doesn't natively work for ingres.....


	function ADORecordSet_ingres($queryID,$mode=false) 
	{
		// Impelemenation decision for now is that $queryID will actually be 
		// the ingres connection id....
		//
		// TODO:Test
		

		if ($mode === false) { 
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}

		$this->numberedFields = FALSE;
		$this->namedFields = FALSE;
		
		switch ($mode)
		{
			case ADODB_FETCH_NUM: 
				$this->numberedFields = TRUE;
				break;
				
			case ADODB_FETCH_DEFAULT:
			case ADODB_FETCH_ASSOC:
				$this->namedFields = TRUE;
				break;

			case ADODB_FETCH_BOTH:
			default:
				$this->numberedFields = TRUE;
				$this->namedFields = TRUE;
				break;
		}
		
		TTTRACE( "ADORecordSet_ingres constructor" );
		$this->adodbFetchMode = $mode;
		$this->ADORecordSet($queryID);	
	}
	
	function _initrs()
	{
		// TODO:Test
		TTTRACE( "start _initrs ");
		
		$numflds = ingres_num_fields($this->_queryID);
		$this->_numOfFields = $numflds;
		
		// NOTE - mysql version had both of these with '@' operators before hand - have
		// removed these, pending.....
		
		$fn = "ingres_fetch_" . ($this->namedFields ? "array" : "row");
	
		TTTRACE( "_initrs fn = $fn, removeNums = $removeNums");

		// This call is used to populate the __date_fields_to_map array
        $this->FieldTypesArray();
        
 		$this->_selectedRecords = array();
        while ($row = $fn( $this->_queryID  )) {
        	
        	if ( $this->namedFields && !$this->numberedFields ) {
        		
        		$keys = array_keys( $row );
        		foreach( $keys as $key )
        			if ( is_numeric( $key ) )
        				unset( $row[$key] );

        	}

        	// This maps ingres dates across to Sql Server dates....
            foreach($this->__date_fields_to_map as $key )
                if (array_key_exists( $key, $row ) )
                    $row[$key] = $this->_MapIngresDate( $row[$key] );
        	
			$this->_selectedRecords[] = $row;
        }
			
		$this->_numOfRows = count( $this->_selectedRecords );
		$this->_currRow = -1;
		$this->EOF = false;

		TTTRACE( "_initrs , _numOfFields = $this->_numOfFields, _numOfRows = $this->_numOfRows");
	}
	
	private static $_month_map = null;
	
    private function _MapIngresDate( $date ) {

    	// The purpose of this function is to map ingres dates
    	// across to SQL server dates
    	
    	if ( $date &&
             preg_match( '/^([0-9]{1,2})\-([a-zA-Z]{3})\-([0-9]{4})(.*)/', $date, $m )
           )
        {
        	
        	if ( is_null(self::$_month_map) )
        	   self::$_month_map = 
        	   	array( "jan" => "01", "feb" => "02", "mar" => "03", "apr" => "04", 
        	   	       "may" => "05", "jun" => "06", "jul" => "07", "aug" => "08",
        	   	       "sep" => "09", "oct" => "10", "nov" => "11", "dec" => "12" );
        	
        	  
            return $m[3] . "-" . self::$_month_map[$m[2]] . "-" . $m[1] . $m[4];

        }

        return $date;
    }
    	
	
	private function _MapType( $IIApiType ) {

		/*
		 * This function attempts to map the type received back from 
		 * ingres_field_type into a more generic type.
		 * 
		 * The values used here come from Appendix B of the Ingres 
		 * OpenAPI User Guide, which describes datatypes. The values
		 * mapped to are a bit of a "best guess". Note that to be strict
		 * it should probably make use of length ( which comes from 
		 * ingres_field_length ) but hoping that isn't necessary
		 * 
		 * Note that it doesn't do anything for these types:
         *    IIAPI_INTDS_TYPE  
         *    IIAPI_INTYM_TYPE  
         *    IIAPI_LOGKEY_TYPE    
         *    IIAPI_TABKEY_TYPE  
         *    IIAPI_HNDL_TYPE
         *
         * because they are  very unlikely to be returned, and I was unsure
         * what to do with them
         * 
         * Brian 18/12/2009 
		 */
		switch( $IIApiType ) {
			case "IIAPI_LBYTE_TYPE":  return "byte";
			case "IIAPI_BYTE_TYPE":   return "byte";
			case "IIAPI_CHA_TYPE":    return "char";
			case "IIAPI_CHR_TYPE":    return "char";
			case "IIAPI_DATE_TYPE":   return "datetime";
			case "IIAPI_DTE_TYPE":    return "datetime";
			case "IIAPI_TIME_TYP":    return "datetime";
			case "IIAPI_TMWO_TYP":    return "datetime";
			case "IIAPI_TMTZ_TYP":    return "datetime";
			case "IIAPI_DEC_TYPE":    return "decimal";
			case "IIAPI_FLT_TYPE":    return "float";
			case "IIAPI_INT_TYPE":    return "integer";
			case "IIAPI_MNY_TYPE ":   return "money";
			case "IIAPI_NCHA_TYPE":   return "nchar";
			case "IIAPI_NVCH_TYPE":   return "nvarchar";
			case "IIAPI_LNVCH_TYPE":  return "text";
			case "IIAPI_LVCH_TYPE":   return "text";
			case "IIAPI_LTXT_TYPE":   return "text";
			case "IIAPI_TSWO_TYP":    return "timestamp";
			case "IIAPI_TSTZ_TYP":    return "timestamp";
			case "IIAPI_TS_TYP":      return "timestamp";
			case "IIAPI_TXT_TYPE":    return "text";
			case "IIAPI_VBYTE_TYP":   return "varbyte";
			case "IIAPI_VCH_TYPE":    return "varchar";
			default:
					return $IIApiType;
		}
	}	
	
	
	
	
	function& FieldTypesArray()
	{
		if ( is_null( $this->__field_types_array)) {

			$start = 1*ini_get("ingres.array_index_start");
			$this->__field_types_array = array();
            $this->__date_fields_to_map = array();
			
			for ($i=$start, $end=$this->_numOfFields + $start; $i < $end; $i++)
			{ 
				$o = new ADOFieldObject();
				$o->name = ingres_field_name( $this->_queryID, $i );
				$o->max_length = ingres_field_length( $this->_queryID, $i );
				$o->type = $this->_MapType(ingres_field_type( $this->_queryID, $i ));
				$this->__field_types_array[] = $o;
				
                if ( $o->type == "datetime" ) {

                	if ( $this->numberedFields )
                        $this->__date_fields_to_map[] = $i;
                    if ( $this->namedFields )
                        $this->__date_fields_to_map[] = $o->name;
                }
			}
		}
		return $this->__field_types_array;
	}
	var $__field_types_array = null;
	
	function &FetchField($fieldOffset = -1) 
	{
		//TODO: Test
		if ( $fieldOffset < 0 || $fieldOffset >= $this->_numOfFields ) {
			return NULL; // TODO: OK?	
		} else {
			$arr =& $this->FieldTypesArray();
			return $arr[$fieldOffset];
		}
	}

	function &GetRowAssoc($upper=true)
	{
		//TODO: test
		if ($this->fetchMode == INGRES_ASSOC && !$upper) {
			$row = $this->fields;
		} else {
			$row =& ADORecordSet::GetRowAssoc($upper);
		}
		return $row;
	}
	
	/* Use associative array to get fields array */
	function Fields($colname)
	{	
		//TODO: Test

		// NOTE - mysql version had both of these with '@' operators before hand - have
		// removed these, pending.....
		
		if ($this->fetchMode != INGRES_NUM) {
			if ( array_key_exists( $colname, $this->fields) ) {
				return $this->fields[$colname];
			} else {
				return NULL;
			}
		}
		
		if (!$this->bind) {
			$this->bind = array();
			for ($i=0; $i < $this->_numOfFields; $i++) {
				$o = $this->FetchField($i);
				$this->bind[strtoupper($o->name)] = $i;
			}
		}
		 return $this->fields[$this->bind[strtoupper($colname)]];
	}
	
	function _seek($row)
	{
		if ( $row < 0 || $row >= $this->_numOfRows) 
			Error( "Error in _seek - $row out of range");
			
		$this->fields = $this->_selectedRecords[$row];
		$this->_currRow = $row;
	}
	
	/*function MoveNext()
	{
		return $this->_fetch();
	}*/
	
	function _fetch()
	{
		if ( $this->_currRow >= $this->_numOfRows ) {
			return false;
		} else {
			
			$this->_currRow += 1;
			if ( $this->_currRow >= $this->_numOfRows ) {
				
				$this->EOF = true;
				$this->fields = NULL;
				return false;
				
			} else {
				
				$this->_seek( $this->_currRow );
				return true;
			}
		}
	}
	
	function _close() {
		
		// ingres doesn't have anything to tidy up in this context, so do nothing....
		$this->_queryID = false;
		unset( $this->_selectedRecords );	
	}
	
	function MetaType($t,$len=-1,$fieldobj=false)
	{
		//TODO: Consider
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}
		
		$len = -1; // ingres max_length is not accurate
		switch (strtoupper($t))
		{
			case 'C':
			case 'CHAR':
			case 'TEXT':
			case 'VARCHAR': 
			
			case 'STRING': 
			case 'TINYBLOB': 
			case 'TINYTEXT': 
			case 'ENUM': 
			case 'SET': 
				if ($len <= $this->blobSize) return 'C';
			
		case 'TEXT':
		case 'LONGTEXT': 
		case 'MEDIUMTEXT':
			return 'X';
			
		// php_ingres extension always returns 'blob' even if 'text'
		// so we have to check whether binary...
		case 'IMAGE':
		case 'LONGBLOB': 
		case 'BLOB':
		case 'MEDIUMBLOB':
			return !empty($fieldobj->binary) ? 'B' : 'X';
			
		case 'YEAR':
		case 'DATE': return 'D';
		
		case 'TIME':
		case 'DATETIME':
		case 'TIMESTAMP': return 'T';
		
		case 'INT': 
		case 'INTEGER':
		case 'BIGINT':
		case 'TINYINT':
		case 'MEDIUMINT':
		case 'SMALLINT': 
			
			if (!empty($fieldobj->primary_key)) return 'R';
			else return 'I';
		
		default: return 'N';
		}
	}

}

class ADORecordSet_ext_ingres extends ADORecordSet_ingres {	

	function ADORecordSet_ext_ingres($queryID,$mode=false) 
	{
		$this->ADORecordSet_ingres( $queryID, $mode );
	}
	
	function MoveNext()
	{
		//See note about extension in constructor of of ADODB_ingres
		//Untested - don't know if this will work......
		//TODO: Test with extension installed .....
		return adodb_movenext($this);
	}
}


}
?>
