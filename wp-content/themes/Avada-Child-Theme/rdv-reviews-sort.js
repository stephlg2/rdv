(function () {
	'use strict';

	var DATE_SELECTORS = '.rdvasie-review-date, .ti-date';
	var ABSOLUTE_DATE_RE = /^(\d{2})\/(\d{2})\/(\d{4})$/;
	var RELATIVE_DATE_RE =
		/il y a\s+(?:environ\s+)?(?:(\d+)|(?:un|une))\s*(minute|minutes|min|heure|heures|jour|jours|semaine|semaines|mois|an|ans|année|années)/i;

	var UNIT_SECONDS = {
		minute: 60,
		minutes: 60,
		min: 60,
		heure: 3600,
		heures: 3600,
		jour: 86400,
		jours: 86400,
		semaine: 604800,
		semaines: 604800,
		mois: 2592000,
		an: 31536000,
		ans: 31536000,
		année: 31536000,
		années: 31536000,
	};

	function normalizeText(text) {
		return (text || '')
			.trim()
			.toLowerCase()
			.replace(/[’´`]/g, "'");
	}

	function parseReviewAgeSeconds(text) {
		var raw = (text || '').trim();
		if (!raw) {
			return Number.MAX_SAFE_INTEGER;
		}

		var absolute = raw.match(ABSOLUTE_DATE_RE);
		if (absolute) {
			var date = new Date(
				parseInt(absolute[3], 10),
				parseInt(absolute[2], 10) - 1,
				parseInt(absolute[1], 10)
			);
			return Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
		}

		var lower = normalizeText(raw);

		if (lower.indexOf("aujourd'hui") !== -1) {
			return 43200;
		}
		if (lower.indexOf('hier') !== -1) {
			return 86400;
		}
		if (/instant|à l'instant|a l'instant/.test(lower)) {
			return 60;
		}

		var relative = lower.match(RELATIVE_DATE_RE);
		if (relative) {
			var amount = relative[1] ? parseInt(relative[1], 10) : 1;
			var unit = relative[2];
			return amount * (UNIT_SECONDS[unit] || Number.MAX_SAFE_INTEGER);
		}

		return Number.MAX_SAFE_INTEGER;
	}

	function formatRelativeFr(secondsAgo) {
		var units = [
			['an', 'ans', 31536000],
			['mois', 'mois', 2592000],
			['semaine', 'semaines', 604800],
			['jour', 'jours', 86400],
			['heure', 'heures', 3600],
			['minute', 'minutes', 60],
		];

		if (secondsAgo < 60) {
			return "à l'instant";
		}

		for (var i = 0; i < units.length; i++) {
			var count = Math.floor(secondsAgo / units[i][2]);
			if (count >= 1) {
				return 'il y a ' + count + ' ' + (count > 1 ? units[i][1] : units[i][0]);
			}
		}

		return "à l'instant";
	}

	function getCardAgeSeconds(card, dateSelector) {
		var el = card.querySelector(dateSelector);
		if (!el) {
			return Number.MAX_SAFE_INTEGER;
		}

		var dataTime = el.getAttribute('data-time') || el.getAttribute('datetime');
		if (dataTime) {
			var parsed = Date.parse(dataTime);
			if (!isNaN(parsed)) {
				return Math.max(0, Math.floor((Date.now() - parsed) / 1000));
			}
		}

		return parseReviewAgeSeconds(el.textContent);
	}

	function humanizeDateElements() {
		document.querySelectorAll(DATE_SELECTORS).forEach(function (el) {
			if (el.getAttribute('data-rdv-humanized') === '1') {
				return;
			}

			var raw = (el.textContent || '').trim();
			if (!ABSOLUTE_DATE_RE.test(raw)) {
				return;
			}

			var seconds = parseReviewAgeSeconds(raw);
			el.textContent = formatRelativeFr(seconds);
			el.setAttribute('data-rdv-humanized', '1');
			el.setAttribute('data-rdv-source-date', raw);
		});
	}

	function dedupeContainer(container, itemSelector) {
		var items = Array.prototype.slice.call(
			container.querySelectorAll(':scope > ' + itemSelector)
		);
		if (items.length < 2) {
			return false;
		}

		var seen = Object.create(null);
		var removed = false;

		items.forEach(function (el) {
			var key = (el.textContent || '').replace(/\s+/g, ' ').trim();
			if (!key) {
				return;
			}
			if (seen[key]) {
				el.parentNode.removeChild(el);
				removed = true;
				return;
			}
			seen[key] = true;
		});

		return removed;
	}

	function sortContainer(container, itemSelector, dateSelector) {
		dedupeContainer(container, itemSelector);

		var items = Array.prototype.slice.call(
			container.querySelectorAll(':scope > ' + itemSelector)
		);
		if (items.length < 2) {
			return false;
		}

		var sorted = items.slice().sort(function (a, b) {
			return getCardAgeSeconds(a, dateSelector) - getCardAgeSeconds(b, dateSelector);
		});

		var changed = false;
		for (var i = 0; i < sorted.length; i++) {
			if (sorted[i] !== items[i]) {
				changed = true;
				break;
			}
		}
		if (!changed) {
			return false;
		}

		sorted.forEach(function (el) {
			container.appendChild(el);
		});
		return true;
	}

	function sortRdvasie() {
		var list = document.querySelector('.rdvasie-reviews-list');
		if (!list) {
			return false;
		}
		return sortContainer(list, '.rdvasie-review-card', '.rdvasie-review-date');
	}

	function sortTrustindex() {
		var wrappers = document.querySelectorAll('.ti-reviews-container-wrapper');
		var changed = false;
		wrappers.forEach(function (wrapper) {
			if (sortContainer(wrapper, '.ti-review-item', '.ti-date')) {
				changed = true;
			}
		});
		return changed;
	}

	function run() {
		humanizeDateElements();
		sortRdvasie();
		sortTrustindex();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}

	var debounceTimer;
	var observer = new MutationObserver(function () {
		clearTimeout(debounceTimer);
		debounceTimer = setTimeout(run, 150);
	});

	function observe() {
		var roots = document.querySelectorAll(
			'.rdvasie-reviews-list, .ti-reviews-container-wrapper, .ti-widget-container'
		);
		roots.forEach(function (el) {
			observer.observe(el, { childList: true, subtree: true, characterData: true });
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', observe);
	} else {
		observe();
	}
})();
