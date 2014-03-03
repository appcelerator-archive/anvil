<?php
	require "common.php";
	db_open();

	function pass($value) {
		return "<FONT COLOR=\"#336600\">". $value . "</FONT>";
	}

	function fail($value) {
		return "<FONT COLOR=\"#660000\">". $value . "</FONT>";
	}

	function writeTable($branch, $history) { ?>
		<table class="table1">
			<thead>
				<tr>
					<th>Git Hash</th><th>Date</th><th>iOS 6.0</th><th>iOS 6.1</th><th>iOS 7.0.3</th><th>Android 2.3.6</th><th>Android 3.1</th><th>Android 4.2</th><th>Build Name</th>
				</tr>
			</thead>
			<tbody>
			<?php

				if(!isset($history)) {
					$history = 5;
				}
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
										WHERE driver_runs.run_id = A.id AND A.branch = '".$branch."'
										GROUP BY run_id ORDER BY run_id  DESC LIMIT ".$history." ;";
					$result=mysql_query($query);

					$alt = 0;

					while($row = mysql_fetch_array($result)) {
									if($alt == 1) {
													echo "\t\t<tr class=\"alt\">\n";
													$alt = 0;
									} else {
													echo "\t\t<tr>\n";
													$alt = 1;
									}
									$queryUrl = "http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id];
									echo "\t\t\t<td><a href='http://www.github.com/appcelerator/titanium_mobile/commit/".$row[git_hash]. "'>" . substr($row["git_hash"],0,10) . "</ a></td><td>" .  date("n/j/y g:i A", $row["timestamp"]) . "</td>\n";
									echo "\t\t\t<td><a href='".$queryUrl."&driver_id=ios1'>". pass($row["i5.0P"]) . "/" . fail($row["i5.0F"]) . "</a></td>\n";
									echo "\t\t\t<td><a href='".$queryUrl."&driver_id=ios2'>". pass($row["i5.1P"]) . "/" . fail($row["i5.1F"]) . "</a></td>\n";
									echo "\t\t\t<td><a href='".$queryUrl."&driver_id=ios3'>". pass($row["i6.0P"]) . "/" . fail($row["i6.0F"]) . "</a></td>\n";
									echo "\t\t\t<td><a href='".$queryUrl."&driver_id=android1'>". pass($row["a2.3.6P"]) . "/" . fail($row["a2.3.6F"]) . "</a></td>\n";
									echo "\t\t\t<td><a href='".$queryUrl."&driver_id=android3'>". pass($row["a3.1P"]) . "/" . fail($row["a3.1F"]) . "</a></td>\n";
									echo "\t\t\t<td><a href='".$queryUrl."&driver_id=android2'>". pass($row["a4.2.1P"]) . "/" . fail($row["a4.2.1F"]) . "</a></td>\n";
									echo "\t\t\t<td><a href='http://builds.appcelerator.com.s3.amazonaws.com/mobile/".$row[branch]."/mobilesdk-".$row[file]."-osx.zip'>".$row[file]."</ a></ td>\n";
									echo "\t\t\t</tr>\n";
					} ?>
			</tbody>
		</table>
<?php } ?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<title>Anvil reporting</title>
		<link rel="stylesheet" type="text/css" href="style.css">
	</head>
	<body>
				<?php
				if (isset($_GET["branch"])) {	?>
					<!-- START COMPLETE RESULT COMPARISON -->
					<?php writeTable($_GET["branch"], $_GET["history"]) ?>
					<!-- END COMPLETE RESULT COMPARISON -->
				<?php } ?>
</body>
</html>
