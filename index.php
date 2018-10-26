<?php
/** 
 * ZoneSQL - Web based database SQL interface
 *
 * @author Adam Tandowski <info@zonesql.com>
 * @link http://www.zonesql.com
 * @version 1.0.0
 * @package ZoneSQL
 */

session_start();
$cfg = include('api/config.php');

checkAuthentication($cfg);

use \ZoneSQL\Conn;

/**
 * Parameters that can be passed down to the client side.
 * @var Array
 */
$zoneConfig = array(
	'auth' => $cfg['authentication'],
	'column_autosize' => $cfg['column_autosize'] 
);

// TODO - Pass this to client via $zoneConfig and have the Dialog inputs
// set via there to maintain seperation of logic/presentation.
$conn = Conn::GetConnection($cfg);

// Set up options for Database dropdown in header
$options = '';
try {
	$obj = new Conn();
	foreach($obj->GetDatabases() as $database) {
		$selected = str_replace(array('[',']'), '', $obj->database) == str_replace(array('[',']'), '', $database) 
			? ' selected="selected"' 
			: '';
		$options .= '<option value="' . $database . '"'. $selected . '>' . $database . '</option>';
	}
} catch (Exception $e) {
	error_log($e);
}

$baseAssetsPath = $cfg['environment'] == 'development' ? "src" : "dist";

if(!file_exists($baseAssetsPath . '/dojo/dojo.js')) {
	$tip = ($cfg['environment'] == 'development' ? 'Please ensure ZoneSQL dependencies are configured.' : 'Please ensure ZoneSQL dependencies are configured and dist folder has been built for production environment.');
	throw new Exception("Path not found: [" . $baseAssetsPath  . '/dojo/dojo.js]. ' . $tip);
}

?>
<!DOCTYPE html>
<html>
<head>


    <title>ZoneSQL</title>
    <link rel="stylesheet" type="text/css" href="<?= $baseAssetsPath ?>/zonesql/default.css"/>
    <!--[if IE]><link rel="shortcut icon" href="img/favicon.png"><![endif]-->    
    <link rel="icon" href="img/favicon.png">
    <script>
	dojoConfig = {
		async:true, 
		parseOnload:false
	}
	zoneConfig = <?= json_encode($zoneConfig); ?>
	</script>
	<script src="<?= $baseAssetsPath; ?>/dojo/dojo.js"></script>
	<script src="<?= $baseAssetsPath; ?>/ace/src-min-noconflict/ace.js"></script>
    <script src="<?= $baseAssetsPath; ?>/zonesql/zonesql.js"></script>
</head>
<body class="flat">
    <div id="loadingOverlay" class="loadingOveraly pageOverlay"></div>
    <!-- Top Header Panel -->
    <div data-dojo-type="dijit/layout/ContentPane" id="header">
		<button data-dojo-type="dijit/form/Button" type="button" id="logout" class="headerButton"><img src="img/logout.svg" alt="Logout" /> Logout</button>
        <button data-dojo-type="dijit/form/Button" type="button" id="settings" class="headerButton" onClick="settingsDialog.show();"><img src="img/settings.svg" alt="Settings" /> Settings</button>
        <button data-dojo-type="dijit/form/Button" type="button" id="execute" class="headerButton"><img src="img/play.svg" alt="Execute Query" /> Execute</button>
		<?php if($options) { ?>
		<select data-dojo-type="dijit/form/Select" id="database-header" class="headerDropdown" >
			<?= $options; ?>
		</select>	
		<?php } ?>
        <h1><img src="img/zonesql.png" id="logo"  alt="ZoneSQL - web based sql interface" title="ZoneSQL - web based sql interface" style="width:147pxheight:40px;" /></h1>
    </div>
    <div data-dojo-type="dijit/layout/BorderContainer" data-dojo-props="gutters:true, liveSplitters:false" id="bodyContainer">
        <!-- Left Tree Panel -->
        <div data-dojo-type="dijit/layout/ContentPane" data-dojo-props="minSize:20, region:'leading', splitter:true" style="width: 300px;" id="explorer" class="claro">
            <div id="tree"></div>
        </div>
        <!-- Right Container (Holding SQL Panel + Results Panels) -->
        <div data-dojo-type="dijit/layout/BorderContainer" data-dojo-props="region:'center', gutters:true" style="padding:0;">

            <!-- SQL Panel -->
            <div data-dojo-type="dijit/layout/ContentPane" data-dojo-props="region:'center', splitter:true" id="sqlContainer"> 
                <div id="sql"></div>  				
            </div>           

            <!-- Results Panel  -->
            <div data-dojo-type="dijit/layout/ContentPane" id="results" data-dojo-props="region:'bottom', splitter:true">
				<div id="grid" class="resultsGrid resultsGridHidden"></div>
				<div id="status" class="statusBar">
					<div id="statusRows"></div>
					<div id="statusTime"></div>
					<?php /*
					<div id="statusDatabase"></div>
					<div id="statusUser"></div>
					<div id="statusHost"></div>
					 */ ?>
					<div id="statusMessage"></div>
				</div>
            </div>
        </div>
    </div>
    <div data-dojo-type="dijit/Dialog" data-dojo-id="settingsDialog" title="Settings" id="settingsDialog">
		<form data-dojo-type="dijit/form/Form" data-dojo-id="settingsForm" method="POST" action="api/connection">
			<div class="dijitDialogPaneContentArea">
				<div class="field">
					<label for="type">Database Type:</label>
					<select data-dojo-type="dijit/form/Select" name="type" id="type">
						<option value="mssql" <?php if(getFromArray($conn, 'type') == 'mssql') echo 'selected="selected"'; ?>>Microsoft SQL Server</option>
						<option value="mysql" <?php if(getFromArray($conn, 'type') == 'mysql') echo 'selected="selected"'; ?>>MySQL</option>
						<option value="sqlite3" <?php if(getFromArray($conn, 'type') == 'sqlite3') echo 'selected="selected"'; ?>>SQLite</option>
					</select>
				</div>
				<div class="field">
					<label for="host">Host:</label>
					<input data-dojo-type="dijit/form/TextBox" name="host" id="host" value="<?= getFromArray($conn, 'host'); ?>">
				</div>
				<div class="field">
					<label for="username">Username:</label>
					<input data-dojo-type="dijit/form/TextBox" name="username" id="username" value="<?= getFromArray($conn, 'username'); ?>">
				</div>
				<div class="field">
					<label for="password">Password:</label>
					<input data-dojo-type="dijit/form/ValidationTextBox" type="password" name="password" id="password" value="<?= getFromArray($conn, 'password'); ?>">
				</div>
				<div class="field">
					<label for="database">Database:</label>
					<input data-dojo-type="dijit/form/TextBox" name="database" id="database" value="<?= getFromArray($conn, 'database'); ?>">
				</div>
				<div class="field">
					<label for="port">Port:</label>
					<input data-dojo-type="dijit/form/TextBox" name="port" id="port" value="<?= getFromArray($conn, 'port'); ?>">
				</div>
			</div>
			<div class="dijitDialogPaneActionBar">
				<button data-dojo-type="dijit/form/Button" type="submit" id="connect" class="alt-primary">Connect</button>
				<button data-dojo-type="dijit/form/Button" type="button" data-dojo-props="onClick:function(){settingsDialog.hide();}" id="cancel">Cancel</button>
			</div>
		</form>
    </div>
</body>
</html>