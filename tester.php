<?php

include_once('codaserver.php');
session_start();
//unset($_SESSION['database_connection']);
// if the form is posted and no connection is present, make one
if ($_POST && (!isset($_SESSION['database_connection']) || !$_SESSION['database_connection'])) {
	if (empty($_POST['hostname']) || empty($_POST['port'])) {
		echo "Please enter a valid hostname and port.  Hit back and try again!";
		die;
	}
	$_SESSION['database_connection'] = codaserver_connect($_POST['hostname'], $_POST['port'], $_POST['username'], $_POST['password']);
	if ($_SESSION['database_connection'] === false) {
		unset($_SESSION['database_connection']);
		echo "You didn't enter a valid username and password for the desired host.  Please hit back and try again!";
		die;
	}
}
// var_dump($_SESSION);
// check to see if there is an incoming query
if (isset($_POST['coda_query'])) {	
	// try to run it
	$result = codaserver_query($_SESSION['database_connection'], $_POST['coda_query']);
	
	// if it's false, check to see if the error code is about this session timing out.  if so, reconnect and rerun.
	if ($result === false) {
		//echo 'here';
		$errors = codaserver_errors();
		if (count($errors) == 1) {
			if ($errors[0]['errorcode'] == '1005') {
				if (empty($_POST['hostname']) || empty($_POST['port'])) {
					echo "Please enter a valid hostname and port.  Hit back and try again!";
					die;
				}
				$_SESSION['database_connection'] = codaserver_connect($_POST['hostname'], $_POST['port'], $_POST['username'], $_POST['password']);
				if ($_SESSION['database_connection'] === false) {
					unset($_SESSION['database_connection']);
					echo "You didn't enter a valid username and password for the desired host.  Please hit back and try again!";
					die;
				}
				$result = codaserver_query($_SESSION['database_connection'], $_POST['coda_query']);
			}
		}
	}
}
//var_dump($result);

?>
<html>
	<head>
		<title>CodaServer Driver Tester</title>
	</head>
	<style>
		body {
			font-family: Helvetica, Arial, san-serif;
			font-size: 10pt;
		}
		#content {
			width: 600pt;
		}
		#connection-info div {
			margin: 3px;
			float: left;
		}
		table {
			font-size: 10pt;
			border-left: 2px solid black;
			border-top: 2px solid black;
			border-bottom: 1px solid black;
			border-right: 1px solid black;
			
		}
		table thead {
			background-color: #0A59AF;
			color: white;
		}
		table th {
			padding: 4px;
			border: solid 0px black;
			border-right-width: 1px;
			border-bottom-width: 1px;
		}
		table td {
			padding: 4px;
			border: solid 0px black;
			border-right-width: 1px;
			border-bottom-width: 1px;
		}
		
	</style>
	<body>
		<div id="content">
			<h1>CodaServer Driver Tester</h1>
			<p>
				You can use this page to connect to and run queries against a CodaServer instance.  It is a proof of concept and helps to let
				you know if your installation is set up correctly.
			</p>
			<form method="post">
				<fieldset>
					<legend>Connection Information</legend>
					<div id="connection-info">
						<div>
							<label for="hostname">Hostname</label><br />
							<input name="hostname" id="hostname" size="20" value="<?= $_POST['hostname'] ? $_POST['hostname'] : 'localhost' ?>" />
						</div>
						<div>
							<label for="port">Port</label><br />
							<input name="port" id="port" size="5" value="<?= $_POST ? $_POST['port'] : '3407' ?>" />
						</div>
						<div>
							<label for="username">Username</label><br />
							<input name="username" id="username" size="20" value="<?= $_POST['username'] ?>" />
						</div>
						<div>
							<label for="password">Password</label><br />
							<input type="password" name="password" id="password" size="20" value="<?= $_POST['password'] ?>" />
						</div>
					</div>
				</fieldset>
				<br />
				<fieldset>
					<legend>Coda Query/Command</legend>
					<textarea name="coda_query" rows="6" cols="95"><?= $_POST['coda_query'] ?></textarea>
					<input type="submit" style="float: right; margin-top: 5px;" value="Submit" />		
				</fieldset>
			</form>
<?php

if ($_POST && is_array($result)) {
	$fields = codaserver_fetch_fields($result);
	?>
	<h2>Results</h2>
	<table cellpadding="0" cellspacing="0">
		<thead>
			<tr>
				<?php 
					foreach ($fields as $field) {
						echo '<th>'.$field['columnname'].'</th>';
					}
				?>
			</tr>
		</thead>
		<tbody>
			<?php
				while ($row = codaserver_fetch_row($result)) {
					echo "<tr>";
					foreach($row as $column) {
						echo "<td>&nbsp;".$column."&nbsp;</td>";
					}
					echo "</tr>";
				}
			?>
		</tbody>
	</table>
<?php
} else if ($_POST && $result === true) {
?>
<h2>Success!</h2>
<?php	
} else if ($_POST) {
?>
<h2>The Following Errors Occurred</h2>
<ul>
	<?php
		$errors = codaserver_errors();
		foreach($errors as $error) {
			echo '<li><strong>ERR'.$error['errorcode'].':</strong> '.$error['errormessage'].'</li>';
		}
	?>
</ul>
<?php
}

?>