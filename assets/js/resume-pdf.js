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

	function config() {
		return window.stillframeResumePdf || {};
	}

	function slugify(value) {
		return String(value || "")
			.toLowerCase()
			.replace(/&/g, " and ")
			.replace(/[^a-z0-9]+/g, "-")
			.replace(/^-+|-+$/g, "");
	}

	function tidy(value) {
		return String(value || "").replace(/\s+/g, " ").trim();
	}

	function onAboutPage() {
		return document.body.classList.contains("vibe-about");
	}

	function aboutHash(id) {
		var about = config().about || "";
		if (onAboutPage()) {
			return "#" + id;
		}
		if (!about) {
			return "#" + id;
		}
		return about.replace(/#.*$/, "").replace(/\/?$/, "/") + "#" + id;
	}

	function itemBox(item, viewport) {
		var point = viewport.convertToViewportPoint(item.transform[4], item.transform[5]);
		var height = (item.height || Math.abs(item.transform[3]) || 10) * viewport.scale;
		var width = (item.width || 0) * viewport.scale;

		if (width < 2) {
			width = Math.max(8, item.str.length * height * 0.48);
		}

		return {
			x: point[0],
			y: point[1] - height,
			w: width,
			h: height,
		};
	}

	function unionBox(boxes) {
		var left = boxes[0].x;
		var top = boxes[0].y;
		var right = boxes[0].x + boxes[0].w;
		var bottom = boxes[0].y + boxes[0].h;
		var i;

		for (i = 1; i < boxes.length; i += 1) {
			left = Math.min(left, boxes[i].x);
			top = Math.min(top, boxes[i].y);
			right = Math.max(right, boxes[i].x + boxes[i].w);
			bottom = Math.max(bottom, boxes[i].y + boxes[i].h);
		}

		return { x: left, y: top, w: right - left, h: bottom - top };
	}

	function destinationForHeading(text) {
		var value = tidy(text).toLowerCase();
		var settings = config();

		if (/^(education|academics?|academic background)$/.test(value)) {
			return { href: aboutHash("education"), label: "Education" };
		}
		if (/^(projects?|selected projects|personal projects|relevant projects)$/.test(value)) {
			return { href: settings.projects || "/projects/", label: "Projects" };
		}
		if (/^(experience|work experience|professional experience|employment)$/.test(value)) {
			return { href: aboutHash("experience"), label: "Experience" };
		}
		if (/^(skills?|technical skills|core skills)$/.test(value)) {
			return { href: aboutHash("skills"), label: "Skills" };
		}
		if (/^(contact|contact information)$/.test(value)) {
			return { href: settings.contact || aboutHash("contact"), label: "Contact" };
		}
		if (/^(gallery|photography|photographs)$/.test(value)) {
			return { href: settings.gallery || "/gallery/", label: "Gallery" };
		}
		if (/^(about( me)?|summary|profile|objective)$/.test(value)) {
			return { href: aboutHash("about") || (settings.about || "#content"), label: "About" };
		}
		if (/^linkedin$/.test(value) && settings.linkedin) {
			return { href: settings.linkedin, label: "LinkedIn" };
		}
		if (/^github$/.test(value) && settings.github) {
			return { href: settings.github, label: "GitHub" };
		}

		return null;
	}

	function looksLikeHeading(text) {
		var value = tidy(text);
		if (!value || value.length > 42) {
			return false;
		}
		if (/[.?!]/.test(value)) {
			return false;
		}
		return value.split(" ").length <= 5;
	}

	function addHotspot(wrapper, viewport, box, href, label) {
		if (!href || !box) {
			return;
		}

		var scale = wrapper.clientWidth / viewport.width;
		if (!scale || !isFinite(scale)) {
			scale = 1;
		}

		var pad = Math.max(2, 4 * scale);
		var link = document.createElement("a");
		link.className = "resume-embed__hotspot";
		link.href = href;
		link.title = label || "";
		link.setAttribute("aria-label", label || "Resume link");
		link.style.left = Math.max(0, box.x * scale - pad) + "px";
		link.style.top = Math.max(0, box.y * scale - pad) + "px";
		link.style.width = Math.max(28, box.w * scale + pad * 2) + "px";
		link.style.height = Math.max(16, box.h * scale + pad * 2) + "px";

		if (href.charAt(0) === "#") {
			link.addEventListener("click", function (event) {
				var id = href.slice(1);
				var target = document.getElementById(id) || findAboutHeading(id);
				if (!target) {
					return;
				}
				event.preventDefault();
				target.scrollIntoView({ behavior: "smooth", block: "start" });
				if (history.replaceState) {
					history.replaceState(null, "", href);
				}
			});
		}

		wrapper.appendChild(link);
	}

	function findAboutHeading(id) {
		var needle = String(id || "").replace(/-/g, " ").toLowerCase();
		var headings = document.querySelectorAll(".about__copy h1, .about__copy h2, .about__copy h3, .about__copy h4");
		var i;
		var text;

		for (i = 0; i < headings.length; i += 1) {
			text = tidy(headings[i].textContent).toLowerCase();
			if (text === needle || text.indexOf(needle) !== -1 || slugify(text) === id) {
				return headings[i];
			}
		}

		return null;
	}

	function overlayPdfLinks(wrapper, viewport, textContent, annotations) {
		var settings = config();
		var items = (textContent && textContent.items) || [];
		var lines = [];

		(annotations || []).forEach(function (annot) {
			var url = annot.url || annot.unsafeUrl;
			var rect;
			var box;

			if (!url || (annot.subtype && annot.subtype !== "Link")) {
				return;
			}

			rect = viewport.convertToViewportRectangle(annot.rect);
			box = {
				x: Math.min(rect[0], rect[2]),
				y: Math.min(rect[1], rect[3]),
				w: Math.abs(rect[2] - rect[0]),
				h: Math.abs(rect[3] - rect[1]),
			};
			addHotspot(wrapper, viewport, box, url, url);
		});

		items.forEach(function (item) {
			var box;
			var line;
			var i;

			if (!item.str || !tidy(item.str)) {
				return;
			}

			box = itemBox(item, viewport);
			for (i = 0; i < lines.length; i += 1) {
				if (Math.abs(lines[i].y - box.y) < Math.max(3, box.h * 0.5)) {
					line = lines[i];
					break;
				}
			}

			if (!line) {
				line = { y: box.y, items: [] };
				lines.push(line);
			}

			line.items.push({ item: item, box: box });
		});

		lines.forEach(function (line) {
			var boxes;
			var text;
			var packed;
			var dest;
			var projects;
			var i;
			var label;
			var matchBoxes;

			line.items.sort(function (a, b) {
				return a.box.x - b.box.x;
			});
			boxes = line.items.map(function (entry) {
				return entry.box;
			});
			text = tidy(
				line.items
					.map(function (entry) {
						return entry.item.str;
					})
					.join(" ")
			);
			packed = tidy(
				line.items
					.map(function (entry) {
						return entry.item.str;
					})
					.join("")
			);

			if (looksLikeHeading(text) || looksLikeHeading(packed)) {
				dest = destinationForHeading(text) || destinationForHeading(packed);
				if (dest) {
					addHotspot(wrapper, viewport, unionBox(boxes), dest.href, dest.label);
					return;
				}
			}

			projects = settings.projectItems || [];
			for (i = 0; i < projects.length; i += 1) {
				label = tidy(projects[i].label);
				if (label.length < 3 || !projects[i].url) {
					continue;
				}
				if (text.toLowerCase().indexOf(label.toLowerCase()) === -1) {
					continue;
				}

				matchBoxes = line.items
					.filter(function (entry) {
						return tidy(entry.item.str).toLowerCase().indexOf(label.toLowerCase()) !== -1 || label.toLowerCase().indexOf(tidy(entry.item.str).toLowerCase()) !== -1;
					})
					.map(function (entry) {
						return entry.box;
					});

				addHotspot(wrapper, viewport, matchBoxes.length ? unionBox(matchBoxes) : unionBox(boxes), projects[i].url, label);
			}
		});
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

		var pdfConfig = window.stillframeResumePdf || {};
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

				return Promise.all([page.getTextContent(), page.getAnnotations({ intent: "display" })])
					.then(function (results) {
						overlayPdfLinks(wrapper, viewport, results[0], results[1]);
					})
					.catch(function () {
						return null;
					});
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
				if (!usedLocalWorker && pdfConfig.workerSrc) {
					pdfjsLib.GlobalWorkerOptions.workerSrc = pdfConfig.workerSrc;
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
