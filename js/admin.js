// This Javascript file provides the front-end logic for displaying certain items and submitting
//	form data using AJAX.

var test;

// Runs when the window has finished loading
window.addEventListener('load', function() {
	// Stores the selected tab in the location hash
	$('#tabNav a').on('shown.bs.tab', function(e) {
		localStorage.setItem('lastTab', $(e.target).attr('href'));
	});

	// Switches to the last selected tab, if it exists
	var lastTab = localStorage.getItem('lastTab');
	if (lastTab) {
		$('a[href="' + lastTab + '"]').tab('show'); // Switches to tab
		
		// Removes and sets the 'active' class appropriately - prevents flashing the first page
		$('ul.nav-tabs').children().removeClass('active');
		$('a[href='+ lastTab +']').parents('li:first').addClass('active');
		$('div.tab-content').children().removeClass('active');
		$(lastTab).addClass('active');
	}

	// Sets the onclick of the "Submit Map Entry" button
	document.getElementById("submitEntry").onclick = submitEntry;

	// Sets the oninput for the fields when adding a map
	$('#newMapForm input').on("input", verifyAddInput);

	// Sets the onclick for the "Delete Selected" button
	document.getElementById("deleteSelected").onclick = deleteSelected;
});

// Overrides the setMapEvents function in MapDetails.js to also carry over the admin events
function setMapEvents() {
	// Sets the onclick of each "Edit" button
	var editButtons = document.getElementsByName("edit");
	for (var i = editButtons.length - 1; i >= 0; i--) {
		editButtons[i].onclick = editInfo;
	}

	// Sets the onlick to toggle the "Delete Selected" button
	var deleteChecks = document.getElementsByName("delete");
	for (var i = deleteChecks.length - 1; i >= 0; i--) {
		deleteChecks[i].onchange = checkToggle;
	}

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


// Allows a user to cancel editing an individual map entry
function cancelEdit() {
	var overlay = document.getElementById("overlay");
	document.body.removeChild(overlay);
	var overlayArea = document.getElementById("overlayArea");
	document.body.removeChild(overlayArea);
	return false;
}


// Checks to see if any maps are selected to be deleted, then changes the display
// of the "Delete Selected" button accordingly
function checkToggle() {
	if ($('input:checkbox:checked').length === 0) {
		document.getElementById("deleteSelected").style.display = "none";
	} else {
		document.getElementById("deleteSelected").style.display = "block";
	}
}


// Provides the logic for requesting to delete an entry
function deleteSelected() {
	if (confirm("Are you sure you want to delete the selected maps?")) {
		var http = new XMLHttpRequest();
		var url = "editBackend.php";
		http.open("POST", url, true);
		http.onreadystatechange = function() {
			if (http.readyState == 4) {
				if (http.status == 200) {
					window.location = window.location;
				} else if (http.status == 304) {
					alert("Failed to delete maps. Please try again.");
				} else if (http.status == 500) {
					alert("Something went wrong on the server. Please try again.");
				}
			}
		};
		var toDelete = $('.adminMap input:checked');
		var deleteString = toDelete[0].value;
		if (toDelete.length > 1) {
			for (var i = 1; i < toDelete.length; i++) {
				deleteString += "," + toDelete[i].value;
			}
		}
		var data = new FormData();
		data.append("delete", deleteString);
		http.send(data);
	}
	return false;
}


// Allow a user to edit an individual map entry
function editInfo() {
	var fields = ["location", "description", "year", "language", "material", "width",
			"height", "scale", "backText", "notes", "department", "room", "shelf", "quantity"];
	var parent = this.parentNode;
	var id = parent.getAttribute("data-id");

	// Create overlay and append to page
	var overlay = document.createElement("div");
	overlay.id = "overlay";
	document.body.appendChild(overlay);

	// Create editing area and append to page
	var overlayArea = document.createElement("div");
	overlayArea.id = "overlayArea";
	document.body.appendChild(overlayArea);

	// Add the ID of the map into a hidden paragraph to retrieve when submitting edit
	var idPar = document.createElement("p");
	idPar.id = "idPar";
	idPar.textContent = id;
	overlayArea.appendChild(idPar);

	// AJAX request
	var http = new XMLHttpRequest();
	var url = "//" + hostname + "/etc/fetchMap.php";
	http.open("GET", url + "?id=" + id, true);
	http.onreadystatechange = function() {
		if (http.readyState == 4) {
			if (http.status == 200) {
				// Add textboxes with current values
				var values = http.responseText.split("\n");
				var formContainer = document.createElement("div");
				formContainer.className = "container-fluid";
				var form = document.createElement("form");
				form.className = "form-horizontal";
				form.id = "editForm";
				for (var i = 0; i < fields.length; i++) {
					var formGroup = document.createElement("div");
					formGroup.className = "form-group";
					var label = document.createElement("label");
					formGroup.appendChild(label);
					label.classList.add("col-sm-2");
					label.classList.add("control-label");
					label.textContent = fields[i].charAt(0).toUpperCase() + fields[i].slice(1);
					var inputDiv = document.createElement("div");
					inputDiv.className = "col-sm-10";
					var input = document.createElement("input");
					$(input).on("input", verifyEditInput);
					input.classList.add("form-control");
					if (fields[i] == "year" || fields[i] == "width" || fields[i] == "height" ||
							fields[i] == "room" || fields[i] == "shelf" || fields[i] == "quantity") {
						input.classList.add("smallInput");
					}
					input.name = fields[i];
					var value = values[i].split("|")[1];
					input.value = value;
					inputDiv.appendChild(input);
					formGroup.appendChild(inputDiv);
					form.appendChild(formGroup);
				}

				// Add the "Sold" button
				var soldDiv = document.createElement("div");
				soldDiv.className = "form-group";
				var soldLabel = document.createElement("label");
				soldLabel.classList.add("col-sm-2");
				soldLabel.classList.add("control-label");
				soldLabel.id = "soldLabel";
				soldLabel.textContent = "Sold";
				soldDiv.appendChild(soldLabel);
				var soldBox = document.createElement("input");
				soldBox.type = "checkbox";
				soldBox.name = "sold";
				soldBox.className = "col-sm-1";
				soldBox.id = "soldBox";
				if ($(parent).parent().hasClass("sold")) {
					soldBox.checked = "checked";
				}
				soldDiv.appendChild(soldBox);
				form.appendChild(soldDiv);

				// Container for Submit/Cancel buttons
				var buttonGroup = document.createElement("div");
				buttonGroup.className = "form-group";

				// Add Submit button
				var submitButtonDiv = document.createElement("div");
				submitButtonDiv.classList.add("col-sm-1");
				submitButtonDiv.classList.add("col-sm-offset-4");
				submitButtonDiv.style.display = "inline";
				var submitButton = document.createElement("button");
				submitButton.classList.add("btn");
				submitButton.classList.add("btn-primary");
				submitButton.classList.add("btn-lg");
				submitButton.id = "submitEdit";
				submitButton.textContent = "Submit";
				submitButton.onclick = submitEdit;
				submitButtonDiv.appendChild(submitButton);

				// Add Cancel Button
				var cancelButtonDiv = document.createElement("div");
				cancelButtonDiv.classList.add("col-sm-1");
				cancelButtonDiv.classList.add("col-sm-offset-2");
				cancelButtonDiv.style.display = "inline";
				var cancelButton = document.createElement("button");
				cancelButton.classList.add("btn");
				cancelButton.classList.add("btn-danger");
				cancelButton.classList.add("btn-lg");
				cancelButton.id = "cancelEdit";
				cancelButton.textContent = "Cancel";
				cancelButton.onclick = cancelEdit;
				cancelButtonDiv.appendChild(cancelButton);

				// Add buttons
				buttonGroup.appendChild(submitButtonDiv);
				buttonGroup.appendChild(cancelButtonDiv);
				form.appendChild(buttonGroup);

				formContainer.appendChild(form);
				overlayArea.appendChild(formContainer);
			} else {
				cancelEdit();
				alert("Unable to edit map entry!");
			}
		}
	};
	http.send(null);
	return false;
}


// Provides the logic for requesting to edit a map
// Parameters: e - An event handler
function submitEdit(e) {
	e.preventDefault(); // Must prevent the default behavior here, for some reason
	var submitButton = document.getElementById("submitEdit");
	submitButton.disabled = true;
	var http = new XMLHttpRequest();
	var url = "editBackend.php";
	http.open("POST", url, true);
	http.onreadystatechange = function() {
		if (http.readyState == 4) {
			if (http.status == 200) {
				window.location = window.location;
			} else if (http.status == 304) {
				alert("Failed to edit the map entry.");
				submitButton.disabled = false;
			} else if (http.status == 500) {
				alert("Something went wrong on the server. Please try again.");
				submitButton.disabled = false;
			} else if (http.status == 400) {
				alert("Please ensure the data submitted is valid.");
				submitButton.disabled = false;
			}
		}
	};
	var form = document.getElementById("editForm");
	var data = new FormData(form);
	var id = $('#overlayArea').find("p")[0].textContent;
	data.append("sold", $('#soldBox').prop('checked') ? 1 : 0);
	data.append("id", id);
	http.send(data);
}


// Provides the logic for requesting to add a map
function submitEntry() {
	document.getElementById("submitEntry").disabled = true; // Using "this" does not work properly
	var http = new XMLHttpRequest();
	var url = "addBackend.php";
	http.open("POST", url, true);
	http.onreadystatechange = function() {
		if (http.readyState == 4) {
			document.getElementById("submitEntry").disabled = false; // Using "this" does not work properly
			if (http.status == 201) {
				window.location = window.location;
			} else if (http.status == 400) {
				alert("Please ensure that the data submitted is valid");
			} else if (http.status == 304) {
				alert("Failed to add new map entry.");
			} else if (http.status == 500) {
				alert("Something went wrong on the server. Please try again.");
			} else if (http.status == 206) {
				var text = http.responseText;
				var parts = text.split(" ");
				alert("Map entry already exists. It has an ID of " + parts[0] + " in room " + parts[1] + " on shelf " + parts[2] + ".");
			}
		}
	};
	var form = document.getElementById("newMapForm");
	var data = new FormData(form);
	http.send(data);
	return false;
}


// This function verifies each input when adding fields.
function verifyAddInput() {
	var button = document.getElementById("submitEntry");
	if (!verifyInput(this)) {
		button.disabled = true;
	} else {
		button.disabled = false;
	}
}


// This function verifies each input when editing fields
function verifyEditInput() {
	var button = document.getElementById("submitEdit");
	if (!verifyInput(this)) {
		button.disabled = true;
	} else {
		button.disabled = false;
	}
}


// The main field verification function; verifies a field's value against a regular expression.
//	The fields are also verified in the backend, but this provides a first step in ensuring valid
//	data is submitted.
// Parameters: field - A field DOM object
// Returns: true - If the field passes
//			false - If the field fails
function verifyInput(field) {
	var fieldName = field.name;
	var pipeExpr = /[|]/; // Because pipes are used to split the field name and value in fetchMap.php
	if (pipeExpr.test(field.value)) {
		field.classList.add("failInput");
		return false;
	} else {
		field.classList.remove("failInput");
	}

	// Checks that required fields are filled out
	if (fieldName == "location" || fieldName == "description" || fieldName == "year" || fieldName == "width" ||
			fieldName == "height" || fieldName == "backText" || fieldName == "shelf") {
		if (field.value === "") {
			field.classList.add("failInput");
			return false;
		} else {
			field.classList.remove("failInput");
		}
	}

	// Checks that numeric fields contain only numeric values
	if (fieldName == "year" || fieldName == "width" || fieldName == "height" || fieldName == "quantity") {
		var numberExpr = /\D/;
		if (numberExpr.test(field.value) && field.value !== "") {
			field.classList.add("failInput");
			return false;
		} else {
			field.classList.remove("failInput");
		}
	}
	return true;
}
