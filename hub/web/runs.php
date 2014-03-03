<?php
	require "common.php";
	db_open();
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<title>Appcelerator Anvil</title>
		<link rel="stylesheet" type="text/css" href="style.css">
	</head>
	<body>
		<header>
			<div class="container">
				<div class="row">
					<div class="span11 offset1">
						<a href="/">
						<img src="img/appc.png">
						<h1>Anvil</h1>
						</a>
					</div>
				</div>
			</div>
		</header>
		<div class="container" id="content">
			<div class="span3 offset1">
				<?php generateNavigation() ?>
			</div>
				<div class="span12" id="reports_container">
					<?php
					if (isset($_GET["branch"])) { ?>
						<h2><?php echo $_GET["branch"] ?> Branch</h2>
						<a href="runs.php?branch=<?php echo $_GET['branch'] ?>">Runs</a> | <a href="performance.php?branch=<?php echo $_GET['branch'] ?>">Performance</a> | <a id="next_batch_link" href="\">Next Set</a>
					<?php }

					if (!(isset($_GET["branch"]))) {
					?>
					<!-- START GENERATED DRIVER STATES -->
					<div id="driver_state_container">
						<h4>Driver States</h4>
						<table class="table2">
							<thead>
								<tr>
									<th>ID</th><th>Description</th><th>State</th><th>Last updated</th>
								</tr>
							</thead>
							<tbody>
									<?php
									$query="SELECT * FROM driver_state";
									$result=mysql_query($query);
									while($row = mysql_fetch_array($result)) {
										echo "\t\t<tr>\n";
										echo "\t\t\t<td>" . $row["id"] . "</td><td>" . $row["description"] . "</td><td bgcolor=\"";

										if ($row["state"] === "running") {
											if ($row["timestamp"] < time() - (20 * 60)) {
												echo "maroon\">Non responsive: " . $row["git_hash"];

											} else {
												echo "yellow\">Running: " . $row["git_hash"];
											}

										} else {
											echo "green\">Idle";
										}
										echo "</td><td>" . date("n-j-Y g:i:s A", $row["timestamp"]) . "</td>\n";

										echo "\t\t<tr>\n";
									} ?>
								<tbody>
							</table>
					</div>
					<!-- END GENERATED DRIVER STATES -->
					<?php }

					if (!(isset($_GET["branch"]))) {	?>
						<!-- START COMPLETE RESULT COMPARISON -->
						<div id="complete_result_container">
							<h4>Most Recent 3_2_X Runs</h4>
								<?php writeTable("3_2_X") ?>
						</div>

						<div id="complete_result_container">
							<h4>Most Recent Master Runs</h4>
							<?php writeTable("master") ?>
						</div>
						<!-- END COMPLETE RESULT COMPARISON -->
					<?php } elseif (!(isset($_GET["last_run_id"]))) { ?>
						<div id="complete_result_container">
							<h4>Queue</h4>
							<?php writeQueue($_GET["branch"]) ?>
						</div>
					<?php }

					loadJsDependencies();

					$query = "SELECT * FROM runs";
					if (isset($_GET["branch"])) {
						$query = $query . " WHERE branch = \"" . $_GET["branch"] . "\"";
					}

					if (isset($_GET["last_run_id"])) {
						$query = $query . " AND id < " . $_GET["last_run_id"];
					}

					$query = $query . " ORDER BY timestamp DESC";
					$result=mysql_query($query);

					$displayed_runs = 0;
					$last_run_id = 0;
					while($row = mysql_fetch_array($result)) {
						# you would think that ordering by ASC makes sense here to keep A-Z display but because of
						# how the chart is rendered (bottom up) we actually want to reverse it and use DESC
						$query2="SELECT * FROM driver_runs WHERE run_id = " . $row["id"] . " ORDER BY driver_id DESC";
						$result2=mysql_query($query2);
						$numDriverRuns = mysql_num_rows($result2);

						if ($numDriverRuns > 0) {
							?>
							<!-- START GENERATED CHART -->
							<div id="run_container">
									<h4><span id="chart<?php echo $row["id"] ?>Date"></span> <small><span id="chart<?php echo $row["id"] ?>Branch"></span> / <span id="chart<?php echo $row["id"] ?>Githash"></span></small></h4>
									<div id="chart<?php echo $row["id"] ?>Contents" style="margin-top: 5px; height: <?php echo ($numDriverRuns * 50) + 30 ?>px"></div>
							</div>
							<script>

								// build and draw chart
								var driverIds<?php echo $row["id"] ?> = [];
								var chartRows<?php echo $row["id"] ?> = [[], []];

								<?php
								$j = 1;
								while($row2 = mysql_fetch_array($result2)) { ?>
									driverIds<?php echo $row["id"] ?>.push("<?php echo $row2['driver_id'] ?>");
									chartRows<?php echo $row["id"] ?>[0].push([<?php echo $row2['passed_tests'] ?>, <?php echo $j ?>]);
									chartRows<?php echo $row["id"] ?>[1].push([<?php echo $row2['failed_tests'] ?>, <?php echo $j ?>]);
									<?php
									$j++;
								}
								echo "\n\tdrawRunCharts(\"chart" . $row["id"] . "\", \"" . $row["branch"] . "\", \"" .
									$row["git_hash"] . "\", " . $row["timestamp"] . ", \"" . $row["id"] .
									"\", driverIds" . $row["id"] . ", chartRows" . $row["id"] . ");\n";
								?>

							</script>
							<!-- END GENERATED CHART -->
							<?php
							$displayed_runs++;
						}

						$last_run_id = $row["id"];
						if ($displayed_runs >= 20) {
							break;
						}
					}
								if (isset($_GET["branch"])) { ?>
								 <script>
									var nextBatchLink = document.getElementById("next_batch_link");
									nextBatchLink.href = "runs.php?branch=<?php echo $_GET["branch"] ?>&last_run_id=<?php echo $last_run_id ?>";
							   </script>
								<?php	} ?>
							</div>
						</div>

				<?php

				function pass($value) {
					return "<FONT COLOR=\"#9DD929\">". $value . "</FONT>";
				}

				function fail($value) {
					return "<FONT COLOR=\"#CC6666\">". $value . "</FONT>";
				}

				function writeQueue($branch) { ?>
					<table class="table1">
						<thead>
							<tr>
								<th>Git Hash</th><th>Date</th><th>Build Name</th>
							</tr>
						</thead>
						<tbody>
						<?php
								$query = "SELECT id, branch, base_sdk_filename AS 'file', git_hash, timestamp FROM runs WHERE branch = '".$branch."' AND id not IN (SELECT run_id from driver_runs) ORDER BY TIMESTAMP DESC";
								$result = mysql_query($query);
								while($row = mysql_fetch_array($result)) {
												echo "\t\t<tr>\n";
												echo "\t\t\t<td><a href='http://www.github.com/appcelerator/titanium_mobile/commit/".$row[git_hash]. "'>" . substr($row["git_hash"],0,10) . "</ a></td><td>" .  date("n/j/y g:i A", $row["timestamp"]) . "</td>\n";
												echo "\t\t\t<td><a href='http://builds.appcelerator.com.s3.amazonaws.com/mobile/".$row[branch]."/mobilesdk-".$row[file]."-osx.zip'>".$row[file]."</ a></ td>\n";
												echo "\t\t\t</tr>\n";
								} ?>
						</tbody>
					</table>
				<?php }

				function writeTable($branch) { ?>
					<table class="table1">
						<thead>
							<tr>
								<th>Git Hash</th><th>Date</th><th>iOS 6.0</th><th>iOS 6.1</th><th>iOS 7.0.3</th><th>Android 2.3.6</th><th>Android 3.1</th><th>Android 4.2</th><th>Build Name</th>
							</tr>
						</thead>
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
													WHERE driver_runs.run_id = A.id AND A.branch = '".$branch."'
													GROUP BY run_id ORDER BY run_id  DESC LIMIT 5;";
								$result=mysql_query($query);

								$alt = 0;

								while($row = mysql_fetch_array($result)) {
												$queryUrl = "http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id];
												echo "\t\t<tr>\n";
												echo "\t\t\t<td><a href='http://www.github.com/appcelerator/titanium_mobile/commit/".$row[git_hash]. "'>" . substr($row["git_hash"],0,10) . "</ a></td><td>" .  date("n/j/y g:i A", $row["timestamp"]) . "</td>\n";
												echo '<td class="' . status($row["i5.0P"], $row["i5.0F"]) . '"><a href="' . $queryUrl . '&driver_id=ios1">'. pass($row["i5.0P"]) . " / " . fail($row["i5.0F"]) . '</a></td>';
												echo '<td class="' . status($row["i5.1P"], $row["i5.1F"]) . '"><a href="' . $queryUrl . '&driver_id=ios2">'. pass($row["i5.1P"]) . " / " . fail($row["i5.1F"]) . '</a></td>';
												echo '<td class="' . status($row["i6.0P"], $row["i6.0F"]) . '"><a href="' . $queryUrl . '&driver_id=ios3">'. pass($row["i6.0P"]) . " / " . fail($row["i6.0F"]) . '</a></td>';
												echo '<td class="' . status($row["a2.3.6P"], $row["a2.3.6F"]) . '"><a href="' . $queryUrl . '&driver_id=android1">'. pass($row["a2.3.6P"]) . " / " . fail($row["a2.3.6F"]) . '</a></td>';
												echo '<td class="' . status($row["a3.1P"], $row["a3.1F"]) . '"><a href="' . $queryUrl . '&driver_id=android3">'. pass($row["a3.1P"]) . " / " . fail($row["a3.1F"]) . '</a></td>';
												echo '<td class="' . status($row["a4.2.1P"], $row["a4.2.1F"]) . '"><a href="' . $queryUrl . '&driver_id=android2">'. pass($row["a4.2.1P"]) . " / " . fail($row["a4.2.1F"]) . '</a></td>';
												echo "\t\t\t<td><a href='http://builds.appcelerator.com.s3.amazonaws.com/mobile/".$row[branch]."/mobilesdk-".$row[file]."-osx.zip'>".$row[file]."</ a></ td>\n";
												echo "\t\t\t</tr>\n";
								} ?>
						</tbody>
					</table>
				<?php } ?>
	  </div>
</body>
</html>
