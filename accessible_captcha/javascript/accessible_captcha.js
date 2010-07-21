$(".settings_pair").append('<span id="add_pair">+</span>');
$("#add_pair").click(function() {
	$("#add_pair").remove();
	$(".settings_pair").append('<tr class="even"><td><textarea name="question[]" rows="1" cols="30">{{ lang.warning_question }}</textarea></td><td><textarea name="answer[]" rows="1" cols="30">{{ lang.warning_answer }}</textarea></td></tr>');
	$(".settings_pair").append('<span id="add_pair">+</span>');
});
