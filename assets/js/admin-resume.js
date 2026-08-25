/**
 * Resume PDF picker on page edit screens.
 */
(function ($) {
	"use strict";

	$(document).on("click", "[data-resume-upload]", function (event) {
		event.preventDefault();

		var frame = wp.media({
			title: "Resume",
			button: { text: "Use this file" },
			multiple: false,
			library: { type: "application/pdf" },
		});

		frame.on("select", function () {
			var file = frame.state().get("selection").first().toJSON();
			$("#stillframe_resume_id").val(file.id);
			$("[data-resume-filename]").text(file.filename || file.title);
			$("[data-resume-remove]").prop("hidden", false);
		});

		frame.open();
	});

	$(document).on("click", "[data-resume-remove]", function (event) {
		event.preventDefault();
		$("#stillframe_resume_id").val("");
		$("[data-resume-filename]").text("No file yet.");
		$(this).prop("hidden", true);
	});
})(jQuery);
