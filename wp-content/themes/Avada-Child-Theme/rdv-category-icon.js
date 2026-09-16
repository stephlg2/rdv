(function () {
	'use strict';

	var cfg = window.rdvCategoryIcon || {};
	var iconsCache = null;
	var activeField = null;
	var modal = null;

	function t(key) {
		return (cfg.i18n && cfg.i18n[key]) || key;
	}

	function normalizeIcon(value) {
		value = String(value || '').trim();
		if (!value) {
			return '';
		}
		var match = value.match(/\bfa-([a-z0-9-]+)\b/i);
		if (match) {
			return 'fa-' + match[1].toLowerCase();
		}
		return 'fa-' + value.replace(/^fa-/, '').toLowerCase().replace(/[^a-z0-9-]/g, '');
	}

	function updateFieldPreview(field, icon) {
		var preview = field.querySelector('[data-rdv-fa-icon-preview]');
		var input = field.querySelector('[data-rdv-fa-icon-input]');
		var clearBtn = field.querySelector('[data-rdv-fa-icon-clear]');

		if (input) {
			input.value = icon;
		}

		if (!preview) {
			return;
		}

		if (icon) {
			preview.innerHTML = '<i class="fas ' + icon + '" aria-hidden="true"></i>';
			if (clearBtn) {
				clearBtn.hidden = false;
			}
		} else {
			preview.innerHTML = '<span class="rdv-fa-icon-field__placeholder">' + t('previewEmpty') + '</span>';
			if (clearBtn) {
				clearBtn.hidden = true;
			}
		}
	}

	function ensureModal() {
		if (modal) {
			return modal;
		}

		modal = document.createElement('div');
		modal.className = 'rdv-fa-icon-modal';
		modal.innerHTML =
			'<div class="rdv-fa-icon-modal__backdrop" data-rdv-fa-icon-close></div>' +
			'<div class="rdv-fa-icon-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="rdv-fa-icon-modal-title">' +
			'<div class="rdv-fa-icon-modal__header">' +
			'<h2 id="rdv-fa-icon-modal-title">' + t('modalTitle') + '</h2>' +
			'<button type="button" class="button-link" data-rdv-fa-icon-close>' + t('close') + '</button>' +
			'</div>' +
			'<div class="rdv-fa-icon-modal__search-wrap">' +
			'<input type="search" class="rdv-fa-icon-modal__search widefat" placeholder="' + t('search') + '" data-rdv-fa-icon-search />' +
			'</div>' +
			'<div class="rdv-fa-icon-modal__body">' +
			'<p class="rdv-fa-icon-modal__status" data-rdv-fa-icon-status>' + t('loading') + '</p>' +
			'<div class="rdv-fa-icon-modal__grid" data-rdv-fa-icon-grid></div>' +
			'</div>' +
			'<div class="rdv-fa-icon-modal__footer">' +
			'<button type="button" class="button" data-rdv-fa-icon-close>' + t('close') + '</button>' +
			'</div>' +
			'</div>';

		document.body.appendChild(modal);

		modal.addEventListener('click', function (event) {
			if (event.target.matches('[data-rdv-fa-icon-close]')) {
				closeModal();
			}
		});

		var search = modal.querySelector('[data-rdv-fa-icon-search]');
		if (search) {
			search.addEventListener('input', function () {
				renderGrid(search.value);
			});
		}

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && modal.classList.contains('is-open')) {
				closeModal();
			}
		});

		return modal;
	}

	function loadIcons() {
		if (iconsCache && iconsCache.length) {
			return Promise.resolve(iconsCache);
		}

		if (!cfg.metadataUrl) {
			return Promise.resolve([]);
		}

		return fetch(cfg.metadataUrl, { credentials: 'same-origin' })
			.then(function (response) {
				if (!response.ok) {
					throw new Error('metadata_http_' + response.status);
				}
				return response.json();
			})
			.then(function (data) {
				if (Array.isArray(data)) {
					iconsCache = data;
					return iconsCache;
				}

				iconsCache = Object.keys(data)
					.filter(function (name) {
						var item = data[name];
						if (!item || typeof item !== 'object') {
							return false;
						}

						if (item.styles && item.styles.indexOf('solid') !== -1) {
							return true;
						}

						var free = item.familyStylesByLicense && item.familyStylesByLicense.free;
						if (!free || !free.length) {
							return false;
						}

						return free.some(function (entry) {
							return entry && entry.style === 'solid';
						});
					})
					.map(function (name) {
						var item = data[name];
						var terms = (item.search && item.search.terms) ? item.search.terms.join(' ') : '';
						if (item.aliases && item.aliases.names) {
							terms += ' ' + item.aliases.names.join(' ');
						}
						return {
							name: name,
							label: item.label || name,
							terms: terms.trim(),
							faClass: 'fa-' + name
						};
					})
					.sort(function (a, b) {
						return a.label.localeCompare(b.label, 'fr');
					});

				return iconsCache;
			})
			.catch(function () {
				iconsCache = null;
				return [];
			});
	}

	function renderGrid(query) {
		var grid = modal.querySelector('[data-rdv-fa-icon-grid]');
		var status = modal.querySelector('[data-rdv-fa-icon-status]');
		var current = activeField ? normalizeIcon(activeField.querySelector('[data-rdv-fa-icon-input]').value) : '';
		var q = String(query || '').trim().toLowerCase();
		var list = iconsCache || [];
		var max = q ? 240 : 120;

		if (!grid || !status) {
			return;
		}

		if (!list.length) {
			status.textContent = t('loadError');
			grid.innerHTML = '';
			return;
		}

		var filtered = list.filter(function (icon) {
			if (!q) {
				return true;
			}
			return (
				icon.name.indexOf(q) !== -1 ||
				icon.label.toLowerCase().indexOf(q) !== -1 ||
				icon.terms.toLowerCase().indexOf(q) !== -1 ||
				icon.faClass.indexOf(q) !== -1
			);
		}).slice(0, max);

		grid.innerHTML = '';

		if (!filtered.length) {
			status.textContent = t('noResults');
			return;
		}

		status.textContent = filtered.length + ' / ' + list.length;

		filtered.forEach(function (icon) {
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'rdv-fa-icon-modal__item';
			if (icon.faClass === current) {
				button.classList.add('is-selected');
			}
			button.innerHTML = '<i class="fas fa-' + icon.name + '" aria-hidden="true"></i><span>' + icon.label + '</span>';
			button.addEventListener('click', function () {
				if (activeField) {
					updateFieldPreview(activeField, icon.faClass);
				}
				closeModal();
			});
			grid.appendChild(button);
		});
	}

	function openModal(field) {
		activeField = field;
		var modalEl = ensureModal();
		var search = modalEl.querySelector('[data-rdv-fa-icon-search]');
		var status = modalEl.querySelector('[data-rdv-fa-icon-status]');

		modalEl.classList.add('is-open');
		document.body.classList.add('modal-open');

		if (search) {
			search.value = '';
		}
		if (status) {
			status.textContent = t('loading');
		}

		loadIcons().then(function () {
			renderGrid('');
			if (search) {
				search.focus();
			}
		});
	}

	function closeModal() {
		if (!modal) {
			return;
		}
		modal.classList.remove('is-open');
		document.body.classList.remove('modal-open');
		activeField = null;
	}

	function bindField(field) {
		if (!field || field.dataset.rdvFaIconBound === '1') {
			return;
		}
		field.dataset.rdvFaIconBound = '1';

		var input = field.querySelector('[data-rdv-fa-icon-input]');
		var browseBtn = field.querySelector('[data-rdv-fa-icon-browse]');
		var clearBtn = field.querySelector('[data-rdv-fa-icon-clear]');

		if (input) {
			input.addEventListener('change', function () {
				updateFieldPreview(field, normalizeIcon(input.value));
			});
			input.addEventListener('blur', function () {
				updateFieldPreview(field, normalizeIcon(input.value));
			});
		}

		if (browseBtn) {
			browseBtn.addEventListener('click', function () {
				openModal(field);
			});
		}

		if (clearBtn) {
			clearBtn.addEventListener('click', function () {
				updateFieldPreview(field, '');
			});
		}
	}

	function init() {
		document.querySelectorAll('[data-rdv-fa-icon-field]').forEach(bindField);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
