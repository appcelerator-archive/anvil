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

	function loadJsDependencies()
	{
		echo "<!-- START CHART DEPENDENCIES -->\n";
		echo "<script src=\"jqplot/jquery.min.js\"></script>\n";
		echo "<script src=\"jqplot/jquery.jqplot.min.js\"></script>\n";
		echo "<script src=\"jqplot/plugins/jqplot.barRenderer.min.js\"></script>\n";
		echo "<script src=\"jqplot/plugins/jqplot.categoryAxisRenderer.min.js\"></script>\n";
		echo "<script src=\"jqplot/plugins/jqplot.canvasTextRenderer.min.js\"></script>\n";
		echo "<script src=\"jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js\"></script>\n";
		echo "<script src=\"jqplot/plugins/jqplot.pointLabels.min.js\"></script>\n";
		echo "<script src=\"common.js\"></script>\n\n";

		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"jqplot/jquery.jqplot.css\" />\n";
		echo "<!-- END CHART DEPENDENCIES -->\n\n";
	}

	function status($pass, $fail) {
		if($fail > 0) {
			return "fail";
		} elseif ($pass == 0) {
			return "idle";
		} else {
			return "pass";
		}
	}

	function driverStatus($state, $timestamp) {
		if ($state === "running") {
			if ($timestamp < time() - (20 * 60)) {
				return "<span style='color:maroon;font-size:150%'>&bull;</span>";
			} else {
				return "<span style='color:orange;font-size:150%'>&bull;</span>";
			}
		} else {
			return "<span style='color:green;font-size:150%'>&bull;</span>";
		}
	}

	function generateNavigation() {
		?>
		<!-- START GENERATED BRANCH LIST -->
		<div class="well" id="branches-well">
			<ul class="nav nav-list">
			<li class="nav-header">Branches</li>
				<?php
					$query="SELECT DISTINCT branch FROM runs ORDER BY branch DESC;";
					$result=mysql_query($query);

					while($row = mysql_fetch_array($result)) {
						echo '<li class="nav-branch ' . ($_GET["branch"] == $row["branch"] ? "active" : "") . '" id="branch_master"><a href="runs.php?branch=' . $row["branch"] . "\">" . $row["branch"] . '</a></li>';
					}
				?>
			</ul>

			<ul class="nav nav-list">
			<li class="nav-header">Hub Status:
				<?php
						exec("ps aux | grep -i hub.js | grep -v grep", $pids);
						if (count($pids) > 0) {
							echo '<span id="hub_state_running">Running</span>';

						} else {
							echo '<span id="hub_state_dead">Stopped</span>';
						}
				?>
			</li>
			<?php
				$query="SELECT * FROM driver_state";
				$result=mysql_query($query);
				while($row = mysql_fetch_array($result)) {
					echo '<li class="nav-branch ">' . $row["id"] . ' (' . $row["description"] .') ' . driverStatus($row["state"], $row["timestamp"]) . '</li>';
				} ?>
			</ul>
		</div>
		<!-- END GENERATED BRANCH LIST -->
		<?php
	}

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
					$query = "SELECT id, branch, base_sdk_filename AS 'file', git_hash, timestamp FROM runs WHERE branch = '".mysql_real_escape_string($branch)."' AND id not IN (SELECT run_id from driver_runs) ORDER BY TIMESTAMP DESC";
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

	function writeDriverStates() {
		?>
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
	<?php }

	function writeTable($branch, $rows) {

		if(!isset($rows)) {
				$rows = 6;
		}

		?>
		<table class="table1">
			<thead>
				<tr>
					<th>Git Hash</th><th>Date</th><th>iOS 6.0</th><th>iOS 6.1</th><th>iOS 7.0.3</th><th>Android 2.3.6</th><th>Android 3.1</th><th>Android 4.2</th><th>Build Name</th>
				</tr>
			</thead>
			<tbody>
			<?php

				$branch_clause = "";
				if(!is_null($branch)) {
					$branch_clause = " AND A.branch = '".mysql_real_escape_string($branch)."' ";
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
										WHERE driver_runs.run_id = A.id ".$branch_clause."
										GROUP BY run_id ORDER BY run_id  DESC LIMIT ".mysql_real_escape_string($rows).";";
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
