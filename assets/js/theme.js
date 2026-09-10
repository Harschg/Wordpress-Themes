/**
 * Stillframe motion: loader, fade-in, reveals, ripples, nav.
 */
(function () {
	"use strict";

	var reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
	var body = document.body;
	var loader = document.querySelector("[data-page-loader]");
	var readyCalled = false;

	function ready() {
		if (readyCalled) {
			return;
		}
		readyCalled = true;
		body.classList.add("is-ready");
		bindReveals();
		if (loader) {
			loader.setAttribute("aria-hidden", "true");
			loader.removeAttribute("role");
		}
	}

	if (reduced) {
		ready();
	} else if (document.readyState === "complete") {
		window.setTimeout(ready, 60);
	} else {
		window.addEventListener("load", function () {
			window.setTimeout(ready, 60);
		});
	}

	window.setTimeout(ready, 4000);

	function fadeImage(img) {
		if (!img || img.nodeName !== "IMG") {
			return;
		}

		if (img.closest(".site-brand, .project-single, .about, .photo-card--arch")) {
			img.classList.add("is-in");
			return;
		}

		function show() {
			img.classList.add("is-in");
		}

		function reveal() {
			if (img.decode) {
				img.decode().then(show).catch(show);
			} else {
				show();
			}
		}

		if (img.complete && img.naturalWidth) {
			window.requestAnimationFrame(function () {
				window.requestAnimationFrame(reveal);
			});
			return;
		}

		img.addEventListener("load", reveal, { once: true });
		img.addEventListener("error", show, { once: true });
	}

	function fadeImages(root) {
		(root || document).querySelectorAll("img").forEach(fadeImage);
		if (root && root.nodeName === "IMG") {
			fadeImage(root);
		}
	}

	fadeImages(document);

	if ("MutationObserver" in window) {
		var imageWatch = new MutationObserver(function (mutations) {
			mutations.forEach(function (mutation) {
				mutation.addedNodes.forEach(function (node) {
					if (node.nodeType !== 1) {
						return;
					}
					fadeImages(node);
				});
			});
		});
		imageWatch.observe(document.body, { childList: true, subtree: true });
	}

	function isRevealableNode(el) {
		return el && el.nodeType === 1 && !el.matches("script, style, noscript");
	}

	function projectContentBlocks(root) {
		var kids = Array.prototype.filter.call(root.children, isRevealableNode);

		if (
			kids.length === 1 &&
			kids[0].children.length > 1 &&
			!kids[0].matches("p, h1, h2, h3, h4, figure, ul, ol, blockquote, .wp-block-image, .wp-block-media-text")
		) {
			return projectContentBlocks(kids[0]);
		}

		return kids;
	}

	function markReveal(el) {
		if (!el || el.hasAttribute("data-reveal")) {
			return;
		}
		el.classList.add("reveal");
		el.setAttribute("data-reveal", "");
	}

	function markSlideReveal(el, fromLeft) {
		markReveal(el);
		el.classList.toggle("reveal--from-left", fromLeft);
		el.classList.toggle("reveal--from-right", !fromLeft);
	}

	function prepareContentReveals(root) {
		if (!root) {
			return;
		}

		projectContentBlocks(root).forEach(markReveal);
		root.querySelectorAll("figure, .wp-block-image, .wp-block-media-text").forEach(function (el) {
			if (!el.closest("[data-reveal]")) {
				markReveal(el);
			}
		});
		root.classList.remove("is-awaiting-reveal");
	}

	function prepareScrollReveals() {
		var project = document.querySelector(".project-single");
		if (project) {
			prepareContentReveals(project.querySelector(".project-single__intro"));
			project.querySelectorAll(".project-feature__copy").forEach(markReveal);

			var slide = 0;
			project
				.querySelectorAll(
					".project-single__intro figure, .project-single__intro .wp-block-image, .project-single__intro .wp-block-media-text, .project-single__intro > p > img, .project-feature__media"
				)
				.forEach(function (el) {
					if (el.nodeName === "IMG" && el.parentNode && el.parentNode.matches("p")) {
						el = el.parentNode;
					}
					if (el.closest(".project-feature__copy")) {
						return;
					}
					markSlideReveal(el, 0 === slide % 2);
					slide += 1;
				});
		}

		var aboutCopy = document.querySelector(".about__copy .prose");
		if (aboutCopy) {
			prepareContentReveals(aboutCopy);
		}

		prepareGalleryArches();
	}

	function prepareGalleryArches() {
		var cards = document.querySelectorAll(".photo-card--arch");
		if (!cards.length) {
			return;
		}

		var width = window.innerWidth || 1200;
		var travel = Math.min(260, Math.max(120, width * 0.18));

		cards.forEach(function (card) {
			markReveal(card);

			var fromLeft = Math.random() < 0.5;
			var startX = Math.round((fromLeft ? -1 : 1) * travel * (0.92 + Math.random() * 0.16));
			var startY = Math.round(10 + Math.random() * 16);
			var ctrlX = Math.round(startX * 0.5);
			var ctrlY = Math.round(-(22 + Math.random() * 20));

			card.classList.toggle("reveal--arch-left", fromLeft);
			card.classList.toggle("reveal--arch-right", !fromLeft);
			card.style.setProperty("--arch-x", startX + "px");
			card.style.setProperty("--arch-y", startY + "px");
			card.dataset.archX = String(startX);
			card.dataset.archY = String(startY);
			card.dataset.archCtrlX = String(ctrlX);
			card.dataset.archCtrlY = String(ctrlY);
			card.dataset.archTime = String(Math.round(980 + Math.random() * 160));
			card.dataset.archDelay = String(Math.round(Math.random() * 70));
		});
	}

	function galleryArchFrames(startX, startY, ctrlX, ctrlY, steps) {
		var frames = [];
		var i;

		for (i = 0; i <= steps; i++) {
			var t = i / steps;
			var eased = 1 - Math.pow(1 - t, 3);
			var rest = 1 - eased;
			var x = rest * rest * startX + 2 * rest * eased * ctrlX;
			var y = rest * rest * startY + 2 * rest * eased * ctrlY;

			frames.push({
				transform: "translate3d(" + x.toFixed(2) + "px, " + y.toFixed(2) + "px, 0)",
				opacity: Math.min(1, eased * 2.15).toFixed(3),
			});
		}

		frames[steps].transform = "translate3d(0px, 0px, 0)";
		frames[steps].opacity = "1";
		return frames;
	}

	function finishGalleryArch(card, animation) {
		card.classList.add("is-visible");
		card.classList.remove("is-arching");
		card.style.transform = "";
		card.style.opacity = "";
		if (animation && typeof animation.cancel === "function") {
			animation.cancel();
		}
	}

	function playGalleryArch(card) {
		if (!card || card.classList.contains("is-visible") || card.classList.contains("is-arching")) {
			return;
		}

		var startX = parseFloat(card.dataset.archX);
		var startY = parseFloat(card.dataset.archY);
		var ctrlX = parseFloat(card.dataset.archCtrlX);
		var ctrlY = parseFloat(card.dataset.archCtrlY);
		var duration = parseFloat(card.dataset.archTime) || 1080;
		var delay = parseFloat(card.dataset.archDelay) || 0;

		if (isNaN(startX) || isNaN(startY) || isNaN(ctrlX) || isNaN(ctrlY)) {
			card.classList.add("is-visible");
			return;
		}

		card.classList.add("is-arching");

		if (card.animate) {
			var animation = card.animate(galleryArchFrames(startX, startY, ctrlX, ctrlY, 32), {
				duration: duration,
				delay: delay,
				easing: "linear",
				fill: "forwards",
			});
			var done = function () {
				finishGalleryArch(card, animation);
			};
			if (animation.finished) {
				animation.finished.then(done).catch(done);
			} else {
				animation.onfinish = done;
			}
			return;
		}

		var started = null;

		function frame(now) {
			if (started === null) {
				started = now + delay;
			}
			if (now < started) {
				window.requestAnimationFrame(frame);
				return;
			}

			var t = Math.min(1, (now - started) / duration);
			var eased = 1 - Math.pow(1 - t, 3);
			var rest = 1 - eased;
			var x = rest * rest * startX + 2 * rest * eased * ctrlX;
			var y = rest * rest * startY + 2 * rest * eased * ctrlY;

			card.style.transform = "translate3d(" + x + "px, " + y + "px, 0)";
			card.style.opacity = String(Math.min(1, eased * 2.15));

			if (t < 1) {
				window.requestAnimationFrame(frame);
				return;
			}

			finishGalleryArch(card);
		}

		window.requestAnimationFrame(frame);
	}

	prepareScrollReveals();

	var revealObserver = null;
	var revealsBound = false;
	var revealBusy = false;

	function revealItems() {
		return Array.prototype.slice.call(document.querySelectorAll("[data-reveal]"));
	}

	function nextPendingReveal() {
		var items = revealItems();
		var i;

		for (i = 0; i < items.length; i++) {
			if (!items[i].classList.contains("is-visible") && !items[i].classList.contains("is-arching")) {
				return items[i];
			}
		}

		return null;
	}

	function pendingRevealGroup() {
		var items = revealItems();
		var start = -1;
		var i;

		for (i = 0; i < items.length; i++) {
			if (!items[i].classList.contains("is-visible") && !items[i].classList.contains("is-arching")) {
				start = i;
				break;
			}
		}

		if (start < 0) {
			return [];
		}

		var first = items[start];
		if (!first.classList.contains("photo-card--arch")) {
			return [first];
		}

		var rowTop = first.offsetTop;
		var group = [first];

		for (i = start + 1; i < items.length; i++) {
			var el = items[i];
			if (el.classList.contains("is-visible") || el.classList.contains("is-arching")) {
				continue;
			}
			if (!el.classList.contains("photo-card--arch") || Math.abs(el.offsetTop - rowTop) > 32) {
				break;
			}
			group.push(el);
		}

		return group;
	}

	function primeRevealImages(el) {
		if (!el) {
			return;
		}

		var imgs = el.nodeName === "IMG" ? [el] : Array.prototype.slice.call(el.querySelectorAll("img"));
		imgs.forEach(function (img) {
			if (img.getAttribute("loading") === "lazy") {
				img.setAttribute("loading", "eager");
			}
		});
	}

	function whenImageReady(img) {
		function decoded() {
			if (img.decode) {
				return img.decode().then(
					function () {},
					function () {}
				);
			}
			return Promise.resolve();
		}

		if (img.complete && img.naturalWidth) {
			return decoded();
		}

		return new Promise(function (resolve) {
			var settled = false;
			function done() {
				if (settled) {
					return;
				}
				settled = true;
				decoded().then(resolve);
			}

			img.addEventListener("load", done, { once: true });
			img.addEventListener("error", resolve, { once: true });
			window.setTimeout(done, 2800);
		});
	}

	function waitForRevealMedia(el) {
		var imgs = el.nodeName === "IMG" ? [el] : Array.prototype.slice.call(el.querySelectorAll("img"));
		if (!imgs.length) {
			return Promise.resolve();
		}

		primeRevealImages(el);
		return Promise.all(imgs.map(whenImageReady));
	}

	function revealDue(el) {
		var vh = window.innerHeight || 0;
		var doc = document.documentElement;
		var pageHeight = Math.max(doc.scrollHeight, document.body ? document.body.scrollHeight : 0);

		if (window.scrollY + vh >= pageHeight - 120) {
			return true;
		}

		return el.getBoundingClientRect().top < vh * 0.9;
	}

	function revealGap(el) {
		if (el.classList.contains("photo-card--arch")) {
			return (parseFloat(el.dataset.archTime) || 980) + (parseFloat(el.dataset.archDelay) || 0);
		}
		if (el.classList.contains("reveal--from-left") || el.classList.contains("reveal--from-right")) {
			return 920;
		}
		return 210;
	}

	function startReveal(el) {
		if (el.classList.contains("photo-card--arch")) {
			playGalleryArch(el);
			return;
		}
		el.classList.add("is-visible");
	}

	function tryAdvanceReveals() {
		if (reduced || revealBusy) {
			return;
		}

		var group = pendingRevealGroup();
		if (!group.length) {
			return;
		}

		group.forEach(primeRevealImages);
		var after = revealItems();
		var last = group[group.length - 1];
		var afterIndex = after.indexOf(last);
		if (afterIndex >= 0 && after[afterIndex + 1]) {
			primeRevealImages(after[afterIndex + 1]);
		}

		if (!revealDue(group[0])) {
			return;
		}

		revealBusy = true;
		if (revealObserver) {
			group.forEach(function (el) {
				revealObserver.unobserve(el);
			});
		}

		Promise.all(group.map(waitForRevealMedia))
			.then(function () {
				var gap = 210;
				group.forEach(function (el) {
					startReveal(el);
					gap = Math.max(gap, revealGap(el));
				});
				window.setTimeout(function () {
					revealBusy = false;
					tryAdvanceReveals();
				}, gap);
			})
			.catch(function () {
				group.forEach(startReveal);
				revealBusy = false;
				tryAdvanceReveals();
			});
	}

	function showReveal(el) {
		if (!el || el.classList.contains("is-visible") || el.classList.contains("is-arching")) {
			return;
		}
		if (reduced) {
			el.classList.add("is-visible");
			return;
		}
		tryAdvanceReveals();
	}

	function pendingReveals() {
		return document.querySelectorAll("[data-reveal]:not(.is-visible)");
	}

	function revealOnscreen() {
		tryAdvanceReveals();
	}

	function bindReveals() {
		if (revealsBound) {
			return;
		}
		revealsBound = true;

		prepareScrollReveals();

		document.querySelectorAll("[data-reveal]").forEach(function (el) {
			var stagger = el.getAttribute("data-stagger");
			if (stagger) {
				el.style.setProperty("--stagger", stagger);
			}
		});

		if (reduced || !("IntersectionObserver" in window)) {
			if (reduced) {
				pendingReveals().forEach(function (el) {
					el.classList.add("is-visible");
				});
			} else {
				tryAdvanceReveals();
			}
			return;
		}

		revealObserver = new IntersectionObserver(
			function (entries) {
				var shouldAdvance = false;
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						shouldAdvance = true;
					}
				});
				if (shouldAdvance) {
					tryAdvanceReveals();
				}
			},
			{ threshold: 0, rootMargin: "0px 0px -8% 0px" }
		);

		pendingReveals().forEach(function (el) {
			revealObserver.observe(el);
		});

		revealOnscreen();
		window.addEventListener("scroll", revealOnscreen, { passive: true });
		window.addEventListener("resize", revealOnscreen);
	}

	(function bindPressTilt() {
		if (reduced || !window.matchMedia("(pointer: fine)").matches) {
			return;
		}

		var max = 6.5;

		function surface(root) {
			return root.querySelector(".photo-card__media") || root;
		}

		function reset(el) {
			el.style.setProperty("--press-x", "0deg");
			el.style.setProperty("--press-y", "0deg");
			el.classList.remove("is-pressing");
		}

		function tilt(el, event) {
			var rect = el.getBoundingClientRect();
			if (!rect.width || !rect.height) {
				return;
			}

			var px = (event.clientX - rect.left) / rect.width;
			var py = (event.clientY - rect.top) / rect.height;
			px = Math.max(0, Math.min(1, px));
			py = Math.max(0, Math.min(1, py));

			el.style.setProperty("--press-x", ((0.5 - py) * 2 * max).toFixed(2) + "deg");
			el.style.setProperty("--press-y", ((px - 0.5) * 2 * max).toFixed(2) + "deg");
		}

		document.querySelectorAll(".series-card, .photo-card__link, .project-card").forEach(function (root) {
			var el = surface(root);

			root.addEventListener("pointerenter", function () {
				el.classList.add("is-pressing");
			});

			root.addEventListener(
				"pointermove",
				function (event) {
					tilt(el, event);
				},
				{ passive: true }
			);

			root.addEventListener("pointerleave", function () {
				reset(el);
			});
		});
	})();

	document.addEventListener("click", function (event) {
		var button = event.target.closest(".btn, .directory-card, .series-card");
		if (!button) {
			return;
		}

		var rect = button.getBoundingClientRect();
		var ripple = document.createElement("span");
		var size = Math.max(rect.width, rect.height);
		ripple.className = "ripple";
		ripple.style.width = size + "px";
		ripple.style.height = size + "px";
		ripple.style.left = event.clientX - rect.left - size / 2 + "px";
		ripple.style.top = event.clientY - rect.top - size / 2 + "px";
		button.appendChild(ripple);
		window.setTimeout(function () {
			ripple.remove();
		}, 700);
	});

	var currentNav = document.querySelector(".site-nav .is-current, .site-nav .current-menu-item, .site-nav .current_page_item");
	if (currentNav && currentNav.scrollIntoView) {
		currentNav.scrollIntoView({ inline: "center", block: "nearest" });
	}

	(function bindNavDrop() {
		var nav = document.querySelector("[data-nav]");
		if (!nav) {
			return;
		}

		var drops = nav.querySelectorAll(".nav-item--drop");
		if (!drops.length) {
			return;
		}

		function closeDrops(except) {
			drops.forEach(function (item) {
				if (item === except) {
					return;
				}
				item.classList.remove("is-open");
				var btn = item.querySelector(".nav-drop__toggle");
				if (btn) {
					btn.setAttribute("aria-expanded", "false");
				}
			});
			nav.classList.toggle("is-drop-open", Boolean(except));
		}

		drops.forEach(function (item) {
			var toggle = item.querySelector(".nav-drop__toggle");
			if (!toggle) {
				return;
			}

			toggle.addEventListener("click", function (event) {
				event.preventDefault();
				event.stopPropagation();
				var open = !item.classList.contains("is-open");
				closeDrops(open ? item : null);
				item.classList.toggle("is-open", open);
				toggle.setAttribute("aria-expanded", open ? "true" : "false");
				nav.classList.toggle("is-drop-open", open);
			});
		});

		document.addEventListener("click", function (event) {
			if (!nav.contains(event.target)) {
				closeDrops(null);
			}
		});

		document.addEventListener("keydown", function (event) {
			if (event.key === "Escape") {
				closeDrops(null);
			}
		});
	})();

	var prevPhoto = document.querySelector(".photo-arrow--prev");
	var nextPhoto = document.querySelector(".photo-arrow--next");

	if (prevPhoto || nextPhoto) {
		document.addEventListener("keydown", function (event) {
			if (event.target.closest("input, textarea, select, [contenteditable]")) {
				return;
			}
			if (event.key === "ArrowLeft" && prevPhoto) {
				window.location.href = prevPhoto.href;
			}
			if (event.key === "ArrowRight" && nextPhoto) {
				window.location.href = nextPhoto.href;
			}
		});
	}

	var world = document.querySelector(".page-world");
	var worldImg = world ? world.querySelector("img") : null;
	var finePointer = window.matchMedia("(pointer: fine)").matches;

	function showWorld() {
		if (worldImg) {
			worldImg.classList.add("is-in");
		}
	}

	if (worldImg) {
		worldImg.addEventListener("load", showWorld);
		worldImg.addEventListener("error", showWorld);
		if (reduced || worldImg.complete) {
			showWorld();
		}
	}

	if (world && !reduced) {
		var mouseX = 0;
		var mouseY = 0;
		var scrollShift = 0;
		var currentX = 0;
		var currentY = 0;
		var mouseRange = 22;
		var scrollRange = 36;

		function updateScrollShift() {
			var scrollable = document.documentElement.scrollHeight - window.innerHeight;
			var progress = scrollable > 0 ? window.scrollY / scrollable : 0;
			progress = Math.max(0, Math.min(1, progress));
			scrollShift = (progress - 0.5) * 2 * scrollRange;
		}

		if (finePointer) {
			window.addEventListener(
				"pointermove",
				function (event) {
					var midX = window.innerWidth / 2;
					var midY = window.innerHeight / 2;
					mouseX = ((event.clientX - midX) / midX) * mouseRange;
					mouseY = ((event.clientY - midY) / midY) * mouseRange;
				},
				{ passive: true }
			);
		}

		window.addEventListener("scroll", updateScrollShift, { passive: true });
		window.addEventListener("resize", updateScrollShift);
		updateScrollShift();

		function followWorld() {
			var targetX = mouseX;
			var targetY = mouseY + scrollShift;
			currentX += (targetX - currentX) * 0.05;
			currentY += (targetY - currentY) * 0.05;
			world.style.setProperty("--world-x", currentX.toFixed(2) + "px");
			world.style.setProperty("--world-y", currentY.toFixed(2) + "px");
			window.requestAnimationFrame(followWorld);
		}

		window.requestAnimationFrame(followWorld);
	}

	var toc = document.querySelector("[data-about-toc]");
	if (toc) {
		var tocLinks = Array.prototype.slice.call(toc.querySelectorAll('a[href^="#"]'));
		var tocTargets = tocLinks
			.map(function (link) {
				return document.getElementById(link.getAttribute("href").slice(1));
			})
			.filter(Boolean);
		var tocPinnedId = "";

		function setTocCurrent(id) {
			tocLinks.forEach(function (link) {
				var match = link.getAttribute("href") === "#" + id;
				link.classList.toggle("is-current", match);
				if (match) {
					link.setAttribute("aria-current", "location");
				} else {
					link.removeAttribute("aria-current");
				}
			});
		}

		function pinToc(id) {
			if (!id) {
				return;
			}
			tocPinnedId = id;
			setTocCurrent(id);
		}

		function updateToc() {
			if (!tocTargets.length) {
				return;
			}

			var vh = window.innerHeight || 0;
			var pageHeight = Math.max(
				document.documentElement.scrollHeight,
				document.body ? document.body.scrollHeight : 0
			);
			var atBottom = window.scrollY + vh >= pageHeight - 16;
			var line = Math.max(120, vh * 0.28);

			if (atBottom) {
				var lastTop = tocTargets[tocTargets.length - 1].getBoundingClientRect().top;
				if (lastTop > line) {
					line = lastTop + 1;
				}
			}

			var current = tocTargets[0];
			tocTargets.forEach(function (el) {
				if (el.getBoundingClientRect().top <= line) {
					current = el;
				}
			});

			if (tocPinnedId) {
				var pinned = document.getElementById(tocPinnedId);
				if (!pinned) {
					tocPinnedId = "";
				} else {
					var pinnedTop = pinned.getBoundingClientRect().top;
					if (!(pinnedTop <= line + 8 || atBottom)) {
						setTocCurrent(tocPinnedId);
						return;
					}
					tocPinnedId = "";
				}
			}

			if (current && current.id) {
				setTocCurrent(current.id);
			}
		}

		toc.addEventListener("click", function (event) {
			var link = event.target.closest('a[href^="#"]');
			if (!link || !toc.contains(link)) {
				return;
			}
			pinToc((link.getAttribute("href") || "").replace(/^#/, ""));
		});

		window.addEventListener("hashchange", function () {
			pinToc(window.location.hash.replace(/^#/, ""));
		});

		if (window.location.hash) {
			pinToc(window.location.hash.replace(/^#/, ""));
		}

		window.addEventListener("scroll", updateToc, { passive: true });
		window.addEventListener("resize", updateToc);
		updateToc();
	}

	var gallery = document.querySelector("[data-project-features]");
	var stage = document.querySelector("[data-project-stage]");
	var stageImage = stage ? stage.querySelector("[data-stage-image]") : null;
	var manifestNode = gallery ? gallery.querySelector("[data-gallery-manifest]") : null;
	var galleryItems = [];
	var galleryIndex = 0;

	if (manifestNode) {
		try {
			galleryItems = JSON.parse(manifestNode.textContent || "[]");
		} catch (error) {
			galleryItems = [];
		}
	}

	function showStage(index) {
		if (!stage || !stageImage || !galleryItems.length) {
			return;
		}

		galleryIndex = (index + galleryItems.length) % galleryItems.length;
		var item = galleryItems[galleryIndex];
		stageImage.classList.remove("is-in");
		stageImage.src = item.src || "";
		stageImage.alt = item.alt || "";
		fadeImage(stageImage);
		stage.hidden = false;
		document.body.classList.add("has-project-stage");
		var closeBtn = stage.querySelector("[data-stage-close]");
		if (closeBtn && closeBtn.focus) {
			closeBtn.focus();
		}
	}

	function hideStage() {
		if (!stage) {
			return;
		}
		stage.hidden = true;
		document.body.classList.remove("has-project-stage");
		if (stageImage) {
			stageImage.removeAttribute("src");
			stageImage.alt = "";
		}
	}

	if (gallery && galleryItems.length) {
		gallery.addEventListener("click", function (event) {
			var shot = event.target.closest("[data-shot-index]");
			if (!shot) {
				return;
			}
			showStage(parseInt(shot.getAttribute("data-shot-index"), 10) || 0);
		});
	}

	if (stage) {
		stage.addEventListener("click", function (event) {
			if (event.target.closest("[data-stage-close]") || event.target === stage) {
				hideStage();
			} else if (event.target.closest("[data-stage-prev]")) {
				showStage(galleryIndex - 1);
			} else if (event.target.closest("[data-stage-next]")) {
				showStage(galleryIndex + 1);
			}
		});

		document.addEventListener("keydown", function (event) {
			if (stage.hidden) {
				return;
			}
			if (event.key === "Escape") {
				hideStage();
			} else if (event.key === "ArrowLeft") {
				showStage(galleryIndex - 1);
			} else if (event.key === "ArrowRight") {
				showStage(galleryIndex + 1);
			}
		});
	}

	(function bindLivelyLinks() {
		if (reduced) {
			return;
		}

		var selector = ".prose a[href], .project-feature__text a[href]";

		function setTilt(link) {
			var tilt = (Math.random() < 0.5 ? -1 : 1) * (4 + Math.random() * 4);
			link.style.setProperty("--link-tilt", tilt.toFixed(2) + "deg");
		}

		function isTextLink(link) {
			if (!link || link.querySelector("img") || link.closest(".btn, .site-nav, .nav-sub, .about-toc")) {
				return false;
			}
			return link.matches(selector);
		}

		document.addEventListener(
			"pointerenter",
			function (event) {
				var link = event.target.closest ? event.target.closest("a") : null;
				if (!isTextLink(link)) {
					return;
				}
				setTilt(link);
			},
			true
		);

		function playNudge(link) {
			if (!link || link.classList.contains("is-lively") || link.matches(":hover")) {
				return;
			}

			setTilt(link);
			link.classList.add("is-lively");
			link.addEventListener(
				"animationend",
				function () {
					link.classList.remove("is-lively");
				},
				{ once: true }
			);
		}

		function revealReady(link) {
			var reveal = link.closest("[data-reveal]");
			return !reveal || reveal.classList.contains("is-visible");
		}

		function playWhenReady(link) {
			if (link.dataset.linkInView !== "1") {
				return;
			}
			if (!revealReady(link)) {
				window.setTimeout(function () {
					playWhenReady(link);
				}, 180);
				return;
			}
			playNudge(link);
		}

		if (!("IntersectionObserver" in window)) {
			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					var link = entry.target;
					if (entry.isIntersecting) {
						if (link.dataset.linkInView === "1") {
							return;
						}
						link.dataset.linkInView = "1";
						window.setTimeout(function () {
							playWhenReady(link);
						}, Math.round(60 + Math.random() * 160));
						return;
					}

					link.dataset.linkInView = "0";
					link.classList.remove("is-lively");
				});
			},
			{ threshold: 0.4, rootMargin: "0px 0px -6% 0px" }
		);

		document.querySelectorAll(selector).forEach(function (link) {
			if (isTextLink(link)) {
				observer.observe(link);
			}
		});
	})();
})();
