<?php
 	# This PHP file provides the backend for editing and deleting maps currently in the database.

 	include '/var/www/html/etc/common.php';

 	requireSSL();

	# Checks if deleting
	if (!empty($_POST['delete'])) {
		try {
			$db = connectDB();
			$delete = explode(",", $_POST['delete']);
			foreach ($delete as $mapID) {
				$sql = "DELETE FROM map WHERE id='{$mapID}';";
				$query = $db->prepare($sql);
				if ($query->execute()) {
					header("HTTP/1.1 200 OK");
				} else {
					errorOutput(serialize($query->errorInfo()));
					header("HTTP/1.1 304 Not Modified");
				}
			}
		} catch (PDOException $error) {
			errorOutput($error->getMessage());
			header("HTTP/1.1 500 Internal Server Error");
		}

	# Otherwise, must be editing
	} else {
		try {
			# These fields + a picture are required
			$location = $_POST['location'];
			$description = $_POST['description'];
			$year = $_POST['year'];
			$width = $_POST['width'];
			$height = $_POST['height'];
			$backText = $_POST['backText'];
			$shelf = $_POST['shelf'];
			$department = $_POST['department'];

			# These fields are not required, or can be left blank to give them default values
			$language = $_POST['language'];
			$material = $_POST['material'];
			$scale = $_POST['scale'];
			$notes = $_POST['notes'];
			$room = $_POST['room'];
			$quantity = $_POST['quantity'];
			$sold = $_POST['sold'];

			# An array of all fields
			$fields = array("location", "description", "year", "width", "height", "language", "department", "room", "shelf", "notes", "quantity",
					"material", "scale", "backText");

			# An array of fields that only contain numbers
			$numFields = array("year", "width", "height", "quantity");

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

			# Map ID
			$id = $_POST['id'];

			# Year, if 0, will show as "No year listed" on site, but is 0 in database. Must be translated back!
			if ($year == "No year listed") {
				$year = 0;
			}

			# Ensures all required fields are still filled out
			if ($location === "" || $description === "" || $year === "" || $width === "" || $height === "" || $shelf === ""
					|| $backText === "") {
				header("HTTP/1.1 400 Bad Request");
			} else {
				$db = connectDB();
				$sql = "UPDATE map
						SET location = :location, description = :description, year = :year,
							width = :width, height = :height, shelf = :shelf,
							notes = :notes, sold = :sold, quantity = :quantity,
							language = :language, department = :department, room = :room, material = :material,
							scale = :scale, backText = :backText
						WHERE id = :id;";
				$query = $db->prepare($sql);

				# Binding parameters (can be done using an array, too, but this makes adding fields easier)
				$query->bindParam(':location', $location, PDO::PARAM_STR);
				$query->bindParam(':description', $description, PDO::PARAM_STR);
				$query->bindParam(':year', $year, PDO::PARAM_INT);
				$query->bindParam(':width', $width, PDO::PARAM_INT);
				$query->bindParam(':height', $height, PDO::PARAM_INT);
				$query->bindParam(':shelf', $shelf, PDO::PARAM_STR);
				$query->bindParam(':notes', $notes, PDO::PARAM_STR);
				$query->bindParam(':sold', $sold, PDO::PARAM_INT);
				$query->bindParam(':quantity', $quantity, PDO::PARAM_INT);
				$query->bindParam(':language', $language, PDO::PARAM_STR);
				$query->bindParam(':department', $department, PDO::PARAM_STR);
				$query->bindParam(':room', $room, PDO::PARAM_STR);
				$query->bindParam(':material', $material, PDO::PARAM_STR);
				$query->bindParam(':scale', $scale, PDO::PARAM_STR);
				$query->bindParam(':backText', $backText, PDO::PARAM_STR);
				$query->bindParam(':id', $id, PDO::PARAM_INT);

				if ($query->execute()) {
					header("HTTP/1.1 200 OK");
				} else {	
					errorOutput(serialize($query->errorInfo()));
					header("HTTP/1.1 304 Not Modified");
				}
			}
		} catch (PDOException $error) {
			errorOutput($error->getMessage());
			header("HTTP/1.1 500 Internal Server Error");
		}
	}
?>