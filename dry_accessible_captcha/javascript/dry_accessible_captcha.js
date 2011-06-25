function removeTableRow() {
	if ($(".settings_pair").children("tr").size() == 1) {
		var warning = $("#warning");
		if (warning.length == 0) {
			$(".settings_pair").parent("table").before('<div id="warning">' + AC.lang_warning + '</div>');
			$("#warning").fadeTo(3000, 0.5);
		} else {
			$("#warning").fadeTo(0, 1).fadeTo(3000, 0.5);
		}
	}
	else {
		var controls = $("#controls").detach();
		var removeThis = $(this).parents("tr");
		removeThis.remove();
		$(".settings_pair").parent("table").after(controls);
	}
	
}

$("#message_success").fadeTo(3000, 0.5);

$(".remove_pair").bind('click', removeTableRow);

$("#add_pair").click(function() {
	var warning = $("#warning");
	if (warning.length > 0) {
		$("#warning").remove();
	}
	var controls = $("#controls").detach();
	$(".settings_pair").append('<tr class="even"><td><textarea name="questions[]" rows="1" cols="30"></textarea></td><td><textarea name="answers[]" rows="1" cols="30"></textarea></td><td class="clean"><span class="remove_pair">&ndash;</span></td></tr>');
	$(".settings_pair").parent("table").after(controls);
	$(".remove_pair").unbind().bind('click', removeTableRow);
});
