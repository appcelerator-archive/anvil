<?php
	require "common.php";
	db_open();
?>

<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
		<title>Anvil reporting.</title>

		<style type="text/css">
			#run_description
			{
				text-align: left;
				margin-bottom: 50px;
			}

			#branches_container
			{
				float: left;
				width: 20%;
			}

			#branches_title
			{
				font-size: large;
			}

			#branch_list_container
			{
				margin-left: 20px;
			}

			#reports_container
			{
				float: left;
				width: 50%;
			}

			#hub_state_title
			{
				font-size: large;
				margin-bottom: 40px;
			}

			#hub_state_running
			{
				color: green
			}

			#hub_state_dead
			{
				color: red
			}

			#driver_state_container
			{
				margin-bottom: 80px;
			}

			#driver_state_title
			{
				font-size: large;
				margin-bottom: 3px;
			}

			#run_container
			{
				margin-top: 50px;
			}

			#run_summary_element
			{
				float: left;
				width: 100px;
			}
			#complete_result_container
			{
				margin-top: 50px;
			}

                        table.table2{
                            font-family: "Trebuchet MS", sans-serif;
                            font-size: 12px;
                            font-weight: bold;
                            line-height: 1.4em;
                            font-style: normal;
                            border-collapse:separate;
						}
						.table2 thead th{
                            padding:15px;
                            color:#fff;
                            text-shadow:1px 1px 1px #FF0000;
                            border:1px solid #F5F5F5;
                            border-bottom:3px solid #800000;
                            background-color:#800000;
                            background:-webkit-gradient(
                            	linear,
                            	left bottom,
                            	left top,
                            	color-stop(0.02, rgb(178,34,34)),
                            	color-stop(0.51, rgb(220,20,60)),
                            	color-stop(0.87, rgb(255,0,0))
                            );
                            background: -moz-linear-gradient(
                            	center bottom,
                            	rgb(128,0,0) 2%,
                            	rgb(139,0,0) 51%,
				rgb(255,0,0) 87%
                            );
                            -webkit-border-top-left-radius:5px;
                            -webkit-border-top-right-radius:5px;
                            -moz-border-radius:5px 5px 0px 0px;
	                    border-top-left-radius:5px;
                            border-top-right-radius:5px;
                        }
				.table2 tbody td{
				padding:10px;
				text-align:center;
				border: 1px solid #800000;
				-moz-border-radius:2px;
				-webkit-border-radius:2px;
				border-radius:2px;
				color:#666;
				text-shadow:1px 1px 1px #fff;
			}

                        table.table1{
                            font-family: "Trebuchet MS", sans-serif;
                            font-size: 12px;
                            font-weight: bold;
                            line-height: 1.4em;
                            font-style: normal;
                            border-collapse:separate;
						}
						.table1 thead th{
                            padding:15px;
                            color:#fff;
                            text-shadow:1px 1px 1px #568F23;
                            border:1px solid #93CE37;
                            border-bottom:3px solid #9ED929;
                            background-color:#9DD929;
                            background:-webkit-gradient(
                            	linear,
                            	left bottom,
                            	left top,
                            	color-stop(0.02, rgb(123,192,67)),
                            	color-stop(0.51, rgb(139,198,66)),
                            	color-stop(0.87, rgb(158,217,41))
                            );
                            background: -moz-linear-gradient(
                            	center bottom,
                            	rgb(123,192,67) 2%,
                            	rgb(139,198,66) 51%,
                            	rgb(158,217,41) 87%
                            );
                            -webkit-border-top-left-radius:5px;
                            -webkit-border-top-right-radius:5px;
                            -moz-border-radius:5px 5px 0px 0px;
                            border-top-left-radius:5px;
                            border-top-right-radius:5px;
                            }
						.table1 thead th:empty{
                            background:transparent;
                            border:none;
			}


			.table1 tbody td{
				padding:10px;
				text-align:center;
				background-color:#DEF3CA;
				border: 2px solid #E7EFE0;
				-moz-border-radius:2px;
				-webkit-border-radius:2px;
				border-radius:2px;
				color:#666;
				text-shadow:1px 1px 1px #fff;
			}
			.table1 tbody tr.alt td 
			{
				color:#000000;
				background-color:white;
			}		
		</style>
	</head>
	<body>
		<div>
			<h1 id="run_description">
				<?php
					$header = "";
					if (isset($_GET["branch"])) {
						$header = "Anvil results for branch [" . $_GET["branch"] . "]";

					} else {
						$header = "Anvil reporting.";
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
		echo "\t\t\t\t<div id=\"branches_title\"><b>Branches</b></div>\n";
		echo "\t\t\t\t<div id=\"branch_list_container\">\n";
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
			<div id="reports_container">
<?php
	if (isset($_GET["branch"])) {
		echo "<a id=\"next_batch_link\" href=\"\">Next set</a>\n";
	}

	if (!(isset($_GET["branch"]))) {
		echo "<!-- START GENERATED HUB STATE -->\n";
		echo "<div id=\"hub_state_title\">\n";
		echo "<b>";

		exec("ps aux | grep -i hub.js | grep -v grep", $pids);
		if (count($pids) > 0) {
			echo "<div id=\"hub_state_running\">Hub is Running</div>";

		} else {
			echo "<div id=\"hub_state_dead\">Hub is not running</div>";
		}

		echo "</b>\n";
		echo "</div>\n";
		echo "<!-- END GENERATED HUB STATE -->\n";
	}
?>

<?php
	if (!(isset($_GET["branch"]))) {
		echo "<!-- START GENERATED DRIVER STATES -->\n";
		echo "<div id=\"driver_state_container\">\n";
		echo "\t<div id=\"driver_state_title\"><b>Driver states</b></div>\n";

		echo "\t<table class=\"table2\">\n";
		echo "\t\t<thead>\n";
		echo "\t\t<tr>\n";
		echo "\t\t\t<th>ID</th><th>Description</th><th>State</th><th>Last updated</th>\n";
		echo "\t\t</tr>\n";
		echo "\t\t<thead>\n";
		echo "\t\t<tbody>\n";
		$query="SELECT * FROM driver_state";
		$result=mysql_query($query);
		while($row = mysql_fetch_array($result)) {
			echo "\t\t<tr>\n";
			echo "\t\t\t<td>" . $row["id"] . "</td><td>" . $row["description"] . "</td><td bgcolor=\"";

			if ($row["state"] === "running") {
				if ($row["timestamp"] < time() - (20 * 60)) {
					echo "red\">Non responsive: " . $row["git_hash"];

				} else {
					echo "yellow\">Running: " . $row["git_hash"];
				}

			} else {
				echo "green\">Idle";
			}
			echo "</td><td>" . date("n-j-Y g:i:s A", $row["timestamp"]) . "</td>\n";

			echo "\t\t<tr>\n";
		}

		echo "\t\t<tbody>\n";
		echo "\t</table>\n";
		echo "</div>\n";
		echo "<!-- END GENERATED DRIVER STATES -->\n";
	}
?>

<?php
        if (!(isset($_GET["branch"]))) {
                echo "<!-- START COMPLETE RESULT COMPARISON -->\n";
                echo "<div id=\"complete_result_container\">\n";
                echo " \t<div id=\"complete_result_title\"><b>3_2_X Run Comparison</b></div>";
                
                echo "\t<table class=\"table1\">";
                echo "\t<thead>";
                echo "\t\t<tr>\n";
                echo "\t\t\t<th>Git Hash</th><th>Date</th><th>iOS 5.0</th><th>iOS 5.1</th><th>iOS 6.0</th>\n";
                echo "\t\t\t<th>Android 2.3.6</th><th>Android 3.1</th><th>Android 4.2</th>\n";
                echo "\t\t\t<th>Build Name</th>\n";  
                echo "\t\t</tr>\n";

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
                          SUM(if(driver_id = 'ios3', passed_tests, 0)) AS 'i6.0P' , 
                          SUM(if(driver_id = 'ios3', failed_tests, 0)) AS 'i6.0F' 
                          FROM driver_runs
                          LEFT JOIN (SELECT * FROM runs) AS A ON driver_runs.run_id = A.id 
                          WHERE driver_runs.run_id = A.id AND A.branch = '3_2_X'
                          GROUP BY run_id ORDER BY run_id  DESC LIMIT 10;";
                $result=mysql_query($query);
                echo "\t<thead>";
                echo "\t<tbody>";
                $alt = 0;

                while($row = mysql_fetch_array($result)) {
                        if($alt == 1) {
                                echo "\t\t<tr class=\"alt\">\n";
                                $alt = 0;
                        } else { 
                                echo "\t\t<tr>\n";
                                $alt = 1;
                        }
                        echo "\t\t\t<td><a href='http://www.github.com/appcelerator/titanium_mobile/commit/".$row[git_hash]. "'>" . substr($row["git_hash"],0,10) . "</ a></td><td>" .  date("n/j/y g:i A", $row["timestamp"]) . "</td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=ios1'><FONT COLOR=\"#336600\">". $row["i5.0P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["i5.0F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=ios2'><FONT COLOR=\"#336600\">". $row["i5.1P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["i5.1F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=ios3'><FONT COLOR=\"#336600\">". $row["i6.0P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["i6.0F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=android1'><FONT COLOR=\"#336600\">". $row["a2.3.6P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["a2.3.6F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=android3'><FONT COLOR=\"#336600\">". $row["a3.1P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["a3.1F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=android2'><FONT COLOR=\"#336600\">". $row["a4.2.1P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["a4.2.1F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://builds.appcelerator.com.s3.amazonaws.com/mobile/".$row[branch]."/mobilesdk-".$row[file]."-osx.zip'>".$row[file]."</ a></ td>\n"; 
                        echo "\t\t\t</tr>\n";
                                
                } 
                
                echo "\t</table>\n";
                echo "</div>\n";
                echo "<!-- END COMPLETE RESULT COMPARISON -->\n";
        }
        
?>

<?php
        if (!(isset($_GET["branch"]))) {
                echo "<!-- START COMPLETE RESULT COMPARISON -->\n";
                echo "<div id=\"complete_result_container\">\n";
                echo " \t<div id=\"complete_result_title\"><b>Master Run Comparison</b></div>";
                
                echo "\t<table class=\"table1\">";
                echo "\t<thead>";
                echo "\t\t<tr>\n";
                echo "\t\t\t<th>Git Hash</th><th>Date</th><th>iOS 5.0</th><th>iOS 5.1</th><th>iOS 6.0</th>\n";
                echo "\t\t\t<th>Android 2.3.6</th><th>Android 3.1</th><th>Android 4.2</th>\n";
                echo "\t\t\t<th>Build Name</th>\n";  
                echo "\t\t</tr>\n";

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
                          SUM(if(driver_id = 'ios3', passed_tests, 0)) AS 'i6.0P' , 
                          SUM(if(driver_id = 'ios3', failed_tests, 0)) AS 'i6.0F' 
                          FROM driver_runs
                          LEFT JOIN (SELECT * FROM runs) AS A ON driver_runs.run_id = A.id 
                          WHERE driver_runs.run_id = A.id AND A.branch = 'master'
                          GROUP BY run_id ORDER BY run_id  DESC LIMIT 10;";
                $query__original = "SELECT * FROM testresults2 LEFT JOIN TEST_ALL_COMMITS ON TEST_ALL_COMMITS.id = testresults2.run_id WHERE TEST_ALL_COMMITS.branch='master' GROUP BY testresults2.run_id ORDER BY TEST_ALL_COMMITS.timestamp DESC LIMIT 20";
                $result=mysql_query($query);
                echo "\t<thead>";
                echo "\t<tbody>";
                $alt = 0;

                while($row = mysql_fetch_array($result)) {
                        if($alt == 1) {
                                echo "\t\t<tr class=\"alt\">\n";
                                $alt = 0;
                        } else { 
                                echo "\t\t<tr>\n";
                                $alt = 1;
                        }
                        echo "\t\t\t<td><a href='http://www.github.com/appcelerator/titanium_mobile/commit/".$row[git_hash]. "'>" . substr($row["git_hash"],0,10) . "</ a></td><td>" .  date("n/j/y g:i A", $row["timestamp"]) . "</td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=ios1'><FONT COLOR=\"#336600\">". $row["i5.0P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["i5.0F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=ios2'><FONT COLOR=\"#336600\">". $row["i5.1P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["i5.1F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=ios3'><FONT COLOR=\"#336600\">". $row["i6.0P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["i6.0F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=android1'><FONT COLOR=\"#336600\">". $row["a2.3.6P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["a2.3.6F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=android3'><FONT COLOR=\"#336600\">". $row["a3.1P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["a3.1F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=android2'><FONT COLOR=\"#336600\">". $row["a4.2.1P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["a4.2.1F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://builds.appcelerator.com.s3.amazonaws.com/mobile/".$row[branch]."/mobilesdk-".$row[file]."-osx.zip'>".$row[file]."</ a></ td>\n"; 
                        echo "\t\t\t</tr>\n";
                                
                } 
                
                echo "\t</table>\n";
                echo "</div>\n";
                echo "<!-- END COMPLETE RESULT COMPARISON -->\n";
        }
        
?>

<?php
	if (!(isset($_GET["branch"]))) {
		echo "<!-- START COMPLETE RESULT COMPARISON -->\n";
		echo "<div id=\"complete_result_container\">\n";
		echo " \t<div id=\"complete_result_title\"><b>3_0_X Run Comparison</b></div>";
		
		echo "\t<table class=\"table1\">";
                echo "\t<thead>";
		echo "\t\t<tr>\n";
		echo "\t\t\t<th>Git Hash</th><th>Date</th><th>iOS 5.0</th><th>iOS 5.1</th><th>iOS 6.0</th>\n";
		echo "\t\t\t<th>Android 2.3.6</th><th>Android 3.1</th><th>Android 4.2</th>\n";
		echo "\t\t\t<th>Build Name</th>\n";
		echo "\t\t</tr>\n";
                echo "\t<thead>";
		echo "\t<tbody>";
		$query = "SELECT run_id , A.branch AS 'branch', A.base_sdk_filename AS 'file' , A.git_hash AS 'git_hash', A.timestamp AS 'timestamp',
                          SUM(if(driver_id = 'android1', failed_tests, 0)) AS 'a2.3.6F' ,
                          SUM(if(driver_id = 'android2', passed_tests, 0)) AS 'a4.2.1P' , 
                          SUM(if(driver_id = 'android2', failed_tests, 0)) AS 'a4.2.1F' ,
                          SUM(if(driver_id = 'android3', passed_tests, 0)) AS 'a3.1P' , 
                          SUM(if(driver_id = 'android3', failed_tests, 0)) AS 'a3.1F' ,
                          SUM(if(driver_id = 'ios1', passed_tests, 0)) AS 'i5.0P' , 
                          SUM(if(driver_id = 'ios1', failed_tests, 0)) AS 'i5.0F' ,
                          SUM(if(driver_id = 'ios2', passed_tests, 0)) AS 'i5.1P' , 
                          SUM(if(driver_id = 'ios2', failed_tests, 0)) AS 'i5.1F' ,
                          SUM(if(driver_id = 'ios3', passed_tests, 0)) AS 'i6.0P' , 
                          SUM(if(driver_id = 'ios3', failed_tests, 0)) AS 'i6.0F' 
                          FROM driver_runs
                          LEFT JOIN (SELECT * FROM runs) AS A ON driver_runs.run_id = A.id 
                          WHERE driver_runs.run_id = A.id AND A.branch = '3_0_X'
                          GROUP BY run_id ORDER BY run_id  DESC LIMIT 10;";
		$query_original = "SELECT * FROM testresults2 LEFT JOIN TEST_ALL_COMMITS ON TEST_ALL_COMMITS.id = testresults2.run_id WHERE TEST_ALL_COMMITS.branch='3_0_X' GROUP BY testresults2.run_id ORDER BY TEST_ALL_COMMITS.timestamp DESC LIMIT 20";
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
			echo "\t\t\t<td><a href='http://www.github.com/appcelerator/titanium_mobile/commit/".$row[git_hash]. "'>" . substr($row["git_hash"],0,10) . "</ a></td><td>" .  date("n/j/y g:i A", $row["timestamp"]) . "</td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=ios1'><FONT COLOR=\"#336600\">". $row["i5.0P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["i5.0F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=ios2'><FONT COLOR=\"#336600\">". $row["i5.1P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["i5.1F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=ios3'><FONT COLOR=\"#336600\">". $row["i6.0P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["i6.0F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=android1'><FONT COLOR=\"#336600\">". $row["a2.3.6P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["a2.3.6F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=android3'><FONT COLOR=\"#336600\">". $row["a3.1P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["a3.1F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://anvil.appcelerator.com/results.php?branch=". $row[branch]."&git_hash=".$row[git_hash]."&run_id=".$row[run_id]."&driver_id=android2'><FONT COLOR=\"#336600\">". $row["a4.2.1P"] . "</FONT>/<FONT COLOR=\"#660000\">" . $row["a4.2.1F"] . "</FONT></ a></td>\n";
                        echo "\t\t\t<td><a href='http://builds.appcelerator.com.s3.amazonaws.com/mobile/".$row[branch]."/mobilesdk-".$row[file]."-osx.zip'>".$row[file]."</ a></ td>\n";
			echo "\t\t\t</tr>\n";

				
		} 
		echo "\t<tbody>";
		echo "\t</table>\n";
		echo "</div>\n";
		echo "<!-- END COMPLETE RESULT COMPARISON -->\n";
	}
	
?>

<?php loadJsDependencies(); ?>

<?php
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
			echo "\n<!-- START GENERATED CHART -->\n";
			echo "<div id=\"run_container\">\n";

			echo "\t<div>\n" .
				"\t\t<div id=\"run_summary_element\"><b>Date: </b></div>\n" .
				"\t\t<div id=\"chart" . $row["id"] . "Date\"></div>\n" .
				"\t</div>\n";

			echo "\t<div>\n" .
				"\t\t<div id=\"run_summary_element\"><b>Branch: </b></div>\n" .
				"\t\t<div id=\"chart" . $row["id"] . "Branch\"></div>\n" .
				"\t</div>\n";

			echo "\t<div>\n" .
				"\t\t<div id=\"run_summary_element\"><b>Git Hash: </b></div>\n" .
				"\t\t<div id=\"chart" . $row["id"] . "Githash\"></div>\n" .
				"\t</div>\n";

			echo "\t<div id=\"chart" . $row["id"] . "Contents\" style=\"margin-top: 5px\"></div>\n";
			echo "</div>\n";

			echo "\n<script>\n";
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

			$displayed_runs++;
		}

		$last_run_id = $row["id"];
		if ($displayed_runs >= 20) {
			break;
		}
	}

	if (isset($_GET["branch"])) {
		echo "<script>\n" .
			"\tvar nextBatchLink = document.getElementById(\"next_batch_link\");\n" .
			"\tnextBatchLink.href = \"runs.php?branch=" . $row["branch"] . "&last_run_id=" . $last_run_id . "\";\n" .
			"</script>\n";
	}
?>

			</div>
		</div>
	</body>
</html>
