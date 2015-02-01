// This JavaScript file provides much of the functionality on pages where maps are viewed.

// NOTE 1: Will be set to -1 if "a" is comes before "b", 1 if "a" is comes after "b", and 0 if equal
// NOTE 2: In an effort to pass HTML validation, I had to remove the "name" attribute from each paragraph within
//			each map's information. This meant I had to rewrite dynamicSort and hideNoYear to be able to find each
//			paragraph again. You can reference the paragraphs by class name using jQuery, but this results in slow
//			sorting. Hence, the hard-coded sort fields and indices.

var noYearMaps = []; // Used for storing maps with no year listed, so they can be shown when option is untoggled
var allMaps = []; // Used for storing all maps when sorting by department
var openMap; // Stores the map DOM object of the map whose details are currently being viewed

// Runs when the window has finished loading
window.addEventListener('load', function() {
	// Sets the onclick of the tabbed navigation
	$('#tabNav a').click(function(e) {
		e.preventDefault();
		$(this).tab('show');
	});

	setMapEvents();

	// Sets the onchange for the Order By controls
	var orderBySelect = $('.orderBySelect');
	var ascendingSelect = $('.ascendingSelect');
	var descendingSelect = $('.descendingSelect');
	var hideNoYearBox = $('.hideNoYearBox');
	for (var i = 0; i < orderBySelect.length; i++) { // ALL OF THE ABOVE SHOULD HAVE THE SAME LENGTH
		orderBySelect[i].onchange = changeSort;
		ascendingSelect[i].onchange = changeOrder;
		descendingSelect[i].onchange = changeOrder;
		hideNoYearBox[i].onchange = hideNoYear;
	}
	var departmentSort = document.getElementsByName("dept");
	for (var i = 0; i < departmentSort.length; i++) {
		departmentSort[i].onchange = changeDepartment;
	}

	var maps = $('.map');
	for (var i = 0; i < maps.length; i++) {
		allMaps.push($(maps[i]).clone().get(0));
	}
});

function setMapEvents() {
 	// Sets the onclick of each map picture
	var mapPics = $('.mapPic');
	for (var i = mapPics.length - 1; i >= 0; i--) {
		mapPics[i].onclick = showMapDetails;
	}

	// Sets the onclick of each star
	var starred = $('.star');
	for (var i = 0; i < starred.length; i++) {
		starred[i].onclick = starMap;
	}

	// Sets the onhover of each map
	var maps = $('.map');
	for (var i = 0; i < maps.length; i++) {
		maps[i].onmouseenter = outlineMap;
		maps[i].onmouseleave = removeOutlineMap;
	}
}

// Changes how the maps are sorted
function changeSort() {
	var controlsNode = this.parentNode;
	var orderBy = controlsNode.children[0];
	var descendingSelect = controlsNode.children[2];
	var sortBy = orderBy.value.toLowerCase();
	hideYearControl(sortBy, controlsNode);
	if (descendingSelect.firstElementChild.checked) {
		sortBy = "-" + sortBy;
	}
	var maps = $(controlsNode).parent().parent().find('.map');
	var sortedMaps = maps.sort(dynamicSort(sortBy));
	var mapsNode = $(controlsNode.parentNode.parentNode).find('.maps')[0];
	outputMaps(sortedMaps, mapsNode);
}


// Changes the star displayed on a map's tile
// Parameters:	starring - a boolean that if true, means the map is being starred
//				star - the star's DOM object
//				map - the map's DOM object
function changeStar(starring, star, map) {
	if (starring) {
		star.classList.remove("glyphicon-star-empty");
		star.classList.add("glyphicon-star");
		star.id = "";
		map.classList.add("starred");
	} else {
		star.classList.add("glyphicon-star-empty");
		star.classList.remove("glyphicon-star");
		star.id = "hoverStar";
		map.classList.remove("starred");
	}
}


// Changes whether the maps appear in ascending or descending order
function changeOrder() {
	var controlsNode = this.parentNode.parentNode;
	var maps = $(controlsNode).parent().parent().find('.map');
	var orderedMaps = [];
	for (var i = 0; i < maps.length; i++) {
		orderedMaps[maps.length - 1 - i] = maps[i];
	}
	var mapsNode = $(controlsNode.parentNode.parentNode).find('.maps')[0];
	outputMaps(orderedMaps, mapsNode);
}


function changeDepartment() {
	var controlsNode = this.parentNode.parentNode;
	var mapsNode = $(controlsNode.parentNode.parentNode).find('.maps')[0];
	if (this.value === "all") {
		outputMaps(allMaps, mapsNode);
	} else {
		var mapsToOutput = [];
		if (this.value === "geog") {
			for (var i = 0; i < allMaps.length; i++) {
				if (allMaps[i].children[0].getAttribute("data-dept") === "Geography") {
					mapsToOutput.push(allMaps[i]);
				}
			}
		} else if (this.value === "hist") {
			for (var i = 0; i < allMaps.length; i++) {
				if (allMaps[i].children[0].getAttribute("data-dept") === "History") {
					mapsToOutput.push(allMaps[i]);
				}
			}
		}
		outputMaps(mapsToOutput, mapsNode);
	}
}

// Checks if a map is currently starred
// Parameters: mapID - the ID of the map to check
// Returns: true - if the map is currently starred
//			false - if the map is not currently starred
//			null - if the starredMaps cookie doesn't exist
function checkStarred(mapID) {
	if (typeof $.cookie('starredMaps') === "undefined") {
		return null;
	} else {
		return (($.cookie('starredMaps').split(",").indexOf(mapID) != -1) ? true : false);
	}
}


// Closes the map details overlay
function closeMapDetails() {
	openMap = null;
	var overlay = document.getElementById("overlay");
	document.body.removeChild(overlay);
	var overlayArea = document.getElementById("overlayArea");
	document.body.removeChild(overlayArea);
	return false;
}


// The sorting function for changeSort(). See Note 2.
// Parameters: property - The property to sort the maps by (location, description, etc.)
// Returns: -1 - if the current element is alphabetically/numerically less than the next element
//			0 - if the current element is alphabetically/numerically equal to the next element
//			1 - if the current element is alphabetically/numerically more than the next element
function dynamicSort(property) {
	var sortOrder = 1;
	if (property[0] === "-") {
		sortOrder = -1;
		property = property.substr(1);
	}
	var sortByFields = ["location", "description", "year", "language"];
	var index = sortByFields.indexOf(property);
	return (function(a, b) {
		var aText = a.firstElementChild.children[index + 1].textContent; // Using + 1 because picture is first
		var bText = b.firstElementChild.children[index + 1].textContent; // Using + 1 because picture is first
		var result = (aText < bText) ? -1 : ((aText > bText) ? 1 : 0); // See note #1
		return result * sortOrder;
	});
}


// Provides the functionality for hiding/showing maps with no year listed
function hideNoYear() {
	var controlsNode = this.parentNode.parentNode;
	var maps = $(controlsNode).parent().parent().find('.map');
	var mapsNode = $(controlsNode.parentNode.parentNode).find('.maps')[0];
	var mapsToOutput = [];
	if (this.checked) { // Hide maps
		for (var i = 0; i < maps.length; i++) {
			var year = maps[i].firstElementChild.children[3].textContent;
			if (year != "No year listed") {
				mapsToOutput.push(maps[i]);
			} else {
				noYearMaps.push(maps[i]);
			}
		}
	} else { // Unhide maps
		if (controlsNode.children[2].firstElementChild.checked) { // If in descending order
			for (var i = 0; i < noYearMaps.length; i++) {
				mapsToOutput.push(noYearMaps[i]);
			}
			for (var i = 0; i < maps.length; i++) {
				mapsToOutput.push(maps[i]);
			}
		} else {
			mapsToOutput = maps;
			for (var i = 0; i < noYearMaps.length; i++) {
				mapsToOutput.push(noYearMaps[i]);
			}
		}
		noYearMaps = [];
	}
	outputMaps(mapsToOutput, mapsNode);
}


// Hides the "Hide maps with no year listed" checkbox if not sorting by year
// Parameters:	selectedSort - all-lowercase string representation of the selected sort value
//				controlsNode - the node that contains the sorting/hide controls
function hideYearControl(selectedSort, controlsNode) {
	var hideNoYearLabel = controlsNode.children[3];
	if (selectedSort == "year") {
		hideNoYearLabel.style.display = "inline-block";
	} else {
		hideNoYearLabel.style.display = "none";
	}
}


// Outlines the map and adds a toggleable star
function outlineMap() {
	this.style.border = "1px solid #bbb";
	this.style.borderRadius = "4px";
	var star;
	if (!this.classList.contains("starred")) {
		star = document.createElement("span");
		star.id = "hoverStar";
		var id = this.firstElementChild.getAttribute("data-id");
		star.setAttribute("data-id", id);
		star.classList.add("star");
		star.classList.add("glyphicon");
		star.classList.add("glyphicon-star-empty");
		star.onclick = starMap;
		this.appendChild(star);
	} else {
		star = $(this).find('.star')[0];
		star.onclick = starMap;
	}
}


// Removes the outline when moving away from a map, and removes the star if the map is not starred
function removeOutlineMap() {
	this.style.border = "1px solid #fff";
	if (!this.classList.contains("starred")) {
		var star = document.getElementById("hoverStar");
		this.removeChild(star);
	}
}


// Outputs maps, adding clearfixes where necessary, and updates the number of results shown
// Parameters:	maps - HTMLCollection of maps
//				mapsNode - the node where the maps will be placed into
function outputMaps(maps, mapsNode) {
	mapsNode.innerHTML = "";
	var count = mapsNode.parentNode.children[0].children[3].firstElementChild;
	if (maps.length === 0) {
		count.textContent = "0 results shown";
		mapsNode.textContent = "No maps to display";
	} else if (maps.length == 1) {
		count.textContent = "1 result shown";
		mapsNode.appendChild(maps[0]);
	} else {
		count.textContent = maps.length + " results shown";
		for (var i = 1; i <= maps.length; i++) { // Starting at 1 to insert clearfixes properly
			mapsNode.appendChild(maps[i - 1]);
			if (i % 2 === 0 || i % 3 === 0) {
				var clearfix = document.createElement("div");
				clearfix.classList.add("clearfix");
				if (i % 2 === 0) {
					clearfix.classList.add("visible-sm-block");
				} else if (i % 3 === 0) {
					clearfix.classList.add("visible-md-block");
				} else if (i % 4 === 0) {
					clearfix.classList.add("visible-lg-block");
				}
				mapsNode.appendChild(clearfix);
			}
		}
	}
	setMapEvents();
}


// Shows the map details overlay
function showMapDetails() {
	var mapInfo = this.parentNode;
	var id = mapInfo.getAttribute("data-id");
	var map = mapInfo.parentNode;
	openMap = map;

	// Create transparent overlay and append to page
	var overlay = document.createElement("div");
	overlay.id = "overlay";
	document.body.appendChild(overlay);

	// Create map details area and append to page
	var overlayArea = document.createElement("div");
	overlayArea.id = "overlayArea";
	document.body.appendChild(overlayArea);

	// User can press the escape key to close the details
	document.onkeydown = function(e) {
		if (e.keyCode == 27) { // 27 is the key code for the escape key
			closeMapDetails();
		}
	};

	// AJAX stuff
	var http = new XMLHttpRequest();
	var url = "//" + hostname + "/etc/fetchMap.php";
	http.open("GET", url + "?id=" + id, true);
	http.onreadystatechange = function() {
		if (http.readyState == 4) {
			if (http.status == 200) {
				// Create and append Close button
				var closeButton = document.createElement("button");
				closeButton.className = "close";
				var closeAriaSpan = document.createElement("span");
				closeAriaSpan.setAttribute("aria-hidden", true);
				closeAriaSpan.innerHTML = "&times;";
				closeButton.appendChild(closeAriaSpan);
				var srOnlySpan = document.createElement("span");
				srOnlySpan.className = "sr-only";
				srOnlySpan.textContent = "Close";
				closeButton.appendChild(srOnlySpan);
				closeButton.onclick	= closeMapDetails;
				overlayArea.appendChild(closeButton);

				// Create and append the Star button
				var starButton = document.createElement("button");
				starButton.onclick = starMap;
				starButton.id = "starButton";
				starButton.value = id;
				starButton.classList.add("btn");
				starButton.classList.add("btn-sm");
				starButton.textContent = "Star ";
				var starIcon = document.createElement("span");
				starIcon.id = "starIcon";
				starIcon.classList.add("glyphicon");
				if (checkStarred(id)) {
					starIcon.classList.add("glyphicon-star");
					starButton.classList.add("btn-success");
				} else {
					starIcon.classList.add("glyphicon-star-empty");
					starButton.classList.add("btn-primary");
				}
				starButton.appendChild(starIcon);
				overlayArea.appendChild(starButton);

				// Creates the container for the map's details + image
				var container = document.createElement("div");
				container.className = "container-fluid";

				// Gets the map details
				var details = http.responseText.split("\n");

				// Appends the full map picture
				var img = document.createElement("img");
				img.className = "img-rounded";
				img.id = "fullMapPic";
				var picSrc = "//" + hostname + "/mapPics/" + details[details.length - 1];
				img.src = picSrc;
				img.alt = "Full map picture";
				container.appendChild(img);

				// Creates the container for the map's details
				var detailsContainer = document.createElement("div");
				detailsContainer.className = "container-fluid";
				detailsContainer.id = "details";
				// Because the picture is last, we go to one less than the length of the details array
				for (var i = 0; i < details.length - 1; i++) {
					// Separate each line into its title and the detail itself
					var rowTitle = details[i].split("|")[0];
					var detail = details[i].split("|")[1];
					if (detail !== "") {
						// Create and append each row
						var rowContainer = document.createElement("div");
						rowContainer.className = "container-fluid";
						var row = document.createElement("div");
						row.className = "row";
						rowContainer.appendChild(row);

						// Create and append each label
						var label = document.createElement("label");
						label.className = "col-sm-3";
						label.textContent = "Text on back";
						label.textContent = rowTitle;
						row.appendChild(label);
						
						// Create and append each detail
						var item = document.createElement("div");
						item.className = "col-sm-9";
						if (rowTitle == "Year" && detail == 0) {
							item.textContent = "No year listed";
						} else {
							item.textContent = detail;
						}
						if (rowTitle == "Width" || rowTitle == "Height") {
							item.textContent += " inches";
						}
						row.appendChild(item);
						detailsContainer.appendChild(rowContainer);
					}
				}
				overlayArea.appendChild(detailsContainer);
				overlayArea.appendChild(container);
			} else {
				closeMapDetails();
				alert("Failed to retrieve map details");
			}
		}
	};
	http.send(null);
}


// Provides the functionality for starring a map
function starMap() {
	var mapID;
	var map;
	var star;
	var starIcon;
	var starButton;

	if (this.tagName == "SPAN") { // Starred via hovering on map
		mapID = this.getAttribute("data-id");
		map = this.parentNode;
		star = this;
	} else { // Starred via opening map details
		mapID = this.value;
		map = openMap;
		starIcon = document.getElementById("starIcon");
		starButton = document.getElementById("starButton");
	}
	
	// Change cookie details
	if (checkStarred(mapID) === null) {
		$.cookie('starredMaps', "", {expires: 365, path: '/'});
	}
	var starredMaps;
	if ($.cookie('starredMaps') === "") {
		starredMaps = [];
	} else {
		starredMaps = $.cookie('starredMaps').split(",");
	}

	if (!checkStarred(mapID)) { // Starring a map
		starredMaps.push(mapID);
		map.classList.add("starred");
		if (this.tagName != "SPAN") {
			star = document.createElement("span");
			star.classList.add("glyphicon");
			star.classList.add("star");
			star.setAttribute("data-id", mapID);
			map.appendChild(star);
			starIcon.classList.remove("glyphicon-star-empty");
			starIcon.classList.add("glyphicon-star");
			starButton.classList.remove("btn-primary");
			starButton.classList.add("btn-success");
		}
		changeStar(true, star, map);
	} else { // Unstarring a map
		if (window.location.pathname == "/" || window.location.pathname == "/index.php") {
			removeStar(mapID); // This function is found in index.js
		}
		var mapIDIndex = starredMaps.indexOf(mapID);
		starredMaps.splice(mapIDIndex, 1);
		map.classList.remove("starred");
		if (this.tagName != "SPAN") {
			star = $(map).find('.star')[0];
			map.removeChild(star);
			starIcon.classList.remove("glyphicon-star");
			starIcon.classList.add("glyphicon-star-empty");
			starButton.classList.remove("btn-success");
			starButton.classList.add("btn-primary");
		} else {
			changeStar(false, star, map);
		}
	}
	var starredMapsString = starredMaps.join();
	$.cookie('starredMaps', starredMapsString, {expires: 365, path: '/'});
}