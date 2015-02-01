<?php
	# This PHP file provides the functionality for logging out of the administration area.

	include '/var/www/html/etc/common.php';

	requireSSL();

	session_start();
	if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn']) {
		unset($_SESSION['user']);
		unset($_SESSION['loggedIn']);
		session_destroy();
		header("Location: ../index.php");
	} else {
		header("Location: login.php");
	}
?>