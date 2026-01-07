/**
 * RDV Sitemap Pro - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
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
            formData.robots_manage = $('input[name="robots_manage"]').is(':checked');
            formData.robots_add_sitemap = $('input[name="robots_add_sitemap"]').is(':checked');
            
            // Options texte
            formData.homepage_priority = $('#homepage_priority').val();
            formData.excluded_ids = $('#excluded_ids').val();
            formData.robots_extra_rules = $('#robots_extra_rules').val();
            
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
        
        // Afficher le résultat
        function showResult(type, message) {
            const $result = $('#action-result');
            $result.removeClass('success error').addClass(type).html(message.replace(/\n/g, '<br>')).slideDown();
            
            setTimeout(function() {
                $result.slideUp();
            }, 5000);
        }
        
    });

})(jQuery);

