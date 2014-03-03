<?php
	require "common.php";
	db_open();
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<title>Anvil reporting</title>
		<link rel="stylesheet" type="text/css" href="style.css">
	</head>
	<body style="background-color:#000">
				<?php writeTable($_GET["branch"], $_GET["history"]) ?>
</body>
</html>
