// This JavaScript file provides the logic for a user to search for a map by its location, description,
//	language, year, and dimensions.

// Runs when the window has finished loading
window.addEventListener('load', function() {
	// Places the current query into the relevant search fields
	if (location.pathname == "/search.php") {
		var parts = document.URL.split("?");
		var params = parts[1].split("&");
		var fields = $('#searchForm input');
		for (var i = 0; i < fields.length; i++) {
			var value = params[i].split("=")[1];
			fields[i].value = decodeURIComponent(value.replace(/\+/g, '%20'));
		}
	}

	// Sets the oninput for the fields
	$('#searchForm input').on("input", verifySearchHelper);
});


// Toggles the "disabled" attribute of the search button based on the current value of
//	the search fields
function verifySearchHelper() {
	var button = document.getElementById("submitSearch");
	if (!verifySearchInput(this)) {
		button.disabled = true;
	} else {
		button.disabled = false;
	}
}


// Verifies the current value of a field against a regular expression
// Parameters: field - The currently selected field DOM object
function verifySearchInput(field) {
	var fieldName = field.name;
	var expr;
	if (fieldName.indexOf("year") !== -1 || fieldName.indexOf("width") !== -1 || fieldName.indexOf("height") !== -1 ||
			fieldName == "language") {
		if (fieldName != "language") {
			expr = /\D/;
		} else {
			expr = /\d/;
		}
		if (expr.test(field.value) && field.value !== "") {
			field.classList.add("failInput");
			return false;
		} else {
			field.classList.remove("failInput");
		}
	}
	return true;
}