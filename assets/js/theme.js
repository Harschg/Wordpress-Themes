/**
 * Stillframe motion: page curtain, reveals, ripples, nav.
 */
(function () {
	"use strict";

	var reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
	var body = document.body;

	function ready() {
		body.classList.add("is-ready");
	}

	if (reduced) {
		ready();
		document.querySelectorAll("[data-reveal]").forEach(function (el) {
			el.classList.add("is-visible");
		});
	} else if (document.readyState === "complete") {
		window.setTimeout(ready, 80);
	} else {
		window.addEventListener("load", function () {
			window.setTimeout(ready, 80);
		});
	}

	document.querySelectorAll("[data-reveal]").forEach(function (el) {
		var stagger = el.getAttribute("data-stagger");
		if (stagger) {
			el.style.setProperty("--stagger", stagger);
		}
	});

	if ("IntersectionObserver" in window) {
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
	} else {
		document.querySelectorAll("[data-reveal]").forEach(function (el) {
			el.classList.add("is-visible");
		});
	}

	function sameOrigin(url) {
		try {
			return new URL(url, window.location.href).origin === window.location.origin;
		} catch (e) {
			return false;
		}
	}

	function shouldIgnore(link, event) {
		if (!link || !link.href) {
			return true;
		}
		if (link.closest("#wpadminbar")) {
			return true;
		}
		if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
			return true;
		}
		if (link.target === "_blank" || link.hasAttribute("download")) {
			return true;
		}
		if (link.getAttribute("href") && link.getAttribute("href").charAt(0) === "#") {
			return true;
		}
		if (link.protocol === "mailto:" || link.protocol === "tel:") {
			return true;
		}
		if (!sameOrigin(link.href)) {
			return true;
		}

		var next = new URL(link.href, window.location.href);
		if (next.pathname === window.location.pathname && next.hash) {
			return true;
		}

		if (next.href === window.location.href) {
			return true;
		}

		return false;
	}

	document.addEventListener("click", function (event) {
		var link = event.target.closest("a");
		if (!link || shouldIgnore(link, event)) {
			return;
		}

		if (reduced) {
			return;
		}

		event.preventDefault();
		body.classList.add("is-leaving");
		window.setTimeout(function () {
			window.location.href = link.href;
		}, 520);
	});

	document.addEventListener("click", function (event) {
		var button = event.target.closest(".btn, .directory-card, .nav-toggle");
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

	var toggle = document.querySelector("[data-nav-toggle]");
	var nav = document.querySelector("[data-nav]");

	if (toggle && nav) {
		toggle.addEventListener("click", function () {
			var open = toggle.getAttribute("aria-expanded") === "true";
			toggle.setAttribute("aria-expanded", open ? "false" : "true");
			nav.classList.toggle("is-open", !open);
		});
	}
})();
