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
	<body>
		<div>
			<h1 id="run_description">
				<?php
					$header = "";
					if (isset($_GET["branch"])) {
						$header = "Anvil results for branch [" . $_GET["branch"] . "]";
					} else {
						$header = "Anvil reporting";
					}
					echo $header . "\n";
				?>
			</h1>
		</div>
		<div>
			<div id="branches_container">
			<?php
				if (isset($_GET["branch"])) {
					echo "<a href=\"performance.php?branch=" . $_GET["branch"] . "\">Performance</a>\n";

				} else {
					?>
							<div id="branches_title"><b>Branches</b></div>
							<div id="branch_list_container">
								<!-- START GENERATED BRANCH LIST -->
									<?php
										$query="SELECT DISTINCT branch FROM runs;";
										$result=mysql_query($query);

										while($row = mysql_fetch_array($result)) {
											echo "<a href=\"runs.php?branch=" . $row["branch"] . "\">" . $row["branch"] . "</a><br>\n";
										}
									?>
								<!-- END GENERATED BRANCH LIST -->
							</div>
					<?php
				}
			?>
			</div>
			<div id="reports_container">
				<?php
					if (isset($_GET["branch"])) {
						echo "<a id=\"next_batch_link\" href=\"\">Next set</a>\n";
					}

					if (!(isset($_GET["branch"]))) {
				?>
				<!-- START GENERATED HUB STATE -->
				<div id="hub_state_title">
					<b>
					<?php
							exec("ps aux | grep -i hub.js | grep -v grep", $pids);
							if (count($pids) > 0) {
								echo "<div id=\"hub_state_running\">Hub is Running</div>";

							} else {
								echo "<div id=\"hub_state_dead\">Hub is not running</div>";
							}
					?>
					</b>
				</div>
				<!-- END GENERATED HUB STATE -->
				<?php } ?>
				<?php
				if (!(isset($_GET["branch"]))) {
				?>
				<!-- START GENERATED DRIVER STATES -->
				<div id="driver_state_container">
					<div id="driver_state_title"><b>Driver states</b></div>
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
						<div id="complete_result_title"><b>3_2_X Run Comparison</b></div>
							<?php writeTable("3_2_X") ?>
					</div>

					<div id=\"complete_result_container\">
						<div id="complete_result_title"><b>Master Run Comparison</b></div>
						<?php writeTable("master") ?>
					</div>
					<!-- END COMPLETE RESULT COMPARISON -->
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
							<div>
								<div id="run_summary_element"><b>Date: </b></div>
								<div id="chart<?php echo $row["id"] ?>Date"></div>
							</div>
							<div>
								<div id="run_summary_element"><b>Branch: </b></div>
								<div id="chart<?php echo $row["id"] ?>Branch"></div>
							</div>
							<div>
								<div id="run_summary_element\"><b>Git Hash: </b></div>
								<div id="chart<?php echo $row["id"] ?>Githash"></div>
							</div>

							<div id="chart<?php echo $row["id"] ?>Contents" style="margin-top: 5px"></div>
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
				return "<FONT COLOR=\"#336600\">". $value . "</FONT>";
			}

			function fail($value) {
				return "<FONT COLOR=\"#660000\">". $value . "</FONT>";
			}

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

</body>
</html>
