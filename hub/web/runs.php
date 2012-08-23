<?php
	require "common.php";
	db_open();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<title>Anvil reporting</title>
	</head>
	<body>
		<div>
			<h1 style="text-align: left; margin-bottom: 50px">
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
			<div style="float: left; width: 20%">
<?php
	if (isset($_GET["branch"])) {
		echo "<a href=\"performance.php?branch=" . $_GET["branch"] . "\">Performance</a>\n";

	} else {
		echo "\t\t\t\t<div style=\"font-size: large\"><b>Branches</b></div>\n";
		echo "\t\t\t\t<div style=\"margin-left: 20px;\">\n";
		echo "<!-- START GENERATED BRANCH LIST -->\n";

		$query="SELECT DISTINCT branch FROM runs;";
		$result=mysql_query($query);

		while($row = mysql_fetch_array($result)) {
			echo "<a href=\"runs.php?branch=" . $row["branch"] . "\">" . $row["branch"] . "</a><br>\n";
		}

		echo "<!-- END GENERATED BRANCH LIST -->\n";
		echo "\t\t\t\t</div>\n";
	}
?>

			</div>
			<div style="float: left; width: 50%">
				<div style="font-size: large; margin-bottom: 40px">
<?php
	if (!(isset($_GET["branch"]))) {
		echo "<!-- START GENERATED HUB STATE -->\n";
		echo "<b>";

		exec("ps aux | grep -i hub.js | grep -v grep", $pids);
		if (count($pids) > 0) {
			echo "<div style=\"color: green\">Hub is Running</div>";

		} else {
			echo "<div style=\"color: red\">Hub is not running</div>";
		}

		echo "</b>\n";
		echo "<!-- END GENERATED HUB STATE -->\n";
	}
?>
				</div>

<?php
	if (!(isset($_GET["branch"]))) {
		echo "<!-- START GENERATED DRIVER STATES -->\n";
		echo "<div style=\"margin-bottom: 80px\">\n";
		echo "\t<div style=\"font-size: large; margin-bottom: 3px\"><b>Driver states</b></div>\n";

		echo "\t<table border=\"1\" cellpadding=\"3\" style=\"width: 100%\">\n";
		echo "\t\t<tr>\n";
		echo "\t\t\t<th>ID</th><th>Description</th><th>State</th><th>Last updated</th>\n";
		echo "\t\t</tr>\n";

		$query="SELECT * FROM driver_state";
		$result=mysql_query($query);
		while($row = mysql_fetch_array($result)) {
			echo "\t\t<tr>\n";
			echo "\t\t\t<td>" . $row["id"] . "</td><td>" . $row["description"] . "</td><td bgcolor=\"";

			if ($row["state"] === "running") {
				echo "yellow\">Running: " . $row["git_hash"];

			} else {
				echo "green\">Idle";
			}
			echo "</td><td>" . date("n-j-Y g:i:s A", $row["timestamp"]) . "</td>\n";

			echo "\t\t<tr>\n";
		}

		echo "\t</table>\n";
		echo "</div>\n";
		echo "<!-- END GENERATED DRIVER STATES -->\n";
	}
?>

<?php loadJsDependencies(); ?>

<?php
	$query = "SELECT * FROM runs";
	if (isset($_GET["branch"])) {
		$query = $query . " WHERE branch = \"" . $_GET["branch"] . "\"";
	}
	$query = $query . " ORDER BY timestamp DESC";
	$result=mysql_query($query);

	# just cause we found a record for a run doesn't mean there is valid data to display
	$displayedRuns = 0;

	while($row = mysql_fetch_array($result)) {
		# you would think that ordering by ASC makes sense here to keep A-Z display but because of 
		# how the chart is rendered (bottom up) we actually want to reverse it and use DESC
		$query2="SELECT * FROM driver_runs WHERE run_id = " . $row["id"] . " ORDER BY driver_id DESC";
		$result2=mysql_query($query2);
		$numDriverRuns = mysql_num_rows($result2);

		if ($numDriverRuns > 0) {
			echo "\n<!-- START GENERATED CHART -->\n";
			echo "<div";
			if ($displayedRuns > 0) {
				echo " style=\"margin-top: 50px\"";
			}
			echo ">\n";

			echo "\t<div>\n" .
				"\t\t<div style=\"float: left; width: 100px\"><b>Date: </b></div>\n" .
				"\t\t<div id=\"chart" . $row["id"] . "Date\"></div>\n" .
				"\t</div>\n";

			echo "\t<div>\n" .
				"\t\t<div style=\"float: left; width: 100px\"><b>Branch: </b></div>\n" .
				"\t\t<div id=\"chart" . $row["id"] . "Branch\"></div>\n" .
				"\t</div>\n";

			echo "\t<div>\n" .
				"\t\t<div style=\"float: left; width: 100px\"><b>Git Hash: </b></div>\n" .
				"\t\t<div id=\"chart" . $row["id"] . "Githash\"></div>\n" .
				"\t</div>\n";

			echo "\t<div id=\"chart" . $row["id"] . "Contents\" style=\"margin-top: 5px\"></div>\n";
			echo "</div>\n";

			echo "\n<script type=\"text/javascript\">\n";
			echo "\t// build and draw chart " . $row["id"] . "\n";
			echo "\tvar driverIds" . $row["id"] . " = [];\n";
			echo "\tvar chartRows" . $row["id"] . " = [[], []];\n\n";

			$j = 1;
			while($row2 = mysql_fetch_array($result2)) {
				echo "\tdriverIds" . $row["id"] . ".push(\"" . $row2["driver_id"] . "\");\n";
				echo "\tchartRows" . $row["id"] . "[0].push([" . $row2["passed_tests"] . ", " . $j . "]);\n";
				echo "\tchartRows" . $row["id"] . "[1].push([" . $row2["failed_tests"] . ", " . $j . "]);\n";

				$j++;
			}

			echo "\n\tdrawRunCharts(\"chart" . $row["id"] . "\", \"" . $row["branch"] . "\", \"" . 
				$row["git_hash"] . "\", " . $row["timestamp"] . ", \"" . $row["id"] . 
				"\", driverIds" . $row["id"] . ", chartRows" . $row["id"] . ");\n";

			echo "</script>\n";
			echo "<!-- END GENERATED CHART -->\n";

			$displayedRuns++;

			# only limit the displayed runs if we are on the generic runs page since we want the 
			# initial page to load quickly
			if (!(isset($_GET["branch"]))) {
				# TODO hard code the limit for max displayed runs for now - make this user 
				# configurable in the future?
				if ($displayedRuns >= 20) {
					break;
				}
			}
		}
	}
?>

			</div>
		</div>
	</body>
</html>