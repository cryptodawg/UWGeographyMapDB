<?php
	# This PHP file provides the backend for adding maps to the database.

	include '/var/www/html/etc/common.php';

	requireSSL();

	# These fields + a picture are required
	$location = $_POST['location'];
	$description = $_POST['description'];
	$year = $_POST['year'];
	$width = $_POST['width'];
	$height = $_POST['height'];

	# These fields are not required, or can be left blank to give them default values
	$language = $_POST['language'];
	$room = $_POST['room'];
	$shelf = $_POST['shelf'];
	$notes = $_POST['notes'];
	$quantity = $_POST['quantity'];
	$material = $_POST['material'];
	$scale = $_POST['scale'];
	$backText = $_POST['backText'];
	$dept = $_POST['department'];

	# Sets default values if a field is left blank, as MySQL interprets an input of nothing
	# 	as MySQL interprets a value to be zero (if INT) or empty (if STR or VARCHAR),
	# 	even if a default is given for the field.
	if ($language == "") {
		$language = "English";
	}

	if ($material == "") {
		$material = "Canvas";
	}

	if ($quantity == "") {
		$quantity = 1;
	}

	if ($room == "") {
		$room = "403";
	}

	if ($scale == "") {
		$scale = "No scale listed";
	}

	# An array of all fields
	$fields = array("location", "description", "year", "width", "height", "language", "room", "shelf", "dept", "notes", "quantity",
			"material", "scale", "backText");

	# An array of fields that only contain numbers
	$numFields = array("year", "width", "height", "quantity");

	# Ensures all required fields are filled out and that a file is uploaded
	if ($location == "" || $description == "" || $year == "" || $width == "" || $height == "" || $shelf == ""
			|| $backText == "" || !is_uploaded_file($_FILES['picture']['tmp_name'])
			|| ($_FILES['picture']['type'] != "image/jpeg"	&& $_FILES['picture']['type'] != "image/jpg")) {
		header("HTTP/1.1 400 Bad Request");
	} else {
		# Ensures that the field data is valid
		$pipeExpr = "/[|]/";
		foreach($fields as $field) {
			if (preg_match($pipeExpr, $$field)) {
				header("HTTP/1.1 400 Bad Request");
			}
		}
		$numExpr = "/\D/";
		foreach ($numFields as $field) {
			if (preg_match($numExpr, $$field)) {
				header("HTTP/1.1 400 Bad Request");
			}
		}

		# Attempts to add to DB. Note that there are two types of errors that can be outputted - this is due
		# 	to the difference between a PDO object (connection to DB) and a PDOStatement object (a SQL query).
		try {

			# Connects to DB.
			$db = connectDB();

			# Checks if map entry already exists
			$sql = "SELECT * FROM map WHERE location = '{$location}' AND description = '{$description}' AND
					width = {$width} AND height = {$height} AND language = '{$language}' AND
					material = '{$material}' AND scale = '{$scale}'";
			$query = $db->prepare($sql);
			if ($query->execute()) {
				$results = $query->fetch();
				if (!empty($results)) {
					# This gets parsed by admin.js to give information about the existing map entry
					echo $results['id'];
					echo " ";
					echo $results['room'];
					echo " ";
					echo $results['shelf'];
					header("HTTP/1.1 206 Partial Content");
				}
			} else {
				errorOutput(serialize($query->errorInfo()));
				header("HTTP/1.1 304 Not Modified");
			}

			# Generates a random 4-digit ID for the map, ensuring it doesn't already exist in the database.
			do {
				$id = "";
				for ($i = 0; $i < 4; $i++) {
					$id = $id . rand(0, 9);
				}
				$sql = "SELECT * FROM map WHERE id = {$id}";
				$results = $db->query($sql);
			} while (empty($results));

			# Prepares SQL query
			$sql = "INSERT INTO map (id, location, description, year, language, width, height, material, scale, backText, department, room, shelf, picture, notes, quantity) VALUES
					(:id, :location, :description, :year, :language, :width, :height, :material, :scale, :backText, :department, :room, :shelf, :picture, :notes, :quantity)";
			$query = $db->prepare($sql);

			# Binding parameters (can be done using an array, too, but this makes adding fields easier)
			$query->bindParam(':id', $id, PDO::PARAM_INT);
			$query->bindParam(':location', $location, PDO::PARAM_STR);
			$query->bindParam(':description', $description, PDO::PARAM_STR);
			$query->bindParam(':year', $year, PDO::PARAM_INT);
			$query->bindParam(':language', $language, PDO::PARAM_STR);
			$query->bindParam(':width', $width, PDO::PARAM_INT);
			$query->bindParam(':height', $height, PDO::PARAM_INT);
			$query->bindParam(':material', $material, PDO::PARAM_STR);
			$query->bindParam(':scale', $scale, PDO::PARAM_STR);
			$query->bindParam(':backText', $backText, PDO::PARAM_STR);
			$query->bindParam(':room', $room, PDO::PARAM_STR);
			$query->bindParam(':shelf', $shelf, PDO::PARAM_STR);
			$query->bindParam(':department', $dept, PDO::PARAM_STR);
			$query->bindParam(':picture', $_FILES['picture']['name'], PDO::PARAM_STR);
			$query->bindParam(':notes', $notes, PDO::PARAM_STR);
			$query->bindParam(':quantity', $quantity, PDO::PARAM_INT);

			# Executes query
			if ($query->execute() && move_uploaded_file($_FILES['picture']['tmp_name'],
					"/var/www/html/mapPics/" . $_FILES['picture']['name'])) {
				createThumbs(200);
				header("HTTP/1.1 201 Entry Created");
			} else {
				errorOutput(serialize($query->errorInfo()));
				header("HTTP/1.1 304 Not Modified");
			}
			
			# Closes connection
			$db = null;
		} catch (PDOException $error) {
			errorOutput($error->getMessage());
			header("HTTP/1.1 500 Server Error");
		}
	}
?>
