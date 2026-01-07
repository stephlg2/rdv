/**
 * RDV Sitemap Pro - Admin JavaScript
 */

(function($) {
    'use strict';

    // Variables globales pour le modal
    let currentType = '';
    let currentIsTaxonomy = false;
    let currentUrls = [];

    $(document).ready(function() {
        
        // ==========================================
        // GESTION DES CARTES CLIQUABLES
        // ==========================================
        
        $('.stat-card.clickable').on('click', function() {
            const type = $(this).data('type');
            const label = $(this).data('label');
            const isTaxonomy = $(this).data('taxonomy') === 1;
            
            openUrlsModal(type, label, isTaxonomy);
        });

        // ==========================================
        // MODAL - GESTION DES URLs
        // ==========================================
        
        function openUrlsModal(type, label, isTaxonomy) {
            currentType = type;
            currentIsTaxonomy = isTaxonomy;
            
            // Mettre à jour le titre
            $('#modal-title .title-text').text('Gérer les ' + label);
            
            // Afficher le modal
            $('#urls-modal').fadeIn(200);
            $('body').css('overflow', 'hidden');
            
            // Charger les URLs
            loadUrls();
        }

        function loadUrls() {
            const $list = $('#urls-list');
            $list.html('<div class="loading-spinner"><span class="dashicons dashicons-update spin"></span> Chargement...</div>');
            
            $.post(rdvSitemap.ajaxurl, {
                action: 'rdv_sitemap_get_urls',
                nonce: rdvSitemap.nonce,
                type: currentType,
                taxonomy: currentIsTaxonomy ? 1 : 0
            }, function(response) {
                if (response.success) {
                    currentUrls = response.data.urls;
                    renderUrlsList(currentUrls);
                    updateSelectedCount();
                } else {
                    $list.html('<div class="error-message">❌ ' + (response.data || 'Erreur') + '</div>');
                }
            }).fail(function() {
                $list.html('<div class="error-message">❌ Erreur de connexion</div>');
            });
        }

        function renderUrlsList(urls) {
            const $list = $('#urls-list');
            
            if (urls.length === 0) {
                $list.html('<div class="no-results">Aucune URL trouvée</div>');
                return;
            }
            
            let html = '<table class="urls-table"><thead><tr>';
            html += '<th class="col-checkbox"><input type="checkbox" id="header-select-all" checked></th>';
            html += '<th class="col-title">Titre</th>';
            html += '<th class="col-url">URL</th>';
            html += '<th class="col-info">Info</th>';
            html += '</tr></thead><tbody>';
            
            urls.forEach(function(url) {
                const checked = url.included ? 'checked' : '';
                const rowClass = url.included ? '' : 'excluded';
                
                html += '<tr class="url-row ' + rowClass + '" data-id="' + url.id + '">';
                html += '<td class="col-checkbox"><input type="checkbox" class="url-checkbox" data-id="' + url.id + '" ' + checked + '></td>';
                html += '<td class="col-title">' + escapeHtml(url.title) + '</td>';
                html += '<td class="col-url"><a href="' + url.url + '" target="_blank" class="url-link">' + url.url + ' <span class="dashicons dashicons-external"></span></a></td>';
                html += '<td class="col-info">' + (url.date || url.count || '') + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            $list.html(html);
            
            // Sync header checkbox
            updateHeaderCheckbox();
        }

        function updateSelectedCount() {
            const total = currentUrls.length;
            const selected = currentUrls.filter(u => u.included).length;
            $('#selected-count').text(selected + '/' + total);
        }

        function updateHeaderCheckbox() {
            const allChecked = $('.url-checkbox:not(:checked)').length === 0;
            $('#header-select-all, #select-all-urls').prop('checked', allChecked);
        }

        // Checkbox individuelle
        $(document).on('change', '.url-checkbox', function() {
            const id = $(this).data('id');
            const isChecked = $(this).is(':checked');
            
            // Mettre à jour l'état local
            const url = currentUrls.find(u => u.id === id);
            if (url) {
                url.included = isChecked;
            }
            
            // Style de la ligne
            $(this).closest('tr').toggleClass('excluded', !isChecked);
            
            updateSelectedCount();
            updateHeaderCheckbox();
        });

        // Select all (dans toolbar)
        $('#select-all-urls').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.url-checkbox').prop('checked', isChecked).trigger('change');
            $('#header-select-all').prop('checked', isChecked);
        });

        // Select all (dans header tableau)
        $(document).on('change', '#header-select-all', function() {
            const isChecked = $(this).is(':checked');
            $('.url-checkbox').prop('checked', isChecked);
            $('#select-all-urls').prop('checked', isChecked);
            
            // Mettre à jour l'état local
            currentUrls.forEach(u => u.included = isChecked);
            
            // Style des lignes
            $('.url-row').toggleClass('excluded', !isChecked);
            
            updateSelectedCount();
        });

        // Recherche
        $('#url-search').on('input', function() {
            const search = $(this).val().toLowerCase();
            
            if (search === '') {
                renderUrlsList(currentUrls);
            } else {
                const filtered = currentUrls.filter(function(url) {
                    return url.title.toLowerCase().includes(search) || 
                           url.url.toLowerCase().includes(search);
                });
                renderUrlsList(filtered);
            }
        });

        // Fermer le modal
        $('.rdv-modal-close, .rdv-modal-overlay, #modal-cancel').on('click', function() {
            closeModal();
        });

        function closeModal() {
            $('#urls-modal').fadeOut(200);
            $('body').css('overflow', '');
            currentUrls = [];
            currentType = '';
            $('#url-search').val('');
        }

        // ESC pour fermer
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#urls-modal').is(':visible')) {
                closeModal();
            }
        });

        // Sauvegarder les exclusions
        $('#modal-save').on('click', function() {
            const $btn = $(this);
            const originalHTML = $btn.html();
            
            // Récupérer les IDs à exclure (non cochés) et à inclure (cochés)
            const toExclude = currentUrls.filter(u => !u.included).map(u => u.id);
            const toInclude = currentUrls.filter(u => u.included).map(u => u.id);
            
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Sauvegarde...');
            
            $.post(rdvSitemap.ajaxurl, {
                action: 'rdv_sitemap_save_exclusions',
                nonce: rdvSitemap.nonce,
                to_exclude: toExclude,
                to_include: toInclude
            }, function(response) {
                $btn.prop('disabled', false).html(originalHTML);
                
                if (response.success) {
                    // Mettre à jour les stats affichées
                    if (response.data.stats) {
                        updateStatsDisplay(response.data.stats);
                    }
                    
                    showNotice('✅ ' + response.data.message, 'success');
                    closeModal();
                } else {
                    showNotice('❌ ' + (response.data || 'Erreur'), 'error');
                }
            }).fail(function() {
                $btn.prop('disabled', false).html(originalHTML);
                showNotice('❌ Erreur de connexion', 'error');
            });
        });

        function updateStatsDisplay(stats) {
            if (stats.total_urls !== undefined) {
                $('.stat-card.total .stat-number').text(stats.total_urls.toLocaleString());
            }
        }

        // ==========================================
        // ACTIONS EXISTANTES
        // ==========================================
        
        // Régénérer le sitemap
        $('#regenerate-sitemap').on('click', function() {
            const $btn = $(this);
            const originalHTML = $btn.html();
            
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Régénération...');
            
            $.post(rdvSitemap.ajaxurl, {
                action: 'rdv_sitemap_regenerate',
                nonce: rdvSitemap.nonce
            }, function(response) {
                $btn.prop('disabled', false).html(originalHTML);
                
                if (response.success) {
                    showResult('success', '✅ ' + response.data.message);
                } else {
                    showResult('error', '❌ ' + (response.data || 'Erreur'));
                }
            }).fail(function() {
                $btn.prop('disabled', false).html(originalHTML);
                showResult('error', '❌ Erreur de connexion');
            });
        });
        
        // Ping Google & Bing
        $('#ping-search-engines').on('click', function() {
            const $btn = $(this);
            const originalHTML = $btn.html();
            
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Notification...');
            
            $.post(rdvSitemap.ajaxurl, {
                action: 'rdv_sitemap_ping',
                nonce: rdvSitemap.nonce
            }, function(response) {
                $btn.prop('disabled', false).html(originalHTML);
                
                if (response.success) {
                    let message = '📣 Notification envoyée :\n';
                    message += response.data.google ? '✅ Google OK' : '❌ Google échec';
                    message += '\n';
                    message += response.data.bing ? '✅ Bing OK' : '❌ Bing échec';
                    showResult('success', message);
                } else {
                    showResult('error', '❌ ' + (response.data || 'Erreur'));
                }
            }).fail(function() {
                $btn.prop('disabled', false).html(originalHTML);
                showResult('error', '❌ Erreur de connexion');
            });
        });
        
        // Copier le code robots.txt
        $('.copy-robots').on('click', function() {
            const text = $(this).data('copy');
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    const $btn = $(this);
                    const originalHTML = $btn.html();
                    $btn.html('<span class="dashicons dashicons-yes"></span> Copié !');
                    setTimeout(() => $btn.html(originalHTML), 2000);
                });
            } else {
                // Fallback
                const $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(text).select();
                document.execCommand('copy');
                $temp.remove();
                
                const $btn = $(this);
                const originalHTML = $btn.html();
                $btn.html('<span class="dashicons dashicons-yes"></span> Copié !');
                setTimeout(() => $btn.html(originalHTML), 2000);
            }
        });
        
        // Sauvegarder les réglages
        $('#sitemap-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            const $btn = $(this).find('button[type="submit"]');
            const originalHTML = $btn.html();
            
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Enregistrement...');
            
            // Collecter les données du formulaire
            const formData = {};
            
            // Options booléennes
            formData.enable_xml = $('input[name="enable_xml"]').is(':checked');
            formData.enable_html = $('input[name="enable_html"]').is(':checked');
            formData.enable_llms_txt = $('input[name="enable_llms_txt"]').is(':checked');
            formData.enable_images = $('input[name="enable_images"]').is(':checked');
            formData.auto_ping = $('input[name="auto_ping"]').is(':checked');
            
            // Options texte
            formData.homepage_priority = $('#homepage_priority').val();
            formData.excluded_ids = $('#excluded_ids').val();
            
            // Post types
            formData.post_types = {
                tripzzy: {
                    enabled: $('input[name="post_types[tripzzy][enabled]"]').is(':checked'),
                    priority: $('select[name="post_types[tripzzy][priority]"]').val(),
                    changefreq: $('select[name="post_types[tripzzy][changefreq]"]').val()
                },
                avada_faq: {
                    enabled: $('input[name="post_types[avada_faq][enabled]"]').is(':checked'),
                    priority: $('select[name="post_types[avada_faq][priority]"]').val(),
                    changefreq: $('select[name="post_types[avada_faq][changefreq]"]').val()
                },
                post: {
                    enabled: $('input[name="post_types[post][enabled]"]').is(':checked'),
                    priority: $('select[name="post_types[post][priority]"]').val(),
                    changefreq: $('select[name="post_types[post][changefreq]"]').val()
                },
                page: {
                    enabled: $('input[name="post_types[page][enabled]"]').is(':checked'),
                    priority: $('select[name="post_types[page][priority]"]').val(),
                    changefreq: $('select[name="post_types[page][changefreq]"]').val()
                }
            };
            
            // Taxonomies
            formData.taxonomies = {
                tripzzy_trip_destination: {
                    enabled: $('input[name="taxonomies[tripzzy_trip_destination][enabled]"]').is(':checked'),
                    priority: $('select[name="taxonomies[tripzzy_trip_destination][priority]"]').val()
                },
                tripzzy_trip_type: {
                    enabled: $('input[name="taxonomies[tripzzy_trip_type][enabled]"]').is(':checked'),
                    priority: $('select[name="taxonomies[tripzzy_trip_type][priority]"]').val()
                },
                category: {
                    enabled: $('input[name="taxonomies[category][enabled]"]').is(':checked'),
                    priority: $('select[name="taxonomies[category][priority]"]').val()
                }
            };
            
            $.post(rdvSitemap.ajaxurl, {
                action: 'rdv_sitemap_save_settings',
                nonce: rdvSitemap.nonce,
                settings: formData
            }, function(response) {
                $btn.prop('disabled', false).html(originalHTML);
                
                if (response.success) {
                    showResult('success', '✅ ' + response.data.message);
                } else {
                    showResult('error', '❌ ' + (response.data || 'Erreur'));
                }
            }).fail(function() {
                $btn.prop('disabled', false).html(originalHTML);
                showResult('error', '❌ Erreur de connexion');
            });
        });
        
        // ==========================================
        // UTILITAIRES
        // ==========================================
        
        function showResult(type, message) {
            const $result = $('#action-result');
            $result.removeClass('success error').addClass(type).html(message.replace(/\n/g, '<br>')).slideDown();
            
            setTimeout(function() {
                $result.slideUp();
            }, 5000);
        }

        function showNotice(message, type) {
            const $notice = $('<div class="rdv-notice ' + type + '">' + message + '</div>');
            $('body').append($notice);
            
            setTimeout(function() {
                $notice.fadeOut(300, function() { $(this).remove(); });
            }, 3000);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
    });

})(jQuery);

// CSS dynamique pour animations
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .dashicons.spin {
        animation: spin 1s linear infinite;
    }
    .rdv-notice {
        position: fixed;
        top: 50px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        z-index: 100001;
        font-weight: 500;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
    .rdv-notice.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .rdv-notice.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
`;
document.head.appendChild(style);
