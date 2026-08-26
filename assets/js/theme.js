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
})();
