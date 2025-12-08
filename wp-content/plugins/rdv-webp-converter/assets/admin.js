/**
 * RDV WebP Converter - Admin JavaScript v3.1
 */

(function($) {
    'use strict';

    // Variables globales
    let converting = false;
    let currentPage = 1;
    let totalPages = 1;
    let selectedImages = [];
    let perPage = 20;

    /**
     * Initialisation
     */
    $(document).ready(function() {
        initBulkConversion();
        initGallery();
        initModals();
        initStatsCards();
    });

    /**
     * Cartes de stats cliquables
     */
    function initStatsCards() {
        $('.rdv-webp-stats-card.clickable').on('click', function() {
            const filter = $(this).data('filter');
            
            // Aller à l'onglet images
            $('.tab-link').removeClass('active');
            $('.tab-link[data-tab="images"]').addClass('active');
            $('.rdv-webp-tab-content').removeClass('active');
            $('#tab-images').addClass('active');
            
            // Réinitialiser les filtres
            $('#rdv-filter-status').val('');
            $('#rdv-filter-type').val('');
            $('#rdv-filter-alt').val('');
            $('#rdv-filter-usage').val('');
            $('#rdv-sort-by').val('date_desc');
            $('#rdv-search').val('');
            
            // Appliquer le filtre approprié
            if (filter === 'converted') {
                $('#rdv-filter-status').val('converted');
            } else if (filter === 'pending') {
                $('#rdv-filter-status').val('pending');
            }
            // Pour 'all', on laisse tout vide
            
            // Recharger les images
            currentPage = 1;
            loadImages();
            
            // Mettre à jour le bouton réinitialiser
            if (filter !== 'all') {
                $('#rdv-filter-reset').show();
            } else {
                $('#rdv-filter-reset').hide();
            }
        });
    }

    /**
     * Conversion en lot (Dashboard)
     */
    function initBulkConversion() {
        const $startBtn = $('#rdv-start-bulk-conversion');
        if (!$startBtn.length) return;

        const $progressContainer = $('.rdv-webp-progress-container');
        const $progressFill = $('.rdv-webp-progress-fill');
        const $progressText = $('.rdv-webp-progress-text');
        const $progressStatus = $('.rdv-webp-progress-status');
        const $results = $('.rdv-webp-results');

        $startBtn.on('click', function() {
            if (converting) return;

            converting = true;
            let offset = 0;
            let converted = 0;
            let total = 0;
            const batchSize = 5;

            $startBtn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Conversion en cours...');
            $progressContainer.show();
            $results.hide();

            // Obtenir le total d'abord
            $.post(rdvWebp.ajaxurl, {
                action: 'rdv_webp_get_stats',
                nonce: rdvWebp.nonce
            }, function(response) {
                if (response.success) {
                    total = response.data.pending;
                    if (total === 0) {
                        finishConversion(0);
                        return;
                    }
                    processNextBatch();
                }
            });

            function processNextBatch() {
                $.post(rdvWebp.ajaxurl, {
                    action: 'rdv_webp_convert_bulk',
                    nonce: rdvWebp.nonce,
                    offset: offset,
                    batch_size: batchSize
                }, function(response) {
                    if (response.success) {
                        converted += response.data.converted;
                        offset += batchSize;

                        let progress = total > 0 ? Math.min(100, Math.round((offset / total) * 100)) : 100;
                        $progressFill.css('width', progress + '%');
                        $progressText.text(progress + '%');
                        $progressStatus.text('Images traitées : ' + Math.min(offset, total) + ' / ' + total);

                        if (response.data.has_more) {
                            processNextBatch();
                        } else {
                            finishConversion(converted);
                        }
                    } else {
                        showError('Erreur lors de la conversion');
                        resetButton();
                    }
                }).fail(function() {
                    showError('Erreur de connexion');
                    resetButton();
                });
            }

            function finishConversion(count) {
                converting = false;
                $results
                    .removeClass('error')
                    .addClass('success')
                    .html('<strong>✅ Conversion terminée !</strong> ' + count + ' images converties en WebP.')
                    .show();
                resetButton();
            }

            function showError(message) {
                $results
                    .removeClass('success')
                    .addClass('error')
                    .html('<strong>❌ Erreur :</strong> ' + message)
                    .show();
            }

            function resetButton() {
                converting = false;
                $startBtn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Convertir toutes les images en attente');
            }
        });
    }

    /**
     * Galerie d'images
     */
    function initGallery() {
        const $gallery = $('#rdv-images-gallery');
        if (!$gallery.length) return;

        // Charger les images
        loadImages();

        // Fonction pour vérifier si des filtres sont actifs
        function hasActiveFilters() {
            return $('#rdv-filter-status').val() !== '' ||
                   $('#rdv-filter-type').val() !== '' ||
                   $('#rdv-filter-alt').val() !== '' ||
                   $('#rdv-filter-usage').val() !== '' ||
                   $('#rdv-sort-by').val() !== 'date_desc' ||
                   $('#rdv-search').val() !== '';
        }

        // Afficher/masquer le bouton Réinitialiser
        function updateResetButton() {
            if (hasActiveFilters()) {
                $('#rdv-filter-reset').show();
            } else {
                $('#rdv-filter-reset').hide();
            }
        }

        // Masquer le bouton au démarrage
        $('#rdv-filter-reset').hide();

        // Filtres
        $('#rdv-filter-apply').on('click', function() {
            currentPage = 1;
            loadImages();
            updateResetButton();
        });

        // Réinitialiser les filtres
        $('#rdv-filter-reset').on('click', function() {
            $('#rdv-filter-status').val('');
            $('#rdv-filter-type').val('');
            $('#rdv-filter-alt').val('');
            $('#rdv-filter-usage').val('');
            $('#rdv-sort-by').val('date_desc');
            $('#rdv-search').val('');
            currentPage = 1;
            loadImages();
            $(this).hide();
        });

        $('#rdv-search').on('keypress', function(e) {
            if (e.which === 13) {
                currentPage = 1;
                loadImages();
                updateResetButton();
            }
        });

        // Nombre d'images par page
        $('#rdv-per-page').on('change', function() {
            perPage = parseInt($(this).val()) || 24;
            currentPage = 1;
            loadImages();
        });

        // Pagination
        $('#rdv-prev-page').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadImages();
            }
        });

        $('#rdv-next-page').on('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                loadImages();
            }
        });

        // Vue grille/liste
        $('#rdv-view-grid').on('click', function() {
            $(this).addClass('active').siblings().removeClass('active');
            $gallery.removeClass('rdv-gallery-list').addClass('rdv-gallery-grid');
        });

        $('#rdv-view-list').on('click', function() {
            $(this).addClass('active').siblings().removeClass('active');
            $gallery.removeClass('rdv-gallery-grid').addClass('rdv-gallery-list');
        });

        // Sélection d'images
        $gallery.on('click', '.item-checkbox', function(e) {
            e.stopPropagation();
            const $item = $(this).closest('.rdv-gallery-item');
            const id = $item.data('id');
            
            $item.toggleClass('selected');
            
            if ($item.hasClass('selected')) {
                if (!selectedImages.includes(id)) {
                    selectedImages.push(id);
                }
            } else {
                selectedImages = selectedImages.filter(i => i !== id);
            }
            
            updateSelectionBar();
        });

        // Clic sur l'item pour ouvrir le modal
        $gallery.on('click', '.rdv-gallery-item', function(e) {
            if ($(e.target).closest('.item-checkbox, .item-actions').length) return;
            const id = $(this).data('id');
            openEditModal(id);
        });

        // Actions rapides
        $gallery.on('click', '.btn-convert', function(e) {
            e.stopPropagation();
            const id = $(this).closest('.rdv-gallery-item').data('id');
            convertSingle(id);
        });

        $gallery.on('click', '.btn-edit', function(e) {
            e.stopPropagation();
            const id = $(this).closest('.rdv-gallery-item').data('id');
            openEditModal(id);
        });

        // Actions sur la sélection
        $('#rdv-convert-selected').on('click', function() {
            if (selectedImages.length === 0) return;
            convertSelected();
        });

        $('#rdv-edit-selected').on('click', function() {
            if (selectedImages.length === 0) return;
            openBulkEditModal();
        });

        $('#rdv-clear-selection').on('click', function() {
            clearSelection();
        });
    }

    /**
     * Charge les images
     */
    function loadImages() {
        const $gallery = $('#rdv-images-gallery');
        const $loading = $('#rdv-images-loading');

        $loading.show();
        $gallery.hide();

        $.post(rdvWebp.ajaxurl, {
            action: 'rdv_webp_get_images',
            nonce: rdvWebp.nonce,
            page: currentPage,
            per_page: perPage,
            status: $('#rdv-filter-status').val(),
            type: $('#rdv-filter-type').val(),
            alt_filter: $('#rdv-filter-alt').val(),
            usage_filter: $('#rdv-filter-usage').val(),
            sort_by: $('#rdv-sort-by').val(),
            search: $('#rdv-search').val()
        }, function(response) {
            $loading.hide();

            if (response.success) {
                totalPages = response.data.pages;
                renderGallery(response.data.images);
                updatePagination(response.data);
                $gallery.show();
            }
        });
    }

    /**
     * Affiche la galerie
     */
    function renderGallery(images) {
        const $gallery = $('#rdv-images-gallery');
        $gallery.empty();

        if (images.length === 0) {
            $gallery.html('<p style="text-align: center; padding: 40px; color: #666;">Aucune image trouvée</p>');
            return;
        }

        images.forEach(function(image) {
            const statusClass = image.is_converted ? 'converted' : 'pending';
            const statusText = image.is_converted ? 'WebP' : 'En attente';
            const altText = image.alt ? image.alt : '<span class="missing">Alt manquant</span>';
            const isSelected = selectedImages.includes(image.id);

            const item = `
                <div class="rdv-gallery-item ${isSelected ? 'selected' : ''}" data-id="${image.id}">
                    <div class="item-checkbox"></div>
                    <div class="item-thumbnail">
                        <img src="${image.thumbnail || ''}" alt="${escapeHtml(image.alt)}" />
                        <span class="item-status ${statusClass}">${statusText}</span>
                    </div>
                    <div class="item-info">
                        <div class="item-title" title="${escapeHtml(image.filename)}">${escapeHtml(image.filename)}</div>
                        <div class="item-meta">
                            <span>${image.original_size}</span>
                            ${image.is_converted ? '<span class="savings">-' + image.savings + '</span>' : ''}
                        </div>
                        <div class="item-alt">${altText}</div>
                    </div>
                    <div class="item-actions">
                        ${!image.is_converted ? 
                            '<button class="button btn-convert" title="Convertir en WebP"><span class="dashicons dashicons-update"></span></button>' : 
                            ''
                        }
                        <button class="button btn-edit" title="Modifier"><span class="dashicons dashicons-edit"></span></button>
                    </div>
                </div>
            `;
            $gallery.append(item);
        });
    }

    /**
     * Met à jour la barre de sélection
     */
    function updateSelectionBar() {
        const $bar = $('.rdv-webp-selection-bar');
        const count = selectedImages.length;
        
        if (count > 0) {
            $('#rdv-selection-count').text(count);
            $bar.slideDown(200);
        } else {
            $bar.slideUp(200);
        }
    }

    /**
     * Efface la sélection
     */
    function clearSelection() {
        selectedImages = [];
        $('.rdv-gallery-item').removeClass('selected');
        updateSelectionBar();
    }

    /**
     * Met à jour la pagination
     */
    function updatePagination(data) {
        $('#rdv-page-info').text('Page ' + data.current_page + ' sur ' + data.pages + ' (' + data.total + ' images)');
        $('#rdv-prev-page').prop('disabled', data.current_page <= 1);
        $('#rdv-next-page').prop('disabled', data.current_page >= data.pages);
    }

    /**
     * Initialise les modals
     */
    function initModals() {
        // Fermer les modals
        $('.rdv-modal-close, .rdv-modal-overlay').on('click', function() {
            closeModals();
        });

        // Empêcher la propagation du clic dans le contenu
        $('.rdv-modal-content').on('click', function(e) {
            e.stopPropagation();
        });

        // ESC pour fermer
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModals();
            }
        });

        // Sauvegarder (modal simple)
        $('#rdv-edit-save').on('click', function() {
            saveImageEdit();
        });

        // Convertir depuis le modal avec qualité personnalisée
        $('#rdv-edit-convert').on('click', function() {
            const id = $('#rdv-edit-id').val();
            const quality = $('#rdv-edit-quality').val();
            if (id) {
                convertSingleWithQuality(id, quality, function(response) {
                    // Mettre à jour l'affichage dans le modal
                    if (response.data) {
                        $('#rdv-edit-webp-status').html(
                            '<span style="color: #28a745;">✅ Converti (' + response.data.webp_size + ') - Économie : ' + response.data.savings + '</span>'
                        );
                    }
                    // Recharger les détails
                    loadImageDetails(id);
                    // Recharger la liste
                    loadImages();
                    showNotice('✅ Image convertie à ' + quality + '% de qualité !', 'success');
                });
            }
        });

        // Annuler WebP (supprimer les fichiers WebP)
        $('#rdv-edit-revert').on('click', function() {
            const id = $('#rdv-edit-id').val();
            if (!id) return;
            
            if (!confirm('Êtes-vous sûr de vouloir supprimer les fichiers WebP de cette image ?\nL\'image originale (JPG/PNG) sera servie à la place.')) {
                return;
            }
            
            const $btn = $(this);
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Suppression...');
            
            $.post(rdvWebp.ajaxurl, {
                action: 'rdv_webp_restore_original',
                nonce: rdvWebp.nonce,
                attachment_id: id
            }, function(response) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-undo"></span> Annuler WebP');
                
                if (response.success) {
                    $('#rdv-edit-webp-status').html(
                        '<span style="color: #ffc107;">⏳ Non converti</span>'
                    );
                    $('#rdv-edit-convert').show();
                    $('#rdv-edit-revert').hide();
                    loadImages();
                    showNotice('✅ ' + response.data.message, 'success');
                } else {
                    showNotice('❌ ' + (response.data || 'Erreur'), 'error');
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-undo"></span> Annuler WebP');
                showNotice('❌ Erreur de connexion', 'error');
            });
        });

        // Sauvegarder (modal bulk)
        $('#rdv-bulk-save').on('click', function() {
            saveBulkEdit();
        });

        // Convertir tout (modal bulk)
        $('#rdv-bulk-convert').on('click', function() {
            convertSelected(function() {
                closeModals();
                loadImages();
            });
        });
    }

    /**
     * Ouvre le modal d'édition
     */
    function openEditModal(id) {
        loadImageDetails(id);
        $('#rdv-edit-modal').fadeIn(200);
        $('body').css('overflow', 'hidden');
    }

    /**
     * Charge les détails d'une image
     */
    function loadImageDetails(id) {
        $.post(rdvWebp.ajaxurl, {
            action: 'rdv_webp_get_image_details',
            nonce: rdvWebp.nonce,
            attachment_id: id
        }, function(response) {
            if (response.success) {
                const img = response.data;
                
                $('#rdv-edit-id').val(img.id);
                $('#rdv-edit-image').attr('src', img.thumbnail);
                $('#rdv-edit-filename').val(img.filename);
                $('#rdv-edit-extension').text(img.extension);
                $('#rdv-edit-alt').val(img.alt);
                $('#rdv-edit-title').val(img.title);
                $('#rdv-edit-type').text(img.type);
                $('#rdv-edit-size').text(img.original_size);
                $('#rdv-edit-dimensions').text(img.dimensions);
                $('#rdv-edit-webp-status').html(img.is_converted ? 
                    '<span style="color: #28a745;">✅ Converti (' + img.webp_size + ')</span>' : 
                    '<span style="color: #ffc107;">⏳ Non converti</span>'
                );

                // Afficher les pages où l'image est utilisée
                const $usageList = $('#rdv-edit-used-in');
                $usageList.empty();
                
                if (img.used_in && img.used_in.length > 0) {
                    img.used_in.forEach(function(item) {
                        const typeLabel = item.type === 'page' ? 'Page' : (item.type === 'post' ? 'Article' : item.type);
                        $usageList.append(`
                            <div class="usage-item">
                                <div>
                                    <span class="usage-title">${escapeHtml(item.title)}</span>
                                    <span class="usage-type">${typeLabel}</span>
                                </div>
                                <div class="usage-links">
                                    <a href="${item.url}" target="_blank">
                                        <span class="dashicons dashicons-external"></span> Voir
                                    </a>
                                    <a href="${item.edit_url}" target="_blank">
                                        <span class="dashicons dashicons-edit"></span> Modifier
                                    </a>
                                </div>
                            </div>
                        `);
                    });
                } else {
                    $usageList.html('<span class="no-usage">Cette image n\'est utilisée sur aucune page publiée</span>');
                }

                // Afficher/masquer les boutons de conversion/annulation
                if (img.is_converted) {
                    $('#rdv-edit-convert').hide();
                    $('#rdv-edit-revert').show();
                } else {
                    $('#rdv-edit-convert').show();
                    $('#rdv-edit-revert').hide();
                }
            }
        });
    }

    /**
     * Sauvegarde les modifications d'une image
     */
    function saveImageEdit() {
        const id = $('#rdv-edit-id').val();
        const filename = $('#rdv-edit-filename').val();
        const alt = $('#rdv-edit-alt').val();
        const title = $('#rdv-edit-title').val();

        const $btn = $('#rdv-edit-save');
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Enregistrement...');

        // D'abord mettre à jour alt et titre
        $.post(rdvWebp.ajaxurl, {
            action: 'rdv_webp_update_alt',
            nonce: rdvWebp.nonce,
            attachment_id: id,
            alt: alt,
            title: title
        }, function(response) {
            // Ensuite renommer si nécessaire
            $.post(rdvWebp.ajaxurl, {
                action: 'rdv_webp_rename_file',
                nonce: rdvWebp.nonce,
                attachment_id: id,
                filename: filename
            }, function(response) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Enregistrer');
                
                if (response.success) {
                    closeModals();
                    loadImages();
                    showNotice('✅ Image mise à jour avec succès !', 'success');
                } else {
                    showNotice('❌ ' + (response.data || 'Erreur lors de la mise à jour'), 'error');
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Enregistrer');
                showNotice('❌ Erreur de connexion', 'error');
            });
        });
    }

    /**
     * Ouvre le modal d'édition multiple
     */
    function openBulkEditModal() {
        const $list = $('#rdv-bulk-edit-list');
        $list.empty();

        // Charger les détails de chaque image sélectionnée
        let loaded = 0;
        selectedImages.forEach(function(id) {
            $.post(rdvWebp.ajaxurl, {
                action: 'rdv_webp_get_image_details',
                nonce: rdvWebp.nonce,
                attachment_id: id
            }, function(response) {
                if (response.success) {
                    const img = response.data;
                    const item = `
                        <div class="rdv-bulk-edit-item" data-id="${img.id}">
                            <img src="${img.thumbnail}" alt="" />
                            <div class="edit-fields">
                                <input type="text" class="bulk-filename" value="${escapeHtml(img.filename)}" placeholder="Nom du fichier" />
                                <input type="text" class="bulk-title" value="${escapeHtml(img.title)}" placeholder="Titre" />
                                <input type="text" class="bulk-alt field-full" value="${escapeHtml(img.alt)}" placeholder="Texte alternatif (alt)" />
                            </div>
                        </div>
                    `;
                    $list.append(item);
                }
                
                loaded++;
                if (loaded === selectedImages.length) {
                    $('#rdv-bulk-edit-modal').fadeIn(200);
                    $('body').css('overflow', 'hidden');
                }
            });
        });
    }

    /**
     * Sauvegarde les modifications multiples
     */
    function saveBulkEdit() {
        const updates = [];
        
        $('#rdv-bulk-edit-list .rdv-bulk-edit-item').each(function() {
            const $item = $(this);
            updates.push({
                id: $item.data('id'),
                filename: $item.find('.bulk-filename').val(),
                title: $item.find('.bulk-title').val(),
                alt: $item.find('.bulk-alt').val()
            });
        });

        const $btn = $('#rdv-bulk-save');
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Enregistrement...');

        $.post(rdvWebp.ajaxurl, {
            action: 'rdv_webp_bulk_update',
            nonce: rdvWebp.nonce,
            updates: updates
        }, function(response) {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Enregistrer tout');
            
            if (response.success) {
                closeModals();
                clearSelection();
                loadImages();
                showNotice('✅ ' + response.data.success + ' images mises à jour !', 'success');
            } else {
                showNotice('❌ Erreur lors de la mise à jour', 'error');
            }
        });
    }

    /**
     * Ferme tous les modals
     */
    function closeModals() {
        $('.rdv-modal').fadeOut(200);
        $('body').css('overflow', '');
    }

    /**
     * Convertit une seule image
     */
    function convertSingle(id, callback) {
        const $item = $(`.rdv-gallery-item[data-id="${id}"]`);
        const $btn = $item.find('.btn-convert');
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');

        $.post(rdvWebp.ajaxurl, {
            action: 'rdv_webp_convert_single',
            nonce: rdvWebp.nonce,
            attachment_id: id
        }, function(response) {
            if (response.success) {
                if (callback) {
                    callback();
                } else {
                    loadImages();
                }
                showNotice('✅ Image convertie en WebP !', 'success');
            } else {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span>');
                showNotice('❌ Erreur lors de la conversion', 'error');
            }
        });
    }

    /**
     * Convertit une seule image avec qualité personnalisée
     */
    function convertSingleWithQuality(id, quality, callback) {
        const $btn = $('#rdv-edit-convert');
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Conversion...');

        $.post(rdvWebp.ajaxurl, {
            action: 'rdv_webp_convert_single',
            nonce: rdvWebp.nonce,
            attachment_id: id,
            quality: quality
        }, function(response) {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Convertir en WebP');
            
            if (response.success) {
                if (callback) {
                    callback(response);
                }
            } else {
                showNotice('❌ Erreur lors de la conversion', 'error');
            }
        }).fail(function() {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Convertir en WebP');
            showNotice('❌ Erreur de connexion', 'error');
        });
    }

    /**
     * Convertit les images sélectionnées
     */
    function convertSelected(callback) {
        if (selectedImages.length === 0) return;

        const $btn = $('#rdv-convert-selected');
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Conversion...');

        $.post(rdvWebp.ajaxurl, {
            action: 'rdv_webp_convert_selected',
            nonce: rdvWebp.nonce,
            ids: selectedImages
        }, function(response) {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Convertir la sélection');
            
            if (response.success) {
                if (callback) {
                    callback();
                } else {
                    clearSelection();
                    loadImages();
                }
                showNotice('✅ ' + response.data.converted + ' images converties !', 'success');
            } else {
                showNotice('❌ Erreur lors de la conversion', 'error');
            }
        });
    }

    /**
     * Affiche une notification
     */
    function showNotice(message, type) {
        const $notice = $(`
            <div class="rdv-notice ${type}" style="position: fixed; top: 50px; right: 20px; padding: 15px 20px; background: ${type === 'success' ? '#d4edda' : '#f8d7da'}; border: 1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'}; border-radius: 8px; z-index: 100001; animation: slideIn 0.3s ease;">
                ${message}
            </div>
        `);

        $('body').append($notice);

        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * Utilitaire : échappe le HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})(jQuery);

// CSS pour les animations
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .dashicons.spin {
        animation: spin 1s linear infinite;
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }
`;
document.head.appendChild(style);
