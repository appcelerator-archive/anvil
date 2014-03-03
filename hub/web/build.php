<?php

	$connection;

	function db_open()
	{
		$username="root";
		$database="anvil_hub";

		$connection = mysql_pconnect("localhost", $username);
		if (!$connection) {
			die('Could not connect: ' . mysql_error());
		}

		@mysql_select_db($database) or die( "Unable to select database");
	}

	db_open();

	function status($pass, $fail) {
		if($fail > 0) {
			return "fail";
		} elseif ($pass == 0) {
			return "idle";
		} else {
			return "pass";
		}
	}

	function writeTableShort($git_hash) { ?>
		<table class="table1">
			<tbody>
			<?php
				$query = "SELECT run_id, A.branch AS 'branch', A.base_sdk_filename AS 'file' ,A.git_hash AS 'git_hash', A.timestamp AS 'timestamp',
										SUM(if(driver_id = 'android1', passed_tests, 0)) AS 'a2.3.6P' ,
										SUM(if(driver_id = 'android1', failed_tests, 0)) AS 'a2.3.6F' ,
										SUM(if(driver_id = 'android2', passed_tests, 0)) AS 'a4.2.1P' ,
										SUM(if(driver_id = 'android2', failed_tests, 0)) AS 'a4.2.1F' ,
										SUM(if(driver_id = 'android3', passed_tests, 0)) AS 'a3.1P' ,
										SUM(if(driver_id = 'android3', failed_tests, 0)) AS 'a3.1F' ,
										SUM(if(driver_id = 'ios1', passed_tests, 0)) AS 'i5.0P' ,
										SUM(if(driver_id = 'ios1', failed_tests, 0)) AS 'i5.0F' ,
										SUM(if(driver_id = 'ios2', passed_tests, 0)) AS 'i5.1P' ,
										SUM(if(driver_id = 'ios2', failed_tests, 0)) AS 'i5.1F' ,
										SUM(if(driver_id = 'ios4', passed_tests, 0)) AS 'i6.0P' ,
										SUM(if(driver_id = 'ios4', failed_tests, 0)) AS 'i6.0F'
										FROM driver_runs
										LEFT JOIN (SELECT * FROM runs) AS A ON driver_runs.run_id = A.id
										WHERE driver_runs.run_id = A.id AND A.git_hash = '".$git_hash."';";
					$result=mysql_query($query);
					while($row = mysql_fetch_array($result)) {
									$queryUrl = "http://anvil.appcelerator.com/results.php?branch=" . $row[branch] . "&git_hash=" . $row[git_hash] . "&run_id=" . $row[run_id];
									echo '<tr><td class="' . status($row["i5.0P"], $row["i5.0F"]) . '"><a href="' . $queryUrl . '&driver_id=ios1" target="_blank">iOS 6.0</a></td>';
									echo '<td class="' . status($row["i5.1P"], $row["i5.1F"]) . '"><a href="' . $queryUrl . '&driver_id=ios2" target="_blank">iOS 6.1</a></td>';
									echo '<td class="' . status($row["i6.0P"], $row["i6.0F"]) . '"><a href="' . $queryUrl . '&driver_id=ios3" target="_blank">iOS 7.0.3</a></td>';
									echo '<td class="' . status($row["a2.3.6P"], $row["a2.3.6F"]) . '"><a href="' . $queryUrl . '&driver_id=android1" target="_blank">Android 2.3.6</a></td>';
									echo '<td class="' . status($row["a3.1P"], $row["a3.1F"]) . '"><a href="' . $queryUrl . '&driver_id=android3" target="_blank">Android 3.1</a></td>';
									echo '<td class="' . status($row["a4.2.1P"], $row["a4.2.1F"]) . '"><a href="' . $queryUrl . '&driver_id=android2" target="_blank">Android 4.2</a></td>';
									echo '</tr>';
					}
			?>
			</tbody>
		</table>
<?php }

header('Cache-Control: max-age=259200');

?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<title>Anvil reporting</title>
		<style>

			html, body{ margin:0; padding:0; }

			table {
				width:100%;
				height:20px;
				border-collapse:collapse;
				table-layout:fixed;
			}

			table td {
				padding: 5px;
				text-align:center;
			}

			body {
				font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;
				font-size:10px;
				color:#333;
			}

			.pass {
				background-color: green;
			}

			.idle {
				background-color: #777;
			}

			.fail {
				background-color: maroon;
			}

			a {
					color:white;
					text-decoration: none;
			}

			a:hover { text-decoration: underline; }

		</style>
	</head>
	<body>
				<?php
				if (isset($_GET["git_hash"])) {	?>
					<!-- START COMPLETE RESULT COMPARISON -->
					<?php writeTableShort($_GET["git_hash"]) ?>
					<!-- END COMPLETE RESULT COMPARISON -->
				<?php } ?>
  </body>
</html>
