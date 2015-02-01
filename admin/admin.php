<?php
	# This PHP webpage allows you to perform various administrator-only functions - such as adding,
	# 	editing, and deleting maps, as well the ability to search for a map by its ID.

	include "/var/www/html/etc/common.php";
	
	requireSSL();
	session_start();
	if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn']) { # Check if logged in
?>
	<!DOCTYPE html>
	<html>
		<head>
			<title>UW Geography Map Store Administration</title>
			<?= outputScripts(); ?>
			<link rel="stylesheet" type="text/css" href="//<?= $HOSTNAME ?>/stylesheets/admin.css" />
			<script src="//<?= $HOSTNAME ?>/js/tab.js" type="text/javascript"></script>
			<script src="//<?= $HOSTNAME ?>/js/admin.js" type="text/javascript"></script>
		</head>

		<body>
			<div class="container-fluid">
				<?= head() ?>

				<br />

				<div class="container">
					<span>Welcome back, <?= $_SESSION['user'] ?>!</span>
					<span id="logout"><span class="glyphicon glyphicon-info-sign"></span> <a href="logout.php">Log Out</a></span>

					<hr />

					<div class="container bg-info">
						<p>Please note the following:</p>
						<ul>
							<li>Red background marks a sold map</li>
							<li>Currently unable to edit a map's picture, so use phpmyadmin to do so</li>
						</ul>
					</div>
				</div>

				<br />

				<!-- Nav tabs -->
				<div class="container">
					<ul class="nav nav-tabs" role="tablist" id="tabNav">
			 			<li data-toggle="tab"><a href="#add">Add Entries</a></li>
			 			<li data-toggle="tab"><a href="#edit">Edit Entries</a></li>
						<li data-toggle="tab"><a href="#search">Search</a></li>
					</ul>
				</div>

				<br />

				<!-- Tab panes -->
				<div class="container tab-content">

					<!-- Adding -->
					<div class="tab-pane fade in active" id="add">
						<div class="container">
							<form role="form" class="form-horizontal" id="newMapForm" method="POST" enctype="multipart/form-data">
								<fieldset>
									<legend>Add a map</legend>
									<p>These fields are required</p>
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
										<label class="col-sm-2 control-label">Year</label>
										<div class="col-sm-10">
											<input name="year" class="form-control smallInput" />
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">Size</label>
										<div class="col-sm-10">
											<input name="width" class="form-control smallInput" placeholder="Width" /> inches by <input name="height" class="form-control smallInput" placeholder="Height" /> inches
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">Text on back</label>
										<div class="col-sm-10">
											<input name="backText" class="form-control" />
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">Shelf</label>
										<div class="col-sm-10">
											<input name="shelf" class="form-control smallInput" />
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">Picture</label>
										<div class="col-sm-10">
											<input type="file" name="picture" accept="image/jpeg, image/jpg" />
										</div>
									</div>
									<br />
									<p>These fields can be left blank to take default values</p>
									<div class="form-group">
										<label class="col-sm-2 control-label">Language</label>
										<div class="col-sm-10">
											<input name="language" class="form-control" placeholder="English" />
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">Material</label>
										<div class="col-sm-10">
											<input name="material" class="form-control" placeholder="Canvas" />
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">Scale</label>
										<div class="col-sm-10">
											<input name="scale" class="form-control" />
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">Notes</label>
										<div class="col-sm-10">
											<input name="notes" class="form-control" />
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">Room</label>
										<div class="col-sm-10">
											<input name="room" class="form-control smallInput" placeholder="403" />
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">Department</label>
										<div class="col-sm-10">
											<label class="radio-inline">
												<input name="department" type="radio" value="Geography" checked="checked" />
												Geography
											</label>
											<label class="radio-inline">
												<input name="department" type="radio" value="History" />
												History
											</label>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label">Quantity</label>
										<div class="col-sm-10">
											<input name="quantity" class="form-control smallInput" placeholder="1" />
										</div>
									</div>
									<div class="form-group">
										<div class="col-sm-2 col-sm-offset-5">
											<button type="submit" class="btn btn-primary btn-lg" id="submitEntry">Submit Map Entry</button>
										</div>
									</div>
								</fieldset>
							</form>
						</div>
					</div>

					<!-- Editing -->
					<div class="tab-pane fade" id="edit">
						<?= outputMaps(true); ?>

						<br />			

						<div class="form-group">
							<div class="col-sm-2">
								<button type="submit" class="btn btn-danger btn-lg" id="deleteSelected">Delete Selected Entries</button>
							</div>
						</div>
					</div>

					<!-- Searching -->
					<div class="tab-pane fade" id="search">
						<div class="container">
							<?= searchForm(true) ?>
						</div>
						<div class="container" id="searchResults"></div>
					</div>
				</div>
			</div>
		</body>
	</html>
<?php
	} else { # Redirect the user if not logged in
		header("Location: login.php");
	}
?>