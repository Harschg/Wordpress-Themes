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
		document.querySelectorAll("[data-reveal]").forEach(function (el) {
			el.classList.add("is-visible");
		});
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

	document.querySelectorAll("[data-reveal]").forEach(function (el) {
		var stagger = el.getAttribute("data-stagger");
		if (stagger) {
			el.style.setProperty("--stagger", stagger);
		}
	});

	if ("IntersectionObserver" in window && !reduced) {
		var io = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add("is-visible");
						io.unobserve(entry.target);
					}
				});
			},
			{ threshold: 0.12, rootMargin: "0px 0px -8% 0px" }
		);

		document.querySelectorAll("[data-reveal]").forEach(function (el) {
			io.observe(el);
		});
	}

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
		if (reduced) {
			showWorld();
		} else if (worldImg.complete && worldImg.naturalWidth) {
			window.requestAnimationFrame(function () {
				window.requestAnimationFrame(showWorld);
			});
		} else {
			worldImg.addEventListener("load", showWorld);
			worldImg.addEventListener("error", showWorld);
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

		function updateToc() {
			if (!tocTargets.length) {
				return;
			}

			var line = Math.max(120, window.innerHeight * 0.28);
			var current = tocTargets[0];

			tocTargets.forEach(function (el) {
				if (el.getBoundingClientRect().top <= line) {
					current = el;
				}
			});

			if (current && current.id) {
				setTocCurrent(current.id);
			}
		}

		window.addEventListener("scroll", updateToc, { passive: true });
		window.addEventListener("resize", updateToc);
		updateToc();
	}
})();
