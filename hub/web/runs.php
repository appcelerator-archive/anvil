<?php
	require "common.php";
	db_open();

	$branch = htmlspecialchars($_GET["branch"]);
	$last_run_id = $_GET["last_run_id"];
	$branch_specified = is_null($branch) ? false : true;

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
					if ($branch_specified) { ?>
						<h2><?php echo $branch ?> Branch</h2>
						<a href="runs.php?branch=<?php echo $branch ?>">Runs</a> | <a href="performance.php?branch=<?php echo $branch ?>">Performance</a> | <a id="next_batch_link" href="\">Next Set</a>
					<?php }

					if (!$branch_specified) {
					?>
						<!-- START GENERATED DRIVER STATES -->
						<div id="driver_state_container">
							<h4>Driver States</h4>
								<?php writeDriverStates() ?>
						</div>
						<!-- END GENERATED DRIVER STATES -->

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
					<?php } elseif (is_null($last_run_id)) { ?>
						<div id="complete_result_container">
							<h4>Queue</h4>
							<?php writeQueue($branch) ?>
						</div>
					<?php }

					loadJsDependencies();

					$query = "SELECT * FROM runs";
					if ($branch_specified) {
						$query = $query . " WHERE branch = \"" . $branch . "\"";
					}

					if (!is_null($last_run_id)) {
						$query = $query . " AND id < " . $last_run_id;
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
					if ($branch_specified) { ?>
					 <script>
						var nextBatchLink = document.getElementById("next_batch_link");
						nextBatchLink.href = "runs.php?branch=<?php echo $branch ?>&last_run_id=<?php echo $last_run_id ?>";
				   </script>
					<?php	} ?>
				</div>
			</div>
	  </div>
</body>
</html>
