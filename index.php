<?php
	# This PHP webpage is the main page, which displays the welcome information, a search area, and a
	# 	grid of all of the available maps (with tabbed navigation to view starred maps).

	include '/var/www/html/etc/common.php';
?>

<!DOCTYPE html>
<html>
	<head>
		<title>UW Geography Map Database</title>
		<?= outputScripts(true) ?>
		<script src="/js/search.js" type="text/javascript"></script>
		<meta name="description" content="The main page for UW Geography Map Store" />
	</head>

	<body>
		<div class="container-fluid">
			<?= head() ?>

			<div class="container">
				<div class="page-header">
					<h1>Welcome!</h1>
				</div>
			
				<p>Welcome to UW's Geography map database! You can use the form below to search for a map with desired attributes, or you can browse all of the available maps.</p>
				<p>Click on a map's picture to view more details about it. You can also save a map to view later by clicking the star when you hover over it.</p>
			</div>

			<br />

			<?= searchForm() ?>

			<hr />

			<!-- Nav tabs -->
			<div class="container">
				<ul class="nav nav-tabs" role="tablist" id="tabNav">
					<li data-toggle="tab" id="allMapsTab" class="active"><a href="#all">All Maps</a></li>
					<li data-toggle="tab" id="starredMapsTab"><a href="#starred">Starred Maps</a></li>
				</ul>
			</div>

			<br />

			<!-- Tab panes -->
			<div class="container tab-content">

				<!-- All maps -->
				<div class="tab-pane fade in active" id="all">
					<?= outputMaps(false) ?>
				</div>

				<!-- Starred maps (maps filled via getStarredMaps() in index.js) -->
				<div class="tab-pane fade" id="starred">
					<div class="container mapArea">
						<?= outputControls(0, "starred") ?>
						<div class="row maps"></div>
					</div>
				</div>
			</div>

			<?= foot() ?>
		</div>
	</body>
</html>