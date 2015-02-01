<?php
	# This PHP file provides common code found in UW Geography Map Databases's source files. Remember
	# 	to place "include '/var/www/html/etc/common.php'" without the outside quotes at the top of each
	# 	PHP file.

	# Change this when changing the hostname of the server
	$GLOBALS['HOSTNAME'] = "mapserver.geog.uw.edu";


	# Returns a PDO object used to connect to and manipulate entries in the database
	function connectDB() {
		$dbUser = "nick";
		$dbPasswd = "gohuskies!";
		return new PDO("mysql:host=localhost;dbname=maps", $dbUser, $dbPasswd);
	}


	# Creates thumbnails of each map's picture and stores them to a directory
	# Parameters: $thumbwidth - Integer used to specify the width of created thumbnails in pixels
	function createThumbs($thumbWidth) {
		$pathToImages = "/var/www/html/mapPics/";
		$pathToThumbs = "/var/www/html/mapPics/thumbs/";
		$dir = opendir($pathToImages);
		while (($fname = readdir($dir)) !== false) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE); # Description of file to check
			$filetype = finfo_file($finfo, "{$pathToImages}" . "{$fname}"); # Returns the MIME-type of the file
			if (!file_exists("{$pathToThumbs}" . "{$fname}")
					&& ($filetype == "image/jpeg" || $filetype == "image/jpg")) {
				$info = pathinfo($pathToImages . $fname);
				$img = imagecreatefromjpeg("{$pathToImages}{$fname}");
				$imgWidth = imagesx($img);
				$imgHeight = imagesy($img);
				$newWidth = $thumbWidth;
				$newHeight = floor($imgHeight * ($thumbWidth/$imgWidth));
				$tmpImg = imagecreatetruecolor($newWidth, $newHeight);
				imagecopyresized($tmpImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $imgWidth, $imgHeight);
				imagejpeg($tmpImg, "{$pathToThumbs}{$fname}");
			}
		}
		closedir($dir);
	}


	# Outputs formatted error info to a file
	# Parameters: $errorText - String that describes the error
	function errorOutput($errorText) {
		$date = date("Y-m-d H:i:s");
		$errorString = $date . "\n" . $errorText . "\n\n";
		file_put_contents("/var/www/html/admin/error.log", $errorString, FILE_APPEND);
	}


	# Outputs the footer information
	function foot() {
?>
		<hr />
		<div id="footer" class="container">
			<p>
			 	Department of Geography · University of Washington · Box 353550 · Smith Hall 408 · Seattle, Washington · 98195
				<br>
				Telephone: (206) 543-5843 · Fax: (206) 543-3313
			</p>
			<p>This website was created by <a href="mailto:nievan12@u.washington.edu">Nick Evans</a> for the <a href="http://depts.washington.edu/geog/">Geography Department at the University of Washington</a></p>
			<a href="/admin/login.php">Administrator Login</a>
		</div>
<?php
	}


	# Outputs the header image
	function head() {
?>
		<div id="header" class="container">
			<a href="http://<?= $GLOBALS['HOSTNAME'] ?>/index.php">
				<img src="//<?= $GLOBALS['HOSTNAME'] ?>/logo.png" alt="Department of Geography" />
			</a>
		</div>
<?php
	}

	function outputControls($count = 0, $group = "all") {
?>
		<div class="container displayMapControls">
			<div class="controls">
				Sort by:
				<select class="form-control orderBySelect" name="orderBy">
					<option selected>Location</option>
					<option>Description</option>
					<option>Year</option>
					<option>Language</option>
				</select>
<?php
				# If you have two groups of controls on one page (like viewing all maps vs. starred), this ensures the groups don't bind together,
				#		i.e. switching one won't switch both.
				if ($group != "all") {
					$inputName = "order" . $group;
				} else {
					$inputName = "order";
				}
?>
				<label class="radio-inline">
					<input type="radio" name=<?= $inputName ?> class="ascendingSelect" value="asc" checked> Ascending
				</label>
				<label class="radio-inline">
					<input type="radio" name=<?= $inputName ?> class="descendingSelect" value="desc"> Descending
				</label>
				<label class="checkbox-inline hideNoYearLabel">
					<input type="checkbox" class="hideNoYearBox"> Hide maps with no year listed
				</label>
			</div>
			<br />
			<div class="departmentSort">
				Department:
				<label class="radio-inline">
					<input type="radio" name="dept" value="all" checked> All
				</label>
				<label class="radio-inline">
					<input type="radio" name="dept" value="geog"> Geography
				</label>
				<label class="radio-inline">
					<input type="radio" name="dept" value="hist"> History
				</label>
			</div>
			<div class="countContainer">
<?php
				if ($count == 1) {
?>
					<span class="count"><?= $count ?> result shown</span>
<?php
				} else {
?>
					<span class="count"><?= $count ?> results shown</span>
<?php
				}
?>
			</div>
		</div>

		<br />
<?php
	}


	# Outputs a grid of maps
	# Parameters: $admin - Boolean parameter that toggles the display of administrative information
	#			  $maps - Array of maps to be displayed, but if empty or not specified, will output all maps
	function outputMaps($admin, $maps = "") {
		if ($maps == "") {
			$db = connectDB();
			if ($admin) {
				$sql = "SELECT * FROM map ORDER BY location";
			} else {
				$sql = "SELECT * FROM map WHERE sold = false ORDER BY location";
			}
			$maps = $db->query($sql);
			$count = $maps->rowCount();
		} else {
			$count = count($maps);
		}
		$i = 1;
		if (isset($_COOKIE["starredMaps"]) && $_COOKIE['starredMaps'] != "") {
			$starredMaps = explode(",", $_COOKIE["starredMaps"]);
		} else {
			$starredMaps = Array(); # Empty array so notices from in_array() don't appear in PHP's error log
		}
?>
		<div class="container mapArea">
			<?= outputControls($count) ?>
			<div class="row maps">
<?php
				foreach($maps as $map) {
					$picLocation = "//{$GLOBALS['HOSTNAME']}/mapPics/thumbs/" . $map['picture'];
					$mapClass = "col-sm-6 col-md-4 col-lg-3 map";
					if ($map['sold']) {
						$mapClass = $mapClass . " sold";
					}
					if ($admin) {
						$mapClass = $mapClass . " adminMap";
					}
					if (in_array($map['id'], $starredMaps)) {
						$mapClass = $mapClass . " starred";
					}
?>
					<div class="<?= $mapClass ?>">
<?php
						$picLocation = "//{$GLOBALS['HOSTNAME']}/mapPics/thumbs/" . $map['picture'];
?>
						<div class="mapInfo" data-id=<?= $map['id'] ?> data-dept=<?= $map['department'] ?>>
							<img src=<?= $picLocation ?> alt="map picture" class="img-thumbnail mapPic" />
							<p class="location"><?= $map['location']; ?></p>
							<p class="description"><?= $map['description']; ?></p>
<?php
							if ($map['year'] == 0) {
?>
								<p class="year">No year listed</p>
<?php
							} else {
?>
								<p class="year"><?= $map['year'] ?></p>
<?php
							}
?>
							<p class="language"><?= $map['language'] ?></p>
<?php
							if ($admin) {
?>
								<div class="checkbox">
									<label>
										<input type="checkbox" name="delete" value=<?= $map['id'] ?> /> Delete
									</label>
								</div>
								<button name="edit" class="btn btn-default btn-xs">Edit</button>
<?php
							}
?>
						</div>
<?php
						if (in_array($map['id'], $starredMaps)) {
?>
							<span class="glyphicon glyphicon-star star" data-id="<?= $map['id'] ?>"></span>
<?php
						}
?>
					</div>
<?php
					if ($i % 2 == 0) {
?>
						<div class="clearfix visible-sm-block"></div>
<?php
					} else if ($i % 3 == 0) {
?>
						<div class="clearfix visible-md-block"></div>
<?php
					} else if ($i % 4 == 0) {
?>
						<div class="clearfix visible-lg-block"></div>
<?php
					}
					$i++;
				}
?>
			</div>
		</div>
<?php
		$db = null;
	}


	# Outputs all of the necessary stylesheet links, scripts, and meta info - place the function call in <head> tags of HTML
	# Parameters: $index - a boolean that toggles a link of index.js to the webpage. Will be false if not specified.
	#				Although index.js is designed to only be used on one page (index.php), we cannot link it to the page before
	#				calling this function, as it references the "hostname" variable in common.js.
	function outputScripts($index = false) {
?>
		<link rel="shortcut icon" type="image/png" href="//<?= $GLOBALS['HOSTNAME'] ?>/icon.png" />
		<link rel="stylesheet" type="text/css" href="//<?= $GLOBALS['HOSTNAME'] ?>/stylesheets/style.css" />
		<link rel="stylesheet" type="text/css" href="//<?= $GLOBALS['HOSTNAME'] ?>/stylesheets/bootstrap.css" />
		<script src="//<?= $GLOBALS['HOSTNAME'] ?>/js/jquery-1.11.1.js" type="text/javascript"></script>
		<script src="//<?= $GLOBALS['HOSTNAME'] ?>/js/jquery.cookie.js" type="text/javascript"></script>
		<script src="//<?= $GLOBALS['HOSTNAME'] ?>/js/bootstrap.js" type="text/javascript"></script>
		<script src="//<?= $GLOBALS['HOSTNAME'] ?>/js/common.js" type="text/javascript"></script>
<?php
		if ($index) {
?>
			<script src="/js/index.js" type="text/javascript"></script>
<?php
		}
?>
		<script src="//<?= $GLOBALS['HOSTNAME'] ?>/js/mapDetails.js" type="text/javascript"></script>
		<meta name="author" content="Nick Evans" />
		<meta charset="utf-8" />
<?php
	}


	# Used by the administration pages to require SSL
	function requireSSL() {
		if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") {
			header('Strict-Transport-Security: max-age=31536000');
			header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			exit();
		}
	}


	# Outputs a form that will act on search.php to search for maps
	# Parameters: $admin - a boolean that toggles whether administrative fields can be searched
	function searchForm($admin = false) {
?>
		<div class="container">
<?php
			if ($admin) {
?>
				<form role="form" id="searchForm" class="form-horizontal" action="adminSearch.php" method="GET">
<?php
			} else {
?>
				<form role="form" id="searchForm" class="form-horizontal" action="search.php" method="GET">
<?php
			}
?>
				<fieldset>
					<legend>Search</legend>
					<div class="form-group">	
						<label class="col-sm-2 control-label">Location</label>
						<div class="col-sm-10">
							<input name="location" class="form-control" />
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Description</label>
						<div class="col-sm-10">
							<input name="description" class="form-control" />
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Language</label>
						<div class="col-sm-10">
							<input name="language" class="form-control" />
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Year</label>
						<div class="col-sm-10">
							<input name="minyear" class="form-control smallInput" placeholder="Min" />
							<input name="maxyear" class="form-control smallInput" placeholder="Max" />
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Width (inches)</label>
						<div class="col-sm-10">
							<input name="minwidth" class="form-control smallInput" placeholder="Min" />
							<input name="maxwidth" class="form-control smallInput" placeholder="Max" />
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">Height (inches)</label>
						<div class="col-sm-10">
							<input name="minheight" class="form-control smallInput" placeholder="Min" />
							<input name="maxheight" class="form-control smallInput" placeholder="Max" />
						</div>
					</div>
<?php
					if ($admin) {
?>
						<div class="form-group">
							<label class="col-sm-2 control-label">Text on back</label>
							<div class="col-sm-10">
								<input name="backText" class="form-control" />
							</div>
						</div>
							<div class="form-group">
							<label class="col-sm-2 control-label">Department</label>
							<div class="col-sm-10">
								<input name="department" class="form-control" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">Room</label>
							<div class="col-sm-10">
								<input name="room" class="form-control smallInput" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">Shelf</label>
							<div class="col-sm-10">
								<input name="shelf" class="form-control smallInput" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">ID</label>
							<div class="col-sm-10">
								<input name="id" class="form-control smallInput" />
							</div>
						</div>
<?php
					}
?>
					<div class="form-group">
						<div class="col-sm-offset-2 col-sm-10">
							<button type="submit" id="submitSearch" class="btn btn-default">Search</button>
						</div>
					</div>
				</fieldset>
			</form>
		</div>
<?php
	}
?>