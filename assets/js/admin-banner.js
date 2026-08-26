/**
 * Page banner picker on page edit screens.
 */
(function ($) {
	"use strict";

	function getPostId() {
		var fromConfig = window.stillframeBanner && parseInt(stillframeBanner.postId, 10);
		if (fromConfig) {
			return fromConfig;
		}

		var field = document.getElementById("post_ID");
		if (field && field.value) {
			return parseInt(field.value, 10) || 0;
		}

		if (typeof wp !== "undefined" && wp.data && wp.data.select) {
			try {
				return parseInt(wp.data.select("core/editor").getCurrentPostId(), 10) || 0;
			} catch (error) {
				return 0;
			}
		}

		return 0;
	}

	function setStatus(message) {
		$("[data-banner-status]").text(message || "");
	}

	function persistMeta(imageId) {
		var postId = getPostId();
		var config = window.stillframeBanner || {};

		if (!postId || !config.ajaxUrl) {
			setStatus("Save the page once, then choose the image again.");
			return;
		}

		$.post(config.ajaxUrl, {
			action: "stillframe_save_banner",
			nonce: config.nonce,
			post_id: postId,
			banner_id: imageId || 0,
		})
			.done(function (response) {
				if (response && response.success) {
					setStatus(imageId ? "Saved. View the page to see the banner." : "Removed.");
					if (response.data && typeof response.data.thumb !== "undefined") {
						$("[data-banner-preview]").html(response.data.thumb || "");
					}
				} else {
					setStatus("Could not save. Click Update, then try again.");
				}
			})
			.fail(function () {
				setStatus("Could not save. Click Update, then try again.");
			});
	}

	$(document).on("click", "[data-banner-upload]", function (event) {
		event.preventDefault();

		var frame = wp.media({
			title: "Page banner",
			button: { text: "Use this image" },
			multiple: false,
			library: { type: "image" },
		});

		frame.on("select", function () {
			var file = frame.state().get("selection").first().toJSON();
			$("#stillframe_banner_id").val(file.id);
			$("[data-banner-remove]").prop("hidden", false);
			setStatus("Saving…");
			persistMeta(file.id);
		});

		frame.open();
	});

	$(document).on("click", "[data-banner-remove]", function (event) {
		event.preventDefault();
		$("#stillframe_banner_id").val("");
		$("[data-banner-preview]").empty();
		$(this).prop("hidden", true);
		setStatus("Saving…");
		persistMeta(0);
	});
})(jQuery);
