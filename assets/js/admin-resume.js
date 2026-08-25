/**
 * Resume file picker on page edit screens.
 */
(function ($) {
	"use strict";

	function getPostId() {
		var fromConfig = window.stillframeResume && parseInt(stillframeResume.postId, 10);
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
		$("[data-resume-status]").text(message || "");
	}

	function persistMeta(fileId) {
		if (typeof wp !== "undefined" && wp.data && wp.data.dispatch) {
			try {
				wp.data.dispatch("core/editor").editPost({
					meta: { stillframe_resume_id: fileId || 0 },
				});
			} catch (error) {
				// Classic editor has no block-editor store.
			}
		}

		var postId = getPostId();
		var config = window.stillframeResume || {};

		if (!postId || !config.ajaxUrl) {
			setStatus("Save the page once, then choose the file again.");
			return;
		}

		$.post(config.ajaxUrl, {
			action: "stillframe_save_resume",
			nonce: config.nonce,
			post_id: postId,
			resume_id: fileId || 0,
		})
			.done(function (response) {
				if (response && response.success) {
					setStatus(fileId ? "Saved. View About to see it on the page." : "Removed.");
				} else {
					setStatus("Could not save. Click Update, then try again.");
				}
			})
			.fail(function () {
				setStatus("Could not save. Click Update, then try again.");
			});
	}

	$(document).on("click", "[data-resume-upload]", function (event) {
		event.preventDefault();

		var frame = wp.media({
			title: "Resume",
			button: { text: "Use this file" },
			multiple: false,
		});

		frame.on("select", function () {
			var file = frame.state().get("selection").first().toJSON();
			$("#stillframe_resume_id").val(file.id);
			$("[data-resume-filename]").text(file.filename || file.title);
			$("[data-resume-remove]").prop("hidden", false);
			setStatus("Saving…");
			persistMeta(file.id);
		});

		frame.open();
	});

	$(document).on("click", "[data-resume-remove]", function (event) {
		event.preventDefault();
		$("#stillframe_resume_id").val("");
		$("[data-resume-filename]").text("No file yet.");
		$(this).prop("hidden", true);
		setStatus("Saving…");
		persistMeta(0);
	});
})(jQuery);
