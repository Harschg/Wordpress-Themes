(function () {
	function isMediaPreviewUrl(url) {
		if (!url) {
			return false;
		}

		return (
			/attachment_id=/.test(url) ||
			/\/attachment\//.test(url) ||
			/\/wp-content\/uploads\//.test(url) ||
			/\.(jpe?g|png|gif|webp|avif|pdf)(\?|#|$)/i.test(url)
		);
	}

	wp.customize.bind("ready", function () {
		var previewer = wp.customize.previewer;
		if (!previewer || !previewer.previewUrl) {
			return;
		}

		var lastGood = previewer.previewUrl.get();

		previewer.previewUrl.bind(function (url) {
			if (isMediaPreviewUrl(url)) {
				previewer.previewUrl.set(lastGood);
				return;
			}

			lastGood = url;
		});
	});
})();
