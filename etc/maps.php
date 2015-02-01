<?php
	# This PHP file will query the database, sorting by the given parameters.
	
	$searchAll = true;
	foreach ($GET as $field) {
		if ($field != "") {
			$searchAll = false;
			break;
		}
	}
	if ($searchAll) {
		$query = "SELECT * FROM map";
	} else {
		$query = "SELECT FROM map WHERE ";
		for ($i = 0; $i < count($GET); $i++) {
			if ($GET[$i] != "") {
				
			}
		}
	}
?>