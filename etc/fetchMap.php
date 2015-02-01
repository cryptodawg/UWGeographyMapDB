<?php
	# This PHP file will fetch and output the details of a certain map given its ID.

	include '/var/www/html/etc/common.php';

	# Not displaying any HTML, only plaintext
	header('Content-Type:text/plain');

	if (!empty($_GET)) {
		$db = connectDB();
		$sql = "SELECT * FROM map WHERE id = {$_GET['id']} LIMIT 1";
		$result = $db->query($sql);
		if ($result->rowCount() > 0) {
			foreach($result as $map) {
				echo "Location|" . $map['location'] . "\n";
				echo "Description|" . $map['description'] . "\n";
				echo "Year|" . $map['year'] . "\n";
				echo "Language|" . $map['language'] . "\n";
				echo "Material|" . $map['material'] . "\n";
				echo "Width|" . $map['width'] . "\n";
				echo "Height|" . $map['height'] . "\n";
				echo "Scale|" . $map['scale'] . "\n";
				echo "Text on back|" . $map['backText'] . "\n";
				echo "Notes|" . $map['notes'] . "\n";
				echo "Department|" . $map['department'] . "\n";
				echo "Room|" . $map['room'] . "\n";
				echo "Shelf|" . $map['shelf'] . "\n";
				echo "Quantity|" . $map['quantity'] . "\n";
				echo $map['picture'];
			}
			header("HTTP/1.1 200 OK");
		} else {
			header("HTTP/1.1 404 Not Found");
		}
		$db = null;
	} else {
		header("HTTP/1.1 400 Bad Request");
	}
?>