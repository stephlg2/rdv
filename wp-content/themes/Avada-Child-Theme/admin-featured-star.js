/**
 * Script pour réparer le clic sur l'étoile "À la une" dans l'administration WordPress
 * Ce script gère le clic sur les étoiles "À la une" dans la liste des voyages Tripzzy
 */

(function($) {
    'use strict';

    /**
     * Initialiser la fonctionnalité "À la une"
     */
    function initFeaturedStar() {
        // Sélectionner les étoiles "À la une" avec la classe utilisée par Tripzzy
        var selector = '.tripzzy-featured-trip';
        var starLinks = $(selector);

        if (!starLinks || starLinks.length === 0) {
            console.log('Aucune étoile "À la une" trouvée, réessai dans 500ms...');
            // Réessayer après un court délai au cas où le DOM n'est pas encore chargé
            setTimeout(initFeaturedStar, 500);
            return;
        }

        console.log('✅ Étoiles "À la une" trouvées:', starLinks.length);

        // Attacher l'événement de clic sur les étoiles Tripzzy
        $(document).off('click', selector);
        $(document).on('click', selector, function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $link = $(this);
            
            // Récupérer l'ID du voyage depuis l'attribut data-trip-id
            var tripId = $link.attr('data-trip-id');
            
            // Si pas de data-trip-id, essayer de le trouver depuis la ligne du tableau
            if (!tripId) {
                var $row = $link.closest('tr');
                if ($row.length) {
                    var checkbox = $row.find('input[type="checkbox"][name="post[]"]').val();
                    if (checkbox) {
                        tripId = checkbox;
                    }
                }
            }

            if (!tripId) {
                console.error('Impossible de trouver l\'ID du voyage');
                return false;
            }

            console.log('Clic sur étoile "À la une" - Trip ID:', tripId);

            // Toggle avec AJAX
            toggleFeatured(tripId, $link);
            
            return false;
        });
    }

    /**
     * Basculer le statut "À la une" via AJAX
     * Utilise l'action AJAX du plugin Tripzzy: tripzzy_set_featured_trip
     */
    function toggleFeatured(tripId, $link) {
        // Vérifier si le voyage est actuellement "À la une"
        // Le plugin utilise les classes avec espaces : " dashicons-star-empty " ou " dashicons-star-filled "
        var classes = $link.attr('class') || '';
        var isFeatured = classes.indexOf('dashicons-star-filled') !== -1;
        
        console.log('Toggle featured - Trip ID:', tripId, 'Classes actuelles:', classes, 'Actuellement featured:', isFeatured);

        // Construire l'URL AJAX
        var ajaxUrl = typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php';
        
        // Récupérer le nonce Tripzzy depuis l'objet global tripzzy
        var nonce = '';
        if (typeof tripzzy !== 'undefined' && tripzzy.nonce) {
            nonce = tripzzy.nonce;
            console.log('Nonce récupéré depuis tripzzy.nonce');
        } else {
            // Essayer de récupérer le nonce depuis un input caché dans la page
            var $nonceInput = $('input[name="tripzzy_nonce"]');
            if ($nonceInput.length) {
                nonce = $nonceInput.val();
                console.log('Nonce récupéré depuis input[name="tripzzy_nonce"]');
            } else {
                console.error('❌ Nonce Tripzzy non trouvé !');
                alert('Erreur : Nonce de sécurité non trouvé. Veuillez recharger la page.');
                return;
            }
        }

        if (!nonce) {
            console.error('❌ Nonce vide !');
            alert('Erreur : Nonce de sécurité invalide. Veuillez recharger la page.');
            return;
        }

        // Le plugin Tripzzy attend :
        // 1. trip_id dans INPUT_PAYLOAD (JSON dans le body de la requête via php://input)
        // 2. tripzzy_nonce dans $_REQUEST (donc dans les paramètres GET/POST de l'URL)
        // On doit donc envoyer le JSON dans le body ET le nonce en paramètre GET/POST
        
        var jsonPayload = JSON.stringify({
            trip_id: tripId
        });
        
        // Construire l'URL avec les paramètres (nonce dans $_REQUEST via GET)
        var urlWithParams = ajaxUrl + '?' + $.param({
            action: 'tripzzy_set_featured_trip',
            tripzzy_nonce: nonce
        });

        console.log('Envoi de la requête AJAX Tripzzy:', { 
            action: 'tripzzy_set_featured_trip', 
            trip_id: tripId, 
            nonce_present: !!nonce,
            payload: jsonPayload,
            url: urlWithParams
        });

        // Désactiver le lien pendant le traitement
        $link.css('pointer-events', 'none').addClass('updating');

        // Envoyer la requête AJAX avec JSON dans le body et nonce en paramètre GET
        $.ajax({
            url: urlWithParams,  // URL avec nonce en paramètre GET
            type: 'POST',
            data: jsonPayload,  // JSON dans le body pour trip_id (INPUT_PAYLOAD)
            contentType: 'application/json',
            processData: false,  // Ne pas transformer les données
            success: function(response) {
                console.log('Réponse AJAX Tripzzy:', response);
                
                if (response && response.success) {
                    // Inverser l'état actuel (le serveur a déjà fait le toggle)
                    var newStatus = !isFeatured;
                    updateStarDisplay($link, newStatus);
                    console.log('✅ Statut "À la une" mis à jour avec succès. Nouveau statut:', newStatus);
                } else {
                    console.error('Erreur dans la réponse:', response);
                    var errorMsg = response && response.data && response.data.message ? response.data.message : 'Erreur lors de la mise à jour';
                    alert(errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error, xhr);
                var errorMsg = 'Erreur lors de la mise à jour. Veuillez recharger la page et réessayer.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                }
                alert(errorMsg);
            },
            complete: function() {
                $link.css('pointer-events', '').removeClass('updating');
            }
        });
    }

    /**
     * Mettre à jour l'affichage de l'étoile
     */
    function updateStarDisplay($link, newStatus) {
        // Le plugin Tripzzy utilise des classes avec espaces : " dashicons-star-empty " ou " dashicons-star-filled "
        // On doit remplacer toute la classe dashicons-star-* par la nouvelle
        var currentClasses = $link.attr('class') || '';
        var newClasses = currentClasses.replace(/dashicons-star-(empty|filled)/g, '');
        
        if (newStatus) {
            // Marquer comme "À la une"
            newClasses = newClasses + ' dashicons-star-filled ';
            console.log('✅ Voyage mis "À la une"');
        } else {
            // Retirer "À la une"
            newClasses = newClasses + ' dashicons-star-empty ';
            console.log('✅ Voyage retiré de "À la une"');
        }
        
        // Nettoyer les espaces multiples et appliquer les nouvelles classes
        newClasses = newClasses.replace(/\s+/g, ' ').trim();
        $link.attr('class', newClasses);
        
        console.log('Classes mises à jour:', newClasses);
    }

    // Initialiser au chargement du DOM
    $(document).ready(function() {
        initFeaturedStar();
    });

    // Réinitialiser si le DOM change (pour les mises à jour AJAX)
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            var shouldReinit = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    shouldReinit = true;
                }
            });
            if (shouldReinit) {
                setTimeout(initFeaturedStar, 100);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

})(jQuery);
