<?php
	# This PHP webpage provides the ability to search for a map based on location, description,
	# 	language, year, and dimensions.

	# NOTE 1: $$ is a variable variable, so $$columns[0] is $location, which is $_GET['location'].
	# 	But, $columns[0] is "location", which is just a string.

	include '/var/www/html/etc/common.php';
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Search</title>
		<?= outputScripts(); ?>
		<script src="/js/search.js" type="text/javascript"></script>
		<meta name="description" content="Search for maps within the UW Geography Map Database" />
	</head>

	<body>
		<div class="container-fluid">
			<?= head(); ?>

			<br />
			
			<?= searchForm() ?>

			<hr />
<?php
	# All of the searchable fields
	$location = $_GET['location'];
	$description = $_GET['description'];
	$language = $_GET['language'];
	$minyear = $_GET['minyear'];
	$maxyear = $_GET['maxyear'];
	$minwidth = $_GET['minwidth'];
	$maxwidth = $_GET['maxwidth'];
	$minheight = $_GET['minheight'];
	$maxheight = $_GET['maxheight'];

	# Construct the query
	$columns = array("location", "description", "language", "minyear", "maxyear", "minwidth", "maxwidth", "minheight", "maxheight");
	$sql = "SELECT * FROM map WHERE sold = false"; # Beginning of SQL query
	if ($location != "" || $description != "" || $language != "" || $minyear != "" || $maxyear != "" || $minwidth != "" || $maxwidth != ""
			|| $minheight != "" || $maxheight != "") {
		$searchingMin = true; # Start by searching a minimum value
		for ($i = 0; $i < count($columns); $i++) {
			if ($$columns[$i] != "") { # See note 1
				if (substr($columns[$i], 0, 3) != "min" && substr($columns[$i], 0, 3) != "max") {
					$sql = $sql . " AND ";
					$sql = $sql . "{$columns[$i]} LIKE :" . $columns[$i]; # Add field to query
					$$columns[$i] = '%' . $$columns[$i] . '%';
				} else {
					$searchTerm = substr($columns[$i], 3);
					$sql = $sql . " AND ";
					if ($searchingMin) {
						$sql = $sql . "{$searchTerm} >= :" . $columns[$i];
					} else {
						$sql = $sql . "{$searchTerm} <= :" . $columns[$i];
					}
				}
				$searchingMin = !$searchingMin;
			}
		}
	}

	# Connect to database, bind parameters, submit query, and get results
	$db = connectDB();
	$query = $db->prepare($sql);
	for ($i = 0; $i < count($columns); $i++) { 
		if ($$columns[$i] != "") { # See note 1
			$prefix = substr($columns[$i], 0, 3);
			if ($prefix == "min" || $prefix == "max") {
				$query->bindParam(':' . $columns[$i], $$columns[$i], PDO::PARAM_INT);
			} else {
				$query->bindParam(':' . $columns[$i], $$columns[$i], PDO::PARAM_STR);
			}
		}
	}

	if ($query->execute()) {
		$results = $query->fetchAll();
		if (empty($results)) {
?>
			<div id="searchError" class="container">
				<p>We did not find anything matching your query. Please adjust your query and try again.</p>
			</div>
<?php
		} else {
			outputMaps(false, $results);
		}
	} else {
		errorOutput(serialize($query->errorInfo()));
?>
		<div id="searchError" class="container">
			<p>We did not find anything matching your query. Please adjust your query and try again.</p>
		</div>
<?php
	}
?>
		</div>

		<?= foot() ?>
		
	</body>
</html>