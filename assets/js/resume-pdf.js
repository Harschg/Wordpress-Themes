/**
 * Draw the full resume PDF onto white canvases.
 */
(function () {
	"use strict";

	var root = document.querySelector("[data-pdf-url], [data-pdf-file]");
	if (!root) {
		return;
	}

	function setStatus(message) {
		root.replaceChildren();
		var status = document.createElement("p");
		status.className = "resume-embed__status";
		status.textContent = message;
		root.appendChild(status);
	}

	if (typeof pdfjsLib === "undefined") {
		setStatus("Could not load the resume.");
		return;
	}

	var urls = [];
	var fileUrl = root.getAttribute("data-pdf-file");
	var proxyUrl = root.getAttribute("data-pdf-url");
	var restUrl = root.getAttribute("data-pdf-rest");
	var config = window.stillframeResumePdf || {};
	var workerSrc = config.workerSrc;
	var workerCdn = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";

	if (fileUrl) {
		urls.push(fileUrl);
	}
	if (restUrl) {
		urls.push(restUrl);
	}
	if (proxyUrl) {
		urls.push(proxyUrl);
	}

	if (!urls.length) {
		setStatus("Could not load the resume.");
		return;
	}

	function setWorker(src) {
		pdfjsLib.GlobalWorkerOptions.workerSrc = src || workerCdn;
	}

	setWorker(workerSrc || workerCdn);

	function pageWidth() {
		return Math.max(root.clientWidth, Math.floor(root.getBoundingClientRect().width), 320);
	}

	function renderPage(page) {
		var base = page.getViewport({ scale: 1 });
		var outputScale = window.devicePixelRatio || 1;
		var viewport = page.getViewport({ scale: pageWidth() / base.width });
		var canvas = document.createElement("canvas");
		var context = canvas.getContext("2d");
		var transform = outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null;

		canvas.className = "resume-embed__page";
		canvas.width = Math.floor(viewport.width * outputScale);
		canvas.height = Math.floor(viewport.height * outputScale);
		canvas.style.width = "100%";
		canvas.style.height = "auto";
		context.fillStyle = "#ffffff";
		context.fillRect(0, 0, canvas.width, canvas.height);
		root.appendChild(canvas);

		return page.render({
			canvasContext: context,
			viewport: viewport,
			transform: transform,
			background: "#ffffff",
		}).promise;
	}

	function renderPdf(pdf) {
		root.replaceChildren();
		var chain = Promise.resolve();
		var pageNum;

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

	function loadFromData(buffer) {
		return pdfjsLib.getDocument({
			data: buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer),
			disableRange: true,
			disableStream: true,
			disableAutoFetch: true,
		}).promise.then(renderPdf);
	}

	function loadFromUrl(url) {
		return pdfjsLib
			.getDocument({
				url: url,
				withCredentials: false,
				disableRange: true,
				disableStream: true,
				disableAutoFetch: true,
			})
			.promise.then(renderPdf)
			.catch(function () {
				return fetch(url, { credentials: "same-origin", cache: "no-store" }).then(function (response) {
					if (!response.ok) {
						throw new Error("pdf-http");
					}
					return response.arrayBuffer();
				}).then(loadFromData);
			});
	}

	function tryUrls(index) {
		if (index >= urls.length) {
			if (workerSrc && pdfjsLib.GlobalWorkerOptions.workerSrc !== workerCdn) {
				setWorker(workerCdn);
				return tryUrls(0);
			}
			setStatus("Could not load the resume.");
			return Promise.resolve();
		}

		return loadFromUrl(urls[index]).catch(function () {
			return tryUrls(index + 1);
		});
	}

	tryUrls(0);
})();
