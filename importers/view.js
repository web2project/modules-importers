function ToggleUserFields() {
	userFields = document.getElementsByName("userRelated");
	for (i=0; i<userFields.length; i++) {
		if (userFields[i].style.visibility == "hidden") {
			userFields[i].style.visibility = "visible";
		} else {
			userFields[i].style.visibility = "hidden";
		}
	}
}

function addNew_choice(selection) {
	var selValue = selection.options[selection.selectedIndex].text;
	return selValue == "Add New";
}

function valid(menu, txt) {
	if (txt.value == "") {
		if (addNew_choice(menu)) {
			alert("You need to type the user name to add into the text box");
			return false;
		} else {
			return true;
		}
	} else {
		if (!addNew_choice(menu)) {
			alert("Incompatible selection");
			return false;
		} else {
			return true;
		}
	}
}

function activateTxtFld(field) {
	field.style.visibility  = "visible";
	field.focus();
}

function process_input(textfield) {
	var parents = getParent(textfield, "tr");
	var selRow = (parents ? parents[0] : null);
	var selection = selRow.cells[1].children[0];
	adjust_task_users(selection.name.match(/\d+/)[0], textfield.value);
}

function process_choice(selection) {
	var parents = getParent(selection, "tr");
	var selRow = (parents ? parents[0] : null);
	var textfield = selRow.cells[2].children[0];

	if (addNew_choice(selection)) {
		if (typeof textfield != "undefined") {
			activateTxtFld(textfield);
		}
	} else {
		if (typeof textfield != "undefined") {
			textfield.style.visibility  = "hidden";
			textfield.value = "";
		}
		adjust_task_users(selection.name.match(/\d+/)[0], selection.options[selection.selectedIndex].text);
	}
}

function check_choice(textfield) {
	var parents = getParent(textfield, "tr");
	var selRow = (parents ? parents[0] : null);
	var menu = selRow.cells[1].children[0];

	if (!addNew_choice(menu)) {
		textfield.blur();
		alert("Please check your menu selection first");
		menu.focus();
	}
}

function adjust_task_users(uid, newText) {
	var selects = document.getElementsByTagName("select");

	if (selects) {
		for (var i=0; i < selects.length; i++) {
			var selName = selects[i].name;
			if (selName.search(/^tasks\[\d+\]\[resources\]\[\]/) != -1) {
				var taskuser = selects[i];
				for (var j=0; j < taskuser.options.length; j++){
					if (taskuser.options[j].value == uid) {
						taskuser.options[j].text = newText;
						break;
					}
				}
			}
		}
	}
}