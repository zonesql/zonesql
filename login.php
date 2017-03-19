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

// If config requires no authentication, login form shouldn't be accessed.
if(getFromArray($cfg, 'authentication') == 'none') 
	header('Location: ../');

if(getFromArray($_GET, 'action') == 'logout') {
	session_destroy();
	header('Location: ../login/');
}

$isPost = isset($_POST['login']);
$user = trim(getFromArray($_POST, 'username'));
$pass = trim(getFromArray($_POST, 'password'));
$cfgUser = getFromArray($cfg, 'username');
$cfgPass = getFromArray($cfg, 'password');

$message = "";
if($isPost) {
	if($user && $pass && $cfgUser && $cfgPass && ($user===$cfgUser) && ($pass===$cfgPass)) {
		$_SESSION['user_authenticated'] = true;
		header('Location: ../');	
	} else {
		$message = '<div class="error">Invalid login credentials.</div>';
	}
}	

$baseAssetsPath = $cfg['environment'] == 'development' ? "src" : "dist";

?><!DOCTYPE html>
<html>
<head>
	<title>ZoneSQL - Login</title>
	<link rel="stylesheet" type="text/css" href="../<?= $baseAssetsPath ?>/zonesql/login.css"/>
	<link rel="icon" href="../img/favicon.png">
</head>
<body>
	
<div class="login">
	<h2>Sign in to ZoneSQL</h2>
	<?= $message; ?>
	<form action="" method="post">
		<div class="input-group input-group-lg">
			<span class="input-group-addon"><i class="fa fa-user"></i></span>
			<input type="text" name="username" class="form-control" placeholder="Username" autofocus="autofocus">
		</div>

		<div class="input-group input-group-lg">
			<span class="input-group-addon"><i class="fa fa-lock"></i></span>
			<input type="password" name="password" class="form-control" placeholder="Password">
		</div>

		<button type="submit" name="login" >Login</button>
	</form>
</div>	
</body>
</html>