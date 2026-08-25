/**
 * Draw every resume page in full. Large off-screen canvases often stay
 * blank in Chromium, so each page is painted in short bands on a
 * CPU-backed canvas, then added to the page.
 */
(function () {
	"use strict";

	var started = false;
	var FONT_BASE = "https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/";
	var WORKER_CDN = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";
	var BAND = 800;

	function setStatus(root, message) {
		root.replaceChildren();
		var status = document.createElement("p");
		status.className = "resume-embed__status";
		status.textContent = message;
		root.appendChild(status);
	}

	function docOptions(extra) {
		var options = {
			cMapUrl: FONT_BASE + "cmaps/",
			cMapPacked: true,
			standardFontDataUrl: FONT_BASE + "standard_fonts/",
			useSystemFonts: true,
			disableRange: true,
			disableStream: true,
			disableAutoFetch: false,
			disableFontFace: false,
			verbosity: 0,
		};
		var key;

		for (key in extra) {
			if (Object.prototype.hasOwnProperty.call(extra, key)) {
				options[key] = extra[key];
			}
		}

		return options;
	}

	function flush(context) {
		try {
			context.getImageData(0, 0, 1, 1);
		} catch (error) {
			void error;
		}
	}

	function start() {
		if (started) {
			return;
		}

		var root = document.querySelector("[data-pdf-file], [data-pdf-url], [data-pdf-rest]");
		if (!root) {
			return;
		}

		if (typeof pdfjsLib === "undefined") {
			window.setTimeout(start, 80);
			return;
		}

		started = true;

		var config = window.stillframeResumePdf || {};
		var urls = [root.getAttribute("data-pdf-file"), root.getAttribute("data-pdf-rest"), root.getAttribute("data-pdf-url")].filter(Boolean);

		pdfjsLib.GlobalWorkerOptions.workerSrc = WORKER_CDN;

		function pageWidth() {
			return Math.max(root.clientWidth, Math.floor(root.getBoundingClientRect().width), 320);
		}

		function renderBand(page, viewport, offsetY, height) {
			var canvas = document.createElement("canvas");
			var context = canvas.getContext("2d", { willReadFrequently: true, alpha: false });

			canvas.width = Math.max(1, Math.floor(viewport.width));
			canvas.height = Math.max(1, Math.floor(height));
			canvas.className = "resume-embed__band";
			canvas.style.width = "100%";
			canvas.style.height = "auto";

			return page
				.render({
					canvasContext: context,
					viewport: viewport,
					transform: offsetY ? [1, 0, 0, 1, 0, -offsetY] : null,
					background: "#ffffff",
				})
				.promise.then(function () {
					flush(context);
					return canvas;
				});
		}

		function renderPage(page) {
			var base = page.getViewport({ scale: 1 });
			var viewport = page.getViewport({ scale: pageWidth() / base.width });
			var wrapper = document.createElement("div");
			var offsetY = 0;
			var chain = Promise.resolve();

			wrapper.className = "resume-embed__pdf-page";

			while (offsetY < viewport.height) {
				chain = chain.then(
					(function (y) {
						return function () {
							return renderBand(page, viewport, y, Math.min(BAND, viewport.height - y)).then(function (canvas) {
								wrapper.appendChild(canvas);
							});
						};
					})(offsetY)
				);
				offsetY += BAND;
			}

			return chain.then(function () {
				root.appendChild(wrapper);
			});
		}

		function renderPdf(pdf) {
			var chain = Promise.resolve();
			var pageNum;

			root.replaceChildren();

			for (pageNum = 1; pageNum <= pdf.numPages; pageNum += 1) {
				chain = chain.then(
					(function (n) {
						return function () {
							return pdf.getPage(n).then(renderPage);
						};
					})(pageNum)
				);
			}

			return chain;
		}

		function openPdf(buffer) {
			return pdfjsLib.getDocument(
				docOptions({
					data: buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer),
				})
			).promise.then(renderPdf);
		}

		function tryUrl(index, usedLocalWorker) {
			if (index >= urls.length) {
				if (!usedLocalWorker && config.workerSrc) {
					pdfjsLib.GlobalWorkerOptions.workerSrc = config.workerSrc;
					return tryUrl(0, true);
				}
				setStatus(root, "Could not load the resume.");
				return Promise.resolve();
			}

			return fetch(urls[index], { credentials: "same-origin", cache: "reload" })
				.then(function (response) {
					if (!response.ok) {
						throw new Error("pdf-http");
					}
					return response.arrayBuffer();
				})
				.then(openPdf)
				.catch(function () {
					return tryUrl(index + 1, usedLocalWorker);
				});
		}

		if (!urls.length) {
			setStatus(root, "Could not load the resume.");
			return;
		}

		window.requestAnimationFrame(function () {
			tryUrl(0, false);
		});
	}

	function boot() {
		window.requestAnimationFrame(function () {
			window.setTimeout(start, 80);
		});
	}

	if (document.readyState === "complete") {
		boot();
	} else {
		window.addEventListener("load", boot);
	}

	window.addEventListener("pageshow", function (event) {
		if (event.persisted) {
			started = false;
			boot();
		}
	});
})();
