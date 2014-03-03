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
				return "<span style='color:maroon'>&bull;</span>";
			} else {
				return "<span style='color:yellow'>&bull;</span>";
			}
		} else {
			return "<span style='color:green'>&bull;</span>";
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
?>
