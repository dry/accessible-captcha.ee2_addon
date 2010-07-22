$("#add_pair").click(function() {
	var warning = $("#warning");
	if (warning.length > 0) {
		$("#warning").remove();
	}
	var controls = $("#controls").detach();
	$(".settings_pair").append('<tr class="even"><td><textarea name="question[]" rows="1" cols="30"></textarea></td><td><textarea name="answer[]" rows="1" cols="30"></textarea></td></tr>');
	$(".settings_pair").parent("table").append(controls);
});

$("#remove_pair").click(function() {
	if ($(".settings_pair").children().size() == 1) {
		var warning = $("#warning");
		if (warning.length == 0) {
			$(".settings_pair").parent("table").before('<div id="warning">' + AC.lang_warning + '</div>');
		}
	}
	else {
		var controls = $("#controls").detach();
		$(".settings_pair tr:last-child").remove();
		$(".settings_pair").parent("table").append(controls);
	}
	
});