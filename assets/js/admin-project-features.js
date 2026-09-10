/**
 * Add, remove, and pick photos for project feature rows.
 */
(function ($) {
	"use strict";

	$(document).on("click", "[data-feature-add]", function (event) {
		event.preventDefault();

		var template = document.querySelector("[data-feature-template]");
		var list = document.querySelector("[data-feature-list]");
		if (!template || !list) {
			return;
		}

		list.insertAdjacentHTML("beforeend", template.innerHTML);
	});

	$(document).on("click", "[data-feature-remove]", function (event) {
		event.preventDefault();
		$(this).closest("[data-feature-row]").remove();
	});

	$(document).on("click", "[data-feature-upload]", function (event) {
		event.preventDefault();

		var row = $(this).closest("[data-feature-row]");
		var frame = wp.media({
			title: "Feature photo",
			button: { text: "Use this image" },
			multiple: false,
			library: { type: "image" },
		});

		frame.on("select", function () {
			var file = frame.state().get("selection").first().toJSON();
			var url = file.url;
			if (file.sizes && file.sizes.medium && file.sizes.medium.url) {
				url = file.sizes.medium.url;
			}
			row.find("[data-feature-image-id]").val(file.id);
			row.find("[data-feature-preview]").html(
				'<img src="' +
					url +
					'" alt="" style="display:block;max-width:180px;height:auto;margin:0 0 8px;" />'
			);
			row.find("[data-feature-image-remove]").prop("hidden", false);
		});

		frame.open();
	});

	$(document).on("click", "[data-feature-image-remove]", function (event) {
		event.preventDefault();
		var row = $(this).closest("[data-feature-row]");
		row.find("[data-feature-image-id]").val("");
		row.find("[data-feature-preview]").empty();
		$(this).prop("hidden", true);
	});
})(jQuery);
