<?php
	# This PHP webpage provides the functionality for logging into the administration page.

	# Note from Nick: I realize most of this functionality (SSL, brute-force prevention, etc.) is completely unnecessary,
	# 	as a simple login page would do the trick for something that doesn't contain sensitive data (like this project).
	#	I just wanted to have some fun! :)

	# NOTE 1: DO NOT ALERT THE USER THAT THE USERNAME EXISTS, BUT PASSWORD IS INCORRECT - it is a security risk!
	# 	Only tell them the credentials are wrong.

	include '/var/www/html/etc/common.php';

	requireSSL();

	# Displays the login area
	# Parameters: $info - the string that represents the information to display. If not specified or
	# 				left blank, no information will be displayed.
	function loginBox($info = "") {
?>
		<!DOCTYPE html>
		<html>
			<head>
				<title>Log In</title>
				<?= outputScripts(); ?>
				<link rel="stylesheet" type="text/css" href="//<?= $GLOBALS['HOSTNAME'] ?>/stylesheets/admin.css" />
			</head>
			<body>
				<div class="container" id="loginArea">
					<form class="form-horizontal" id="loginForm" method="POST" action="login.php">
						<fieldset>
							<legend>Log In</legend>
<?php
							if ($info != "") {
?>
								<div class="bg-danger" id="loginInfo">
									<span><span class="glyphicon glyphicon-exclamation-sign"></span> <?= $info ?></span>
								</div>
								<br />
<?php
							}
?>
							<div class="form-group">
								<label class="col-sm-2 control-label">Username</label>
								<div class="col-sm-10">
									<input name="username" class="form-control" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-2 control-label">Password</label>
								<div class="col-sm-10">
									<input name="password" class="form-control" type="password" />
								</div>
							</div>
<?php
							# Inserts a disabled Submit button if the user is currently locked out of logging in
							if (isset($_SESSION['waitUntil'])) {
?>
								<div class="form-group">
									<div class="col-sm-2 col-sm-offset-5">
										<button type="submit" class="btn btn-primary btn-lg" disabled="true">Submit</button>
									</div>
								</div>
<?php
							} else {
?>
								<div class="form-group">
									<div class="col-sm-2 col-sm-offset-5">
										<button type="submit" class="btn btn-primary btn-lg">Submit</button>
									</div>
								</div>
<?php
							}
?>
						</fieldset>
					</form>
					<p><a href="http://<?= $GLOBALS['HOSTNAME'] ?>/">Return to home page</a></p>
				</div>
			</body>
		</html>
<?php
	}


	# Handles an incorrect login attempt
	#	Parameters:	$logQuery - a PDOStatement handler for the log history query
	function incorrectLogin($logQuery) {
		$logQuery->bindParam(':success', $success = false, PDO::PARAM_STR);
		if (!$logQuery->execute()) {
			errorOutput(serialize($logQuery->errorInfo()));
		}
		$_SESSION['attempts'] = $_SESSION['attempts'] + 1;
		if ($_SESSION['attempts'] == 5) { # Tried an incorrect login too many times -> lock the user out
			$_SESSION['waitUntil'] = time() + 30;
			$_SESSION['attempts'] = 0;
			loginBox("You have tried to log in too many times. Please wait for 30 seconds then try again.");
		} else { # Not locked out
			$attemptsRemaining = 5 - $_SESSION['attempts'];
			if ($attemptsRemaining == 1) {
				loginBox("Your credentials are incorrect. Please try again ({$attemptsRemaining} attempt remaining).");
			} else {
				loginBox("Your credentials are incorrect. Please try again ({$attemptsRemaining} attempts remaining).");
			}
		}
	}


	session_start();
	if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn']) { # Redirect if currently logged in
		header('Location: admin.php');
	} else {
		if (isset($_SESSION['waitUntil']) && time() >= $_SESSION['waitUntil']) { # Remove a lock if time has expired
			unset($_SESSION['waitUntil']);
		}
		if (empty($_POST)) {
			if (isset($_SESSION['waitUntil'])) { # User is currently locked out
				$wait = $_SESSION['waitUntil'] - time();
				loginBox("You have been locked out of logging in. Please wait for {$wait} seconds then try again.");
			} else {
				loginBox();
			}
		} else {
			$username = $_POST["username"];
			$password = $_POST["password"];

			if (!isset($_SESSION['attempts'])) { # First time trying to log in
				$_SESSION['attempts'] = 0;
			} 
			if ($_SESSION['attempts'] >= 5) { # If the user is locked out, but re-enabled the submit button and tried to log in again
				$wait = $_SESSION['waitUntil'] - time();
				loginBox("You have been locked out of logging in. Please wait for {$wait} seconds then try again.");
			} else { # User not locked out

				# Connect to the database
				$db_user = "nick";
				$db_passwd = "gohuskies!";
				$db = new PDO("mysql:host=localhost;dbname=mapsAdmin", $db_user, $db_passwd);

				# Check against the login database
				$loginSQL = "SELECT * FROM userInfo WHERE username = :username LIMIT 1";
				$loginQuery = $db->prepare($loginSQL);
				$loginQuery->bindParam(':username', $username, PDO::PARAM_STR);
				if ($loginQuery->execute()) {
					$logSQL = "INSERT INTO loginHistory (success, username, date) VALUES (:success, :username, :date)";
					$logQuery = $db->prepare($logSQL);
					$logQuery->bindParam(':username', $username, PDO::PARAM_STR);
					$date = date("Y-m-d H:i:s");
					$logQuery->bindParam(':date', $date, PDO::PARAM_STR);
					if ($loginQuery->rowCount() == 1) { # Username exists in database
						$userInDB = $loginQuery->fetchAll();
						foreach($userInDB as $user) {
							if ($password == $user['password']) { # Correct password for the username
								$logQuery->bindParam(':success', $success = true, PDO::PARAM_INT);
								$_SESSION['loggedIn'] = true;
								$_SESSION['user'] = $username;
								if ($logQuery->execute()) { # Correct login, successfully logged the login
									header('Location: admin.php');
								} else { # Correct login, but couldn't log it
									errorOutput(serialize($logQuery->errorInfo()));
									loginBox("Your credentials are correct, but the server failed to log you in.");
								}
							} else { # Incorrect password for the username - see Note 1
								incorrectLogin($logQuery);
							}
						}
					} else { # Username does not exist in database
						incorrectLogin($logQuery);
					}
				} else {
					errorOutput($loginQuery->errorInfo());
					loginBox("Something went wrong on the server. Please try again.");
				}
			}
		}
	}
?>