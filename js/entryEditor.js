// This JavaScript file provides most of the functionality to entryEditor.php, by providing the logic
// for sending requests to add, edit, and delete map entries, as well as some other features.

// --------------------------------------------------------------------------------------------

// TODO LATER:
// - Add the ability to change a map's picture when editing
// - Module pattern
// - Write entirely in jQuery

// This function runs as soon as the window has finished loading.
window.addEventListener('load', function() {

	// Sets the onclick of the "Submit Map Entry" button
	document.getElementById("submitEntry").onclick = submitEntry;

	// Sets onclick of each "Edit" button
	var editButtons = document.getElementsByName("edit");
	for (var i = editButtons.length - 1; i >= 0; i--) {
		editButtons[i].onclick = editInfo;
	}

	// Sets the onlick to toggle the "Delete Selected" button
	var deleteChecks = document.getElementsByName("delete");
	for (var i = deleteChecks.length - 1; i >= 0; i--) {
		deleteChecks[i].onchange = checkToggle;
	}

	// Sets onclick for the "Delete Selected" button
	document.getElementById("deleteSelected").onclick = deleteSelected;
});

// This function allows a user to cancel editing an individual map entry.
function cancelEdit() {
	var overlay = document.getElementById("overlay");
	document.body.removeChild(overlay);
	var overlayArea = document.getElementById("overlayArea");
	document.body.removeChild(overlayArea);
	return false;
}

// Checks to see if any maps are selected to be deleted, then changes the display
//		of the "Delete Selected" button accordingly
function checkToggle() {
	if ($('input:checkbox:checked').length == 0) {
		document.getElementById("deleteSelected").style.display = "none";
	} else {
		document.getElementById("deleteSelected").style.display = "block";
	}
}

// This function will allow a user to edit an individual map entry.
function editInfo() {
	var fields = ["location", "description", "year", "language", "material", "width",
			"height", "scale", "backText", "notes", "room", "shelf", "quantity"];
	var parent = this.parentNode;
	var id = parent.getAttribute("value"); // parent.value won't work for some reason

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

	var http = new XMLHttpRequest();
	var url = "http://" + hostname + "/etc/fetchMap.php";
	http.open("GET", url + "?id=" + id, true);
	http.onreadystatechange = function() {
		if (http.readyState == 4) {
		 	if (http.status == 200) {
		 		// Add current values into textboxes
		 		var values = http.responseText.split("\n");
		 		var formContainer = document.createElement("div");
		 		formContainer.className = "container-fluid";
		 		var form = document.createElement("form");
		 		form.className = "form-horizontal";
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
					input.classList.add("form-control");
					if (fields[i] == "year" || fields[i] == "width" || fields[i] == "height"
							|| fields[i] == "room" || fields[i] == "shelf" || fields[i] == "quantity") {
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

// This function provides the logic for requesting to delete an entry.
function deleteSelected() {
	if (confirm("Are you sure you want to delete the selected maps?")) {
		var http = new XMLHttpRequest();
		var boxes = $('.adminMap input:checked');
		var url = "editorBackend.php";
		var params = "delete[]=";
		for (var i = boxes.length - 1; i >= 0; i--) {
			params = params + "&delete[]=" + boxes[i].value; // Must leave the brackets in
		}
		http.open("GET", url + "?" + params, true);
		http.onreadystatechange = function() {
			if (http.readyState == 4 && http.status == 200) {
				window.location = window.location;
			} else if (http.readyState == 4 && http.status == 304) {
				alert("Failed to delete maps. Please try again.");
			} else if (http.readyState == 4 && http.status == 500) {
				alert("Something went wrong on the server. Please try again.");
			}
		}
		http.send(null);
	}
	return false;
}

// This function provides the logic for requesting to edit an entry.
function submitEdit() {
	var submitButton = document.getElementById("submitButton");
	submitButton.disabled = true;
	var fields = $('#overlayArea').find("input");
	var id = $('#overlayArea').find("p")[0].textContent;
	var http = new XMLHttpRequest();
	var url = "editorBackend.php"
	var params = "id=" + id;
	for (var i = fields.length - 1; i >= 0; i--) {
		if (fields[i].name == "sold") {
			params = params + "&sold=" + (fields[i].checked ? 1 : 0);
		} else {
			params = params + "&" + fields[i].name + "=" + encodeURIComponent(fields[i].value);
		}
	}
	http.open("GET", url + "?" + params, true);
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
				alert("Please ensure the required fields are still filled out.");
				submitButton.disabled = false;
			}
		}
	}
	http.send(null);
	return false;
}

// This function provides the logic for requesting to add an entry.
function submitEntry() {
	document.getElementById("submitEntry").disabled = true; // Using "this" does not work properly
	document.getElementById("loading").style.display = "inline";
	var http = new XMLHttpRequest();
	var url = "editorBackend.php";
	http.open("POST", url, true);
	http.onreadystatechange = function() {
		if (http.readyState == 4) {
			document.getElementById("loading").style.display = "none";
			document.getElementById("submitEntry").disabled = false; // Using "this" does not work properly
			if (http.status == 201) {
				window.location = window.location;
			} else if (http.status == 400) {
				alert("Please ensure that you have filled out all fields and selected a file to upload");
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
	}
	var form = document.getElementById("newMapForm");
	var data = new FormData(form);
	http.send(data);
	return false;
}