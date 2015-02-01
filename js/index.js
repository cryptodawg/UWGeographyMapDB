// This Javascript file provides the functionality that is only found on the front page of the website.

// Runs when the window has finished loading
window.addEventListener('load', function() {
	document.getElementById("starredMapsTab").onclick = getStarredMaps;
});


// Outputs starred maps
function getStarredMaps() {
	var starredMaps = $('#all .starred').clone();
	var mapsNode = $('#starred .maps')[0];
	outputMaps(starredMaps, mapsNode); // This function is found in mapDetails.js

	// Sets the onclick of each star
	var starred = $('#starred .star');
	for (var i = 0; i < starred.length; i++) {
		starred[i].onclick = starMap;
	}

	// Sets the onclick of each map picture
	var mapPics = $('#starred .mapPic');
	for (var i = mapPics.length - 1; i >= 0; i--) {
		mapPics[i].onclick = showMapDetails;
	}

	// Sets the onhover of each map
	var maps = $('#starred .map');
	for (var i = 0; i < maps.length; i++) {
		maps[i].onmouseenter = outlineMap;
		maps[i].onmouseleave = removeOutlineMap;
	}
}

// Updates the page to indicate to the user that the map has been successfully unstarred.
// Parameters: map - the ID for the map to be unstarred
function removeStar(mapID) {
	if ($('#tabNav li.active')[0].id == "starredMapsTab") {
		// Removes from the "All" tab
		var starredMapsOnPage = $('#all .starred');
		for (var i = 0; i < starredMapsOnPage.length; i++) {
			var starredMapID = starredMapsOnPage[i].firstElementChild.getAttribute("data-id");
			if (starredMapID == mapID) {
				starredMapsOnPage[i].classList.remove("starred");
				starredMapsOnPage[i].removeChild($(starredMapsOnPage[i]).find('.star')[0]);
			}
		}
		// Removes the starred map from view
		var starredMaps = $('#starred .map');
		for (var i = 0; i < starredMaps.length; i++) {
			if (mapID == starredMaps[i].firstElementChild.getAttribute("data-id")) {
				starredMaps.splice(i, 1);
				var starredMapsNode = $('#starred .maps')[0];
				outputMaps(starredMaps, starredMapsNode); // This function is in mapDetails.js
			}
		}
	}
}