<?php
	# This PHP webpage provides the ability to search for a map based on location, description,
	# 	language, year, and dimensions, as well as administrative fields, such as ID, room, and shelf.

	# NOTE 1: $$ is a variable variable, so $$columns[0] is $location, which is $_GET['location'].
	# 	But, $columns[0] is "location", which is just a string.

	include '/var/www/html/etc/common.php';

	requireSSL();
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Search</title>
		<?= outputScripts(); ?>
		<link rel="stylesheet" type="text/css" href="//<?= $HOSTNAME ?>/stylesheets/admin.css" />
		<script src="//<?= $HOSTNAME ?>/js/admin.js" type="text/javascript"></script>
		<script src="//<?= $GLOBALS['hostname'] ?>/js/search.js" type="text/javascript"></script>
		<meta name="description" content="Search for maps within the UW Geography Map Database" />
	</head>

	<body>
		<div class="container">
			<?= head(); ?>

			<br />

			<div class="container">
				<?= searchForm(true); ?>
			</div>

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
	$backText = $_GET['backText'];
	$department = $_GET['department'];
	$room = $_GET['room'];
	$shelf = $_GET['shelf'];
	$id = $_GET['id'];

	# Construct the query
	$columns = array("location", "description", "language", "minyear", "maxyear", "minwidth", "maxwidth", "minheight", "maxheight", "backText", "department", "room", "shelf", "id");
	$sql = "SELECT * FROM map WHERE sold = false"; # Beginning of SQL query
	if ($location != "" || $description != "" || $language != "" || $minyear != "" || $maxyear != "" || $minwidth != "" || $maxwidth != ""
			|| $minheight != "" || $maxheight != "" || $backText != "" || $department != "" || $room != "" || $shelf != "" || $id != "") {
		$searchingMin = true; # Start by searching a minimum value
		for ($i = 0; $i < count($columns); $i++) {
			if (substr($columns[$i], 0, 3) != "min" && substr($columns[$i], 0, 3) != "max") {
				if ($$columns[$i] != "") { # See note 1
					$sql = $sql . " AND ";
					if ($columns[$i] == "id") {
						$sql = $sql . "{$columns[$i]} = {$$columns[$i]}";
					} else {
						$sql = $sql . "{$columns[$i]} LIKE '%{$$columns[$i]}%'"; # Add field to query
					}
				}
			} else {
				if ($$columns[$i] != "") {
					$searchTerm = substr($columns[$i], 3);
					$sql = $sql . " AND ";
					if ($searchingMin) {
						$sql = $sql . "{$searchTerm} >= {$$columns[$i]}";
					} else {
						$sql = $sql . "{$searchTerm} <= {$$columns[$i]}";
					}
				}
				$searchingMin = !$searchingMin;
			}
		}
	}

	# Connect to database, submit query, and get results
	$db = connectDB();
	$query = $db->prepare($sql);
	if ($query->execute()) {
		$results = $query->fetchAll();
		if (empty($results)) {
			echo "We did not find anything matching your query. Please adjust your query and try again.";
		} else {
			outputMaps(true, $results);
?>
			<br />			

			<div class="form-group">
				<div class="col-sm-2">
					<button type="submit" class="btn btn-danger btn-lg" id="deleteSelected">Delete Selected Entries</button>
				</div>
			</div>
<?php
		}
	} else {
		errorOutput($query->errorInfo());
		echo "Something went wrong on the server. Please try again.";
	}
?>
		</div>
	</body>
</html>