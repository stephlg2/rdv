/**
 * Script pour ajouter un message personnalisé quand les filtres Tripzzy ne retournent aucun résultat
 */

(function($) {
    'use strict';

    console.log('✅ Script tripzzy-no-results-message.js chargé');

    // Message personnalisé à afficher quand aucun résultat
    var noResultsMessage = '<div class="tz-col tz-cols-12-lg tz-no-results-message" style="margin: 20px 0;"><p style="background: #f6efe6; padding: 15px; text-align: center; border-radius: 5px;"><strong>Oups, aucun circuit ne correspond à vos critères de recherches ...</strong><br>Pas de panique, <a href="https://www.rdvasie.com/demande-de-devis/" style="color: #de5b09; text-decoration: underline;">contactez-nous</a> pour personnaliser votre projet de voyage.</p></div>';

    /**
     * Afficher ou masquer le message "Aucun résultat"
     */
    function updateNoResultsMessage(foundPosts) {
        console.log('🔍 updateNoResultsMessage appelé avec foundPosts:', foundPosts);
        
        var $listings = $('#tripzzy-trip-listings');
        var $filterResults = $('#tripzzy-filter-results-list');
        
        console.log('Conteneurs trouvés:', {
            listings: $listings.length,
            filterResults: $filterResults.length
        });
        
        if (foundPosts === 0) {
            // Masquer la toolbar quand aucun résultat
            $('.tz-toolbar').css('display', 'none');
            
            // Afficher le message personnalisé dans le conteneur principal
            if ($listings.length) {
                // Vérifier si le message n'est pas déjà présent
                if ($listings.find('.tz-no-results-message').length === 0) {
                    $listings.html(noResultsMessage);
                    console.log('✅ Message "Aucun résultat" ajouté dans #tripzzy-trip-listings');
                } else {
                    console.log('⚠️ Message déjà présent dans #tripzzy-trip-listings');
                }
            } else {
                console.warn('⚠️ #tripzzy-trip-listings non trouvé');
            }
            
            // Afficher aussi dans le panneau latéral des filtres
            if ($filterResults.length) {
                if ($filterResults.find('.tz-no-results-message').length === 0) {
                    $filterResults.html(noResultsMessage);
                    console.log('✅ Message "Aucun résultat" ajouté dans #tripzzy-filter-results-list');
                }
            }
        } else {
            // Réafficher la toolbar quand il y a des résultats
            $('.tz-toolbar').css('display', '');
            
            // Masquer le message si des résultats sont trouvés
            $('.tz-no-results-message').remove();
            console.log('✅ Message retiré (des résultats trouvés)');
        }
    }

    /**
     * Observer les mutations du DOM pour détecter quand le contenu est vidé
     */
    function observeListingsContainer() {
        var listingsContainer = document.getElementById('tripzzy-trip-listings');
        if (!listingsContainer) {
            console.warn('⚠️ #tripzzy-trip-listings non trouvé pour observer');
            return;
        }

        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    var $listings = $('#tripzzy-trip-listings');
                    var currentContent = $listings.html().trim();
                    var hasTrips = $listings.find('.tripzzy-trip-item, .tz-col:not(.tz-no-results-message)').length > 0;
                    var hasMessage = $listings.find('.tz-no-results-message').length > 0;
                    
                    // Si le conteneur est vide ou ne contient que des espaces
                    if ((currentContent === '' || currentContent === '<br>') && !hasTrips && !hasMessage) {
                        console.log('🔍 Conteneur vidé détecté, vérification du nombre de résultats...');
                        // Vérifier via les données AJAX récentes ou attendre un peu
                        setTimeout(function() {
                            if ($listings.html().trim() === '' || $listings.html().trim() === '<br>') {
                                console.log('✅ Conteneur toujours vide, affichage du message');
                                // Masquer la toolbar
                                $('.tz-toolbar').css('display', 'none');
                                $listings.html(noResultsMessage);
                            }
                        }, 300);
                    } else if (hasTrips && !hasMessage) {
                        // Si des voyages sont présents, réafficher la toolbar
                        $('.tz-toolbar').css('display', '');
                    }
                }
            });
        });

        observer.observe(listingsContainer, {
            childList: true,
            subtree: true
        });
        
        console.log('✅ Observer installé sur #tripzzy-trip-listings');
    }

    /**
     * Intercepter les réponses AJAX de Tripzzy
     */
    function interceptTripzzyAjax() {
        if (typeof jQuery === 'undefined') {
            console.error('❌ jQuery non disponible');
            return;
        }

        console.log('✅ Interception AJAX initialisée');

        // Intercepter les réponses AJAX réussies pour tripzzy_render_trips
        $(document).ajaxSuccess(function(event, xhr, settings) {
            // Vérifier si c'est une requête Tripzzy pour afficher les voyages
            if (settings.url && (settings.url.indexOf('tripzzy_render_trips') !== -1 || settings.url.indexOf('admin-ajax.php') !== -1)) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    console.log('📥 Réponse AJAX reçue:', response);
                    
                    if (response.success && response.data) {
                        var foundPosts = response.data.found_posts || 0;
                        console.log('📊 Nombre de voyages trouvés:', foundPosts);
                        
                        // Attendre un peu pour que Tripzzy mette à jour le DOM
                        setTimeout(function() {
                            updateNoResultsMessage(foundPosts);
                        }, 500);
                    } else if (response.success && response.data && Object.keys(response.data).length === 0) {
                        // Réponse vide peut indiquer aucun résultat
                        console.log('⚠️ Réponse AJAX vide détectée');
                        setTimeout(function() {
                            var $listings = $('#tripzzy-trip-listings');
                            if ($listings.length && ($listings.html().trim() === '' || $listings.html().trim() === '<br>')) {
                                updateNoResultsMessage(0);
                            }
                        }, 500);
                    }
                } catch(e) {
                    console.error('❌ Erreur lors du traitement de la réponse AJAX Tripzzy:', e);
                }
            }
        });

        // Écouter aussi l'événement personnalisé déclenché par Tripzzy
        $(document).on('tripzzy_filter_results_updated', function(e, data) {
            console.log('📢 Événement tripzzy_filter_results_updated déclenché:', data);
            if (data && typeof data.found_posts !== 'undefined') {
                setTimeout(function() {
                    updateNoResultsMessage(data.found_posts);
                }, 200);
            }
        });
    }

    // Initialiser au chargement du DOM
    function init() {
        interceptTripzzyAjax();
        
        // Attendre un peu avant d'installer l'observer pour s'assurer que le DOM est prêt
        setTimeout(function() {
            observeListingsContainer();
        }, 1000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(jQuery);
