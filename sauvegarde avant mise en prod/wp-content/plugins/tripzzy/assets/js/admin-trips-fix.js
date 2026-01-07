/**
 * Correctif pour la suppression des images de galerie et des itinéraires
 * + Polyfill pour sprintf manquant dans admin-trips.js
 */
(function($) {
    'use strict';
    
    // Polyfill pour sprintf si non défini (nécessaire pour admin-trips.js)
    if (typeof window.sprintf === 'undefined') {
        window.sprintf = function(format) {
            var args = Array.prototype.slice.call(arguments, 1);
            return format.replace(/%[sdj%]/g, function(match) {
                if (match === '%%') return '%';
                var index = Math.floor((match.length - 2) / 2);
                var arg = args[index];
                switch (match.substring(match.length - 1)) {
                    case 's': return String(arg !== undefined ? arg : '');
                    case 'd': return parseInt(arg !== undefined ? arg : 0, 10);
                    case 'j': return JSON.stringify(arg !== undefined ? arg : null);
                    default: return match;
                }
            });
        };
        console.log('✅ Tripzzy Fix: Polyfill sprintf ajouté');
    }
    
    $(document).ready(function() {
        let fixInitialized = false;
        function initFix() {
            if (fixInitialized) return;
            if (typeof $ === 'undefined' || typeof wp === 'undefined' || !wp.data) {
                setTimeout(initFix, 500);
                return;
            }
            try {
                const store = wp.data.select('Tripzzy/Trip');
                const dispatch = wp.data.dispatch('Tripzzy/Trip');
                if (!store || !dispatch) {
                    setTimeout(initFix, 500);
                    return;
                }
                console.log('✅ Tripzzy Fix: Store trouvé');
                function getCurrentTripData() {
                    try {
                        return store.getTrip();
                    } catch (e) {
                        console.error('Erreur:', e);
                        return null;
                    }
                }
                function updateTripData(updatedData) {
                    if (dispatch.updateTrip) {
                        dispatch.updateTrip(updatedData);
                    } else if (dispatch.setTrip) {
                        dispatch.setTrip(updatedData);
                    }
                }
                // Détecter les clics sur les boutons de suppression Tripzzy
                $(document).on('click', '.tripzzy-button-remove, button.tripzzy-button-remove', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const $button = $(this);
                    const $panel = $button.closest('.tripzzy-panel-wrapper');
                    if ($panel.length === 0) {
                        console.warn('⚠️ Tripzzy Fix: Panel wrapper non trouvé');
                        return;
                    }
                    const panelId = $panel.attr('data-id');
                    console.log('🎯 Tripzzy Fix: Clic sur bouton de suppression', { panelId, panel: $panel[0] });
                    handleDelete($panel, panelId, e);
                });
                function handleDelete($panel, panelId, e) {
                    const panelClasses = ($panel.attr('class') || '').toLowerCase();
                    const parentClasses = ($panel.parent().attr('class') || '').toLowerCase();
                    const allClasses = (panelClasses + ' ' + parentClasses);
                    const $galleryTab = $panel.closest('[class*="gallery"], [data-tab*="gallery"]');
                    const $itineraryTab = $panel.closest('[class*="itinerary"], [data-tab*="itinerary"]');
                    const isGallery = $galleryTab.length > 0 || allClasses.includes('gallery');
                    const isItinerary = $itineraryTab.length > 0 || allClasses.includes('itinerary') || allClasses.includes('itinéraire');
                    console.log('🔍 Tripzzy Fix: Type détecté', { isGallery, isItinerary, panelId, allClasses });
                    if (!isGallery && !isItinerary) {
                        console.warn('⚠️ Tripzzy Fix: Type non reconnu');
                        return;
                    }
                    if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                        return;
                    }
                    const currentTripData = getCurrentTripData();
                    if (!currentTripData || !currentTripData.trips) {
                        console.error('❌ Tripzzy Fix: Impossible de récupérer les données');
                        alert('Erreur : Impossible de récupérer les données.');
                        return;
                    }
                    const updatedData = JSON.parse(JSON.stringify(currentTripData));
                    if (isGallery && updatedData.trips.gallery && Array.isArray(updatedData.trips.gallery)) {
                        console.log('🖼️ Tripzzy Fix: Suppression d\'une image de galerie');
                        if (panelId !== undefined && panelId !== null && panelId !== '') {
                            const beforeLength = updatedData.trips.gallery.length;
                            updatedData.trips.gallery = updatedData.trips.gallery.filter(function(img, index) {
                                return String(index) !== String(panelId) && (img && img.id && String(img.id) !== String(panelId));
                            });
                            const afterLength = updatedData.trips.gallery.length;
                            if (beforeLength === afterLength) {
                                const allPanels = $panel.parent().find('.tripzzy-panel-wrapper');
                                const actualIndex = allPanels.index($panel);
                                if (!isNaN(actualIndex) && actualIndex >= 0 && actualIndex < updatedData.trips.gallery.length) {
                                    updatedData.trips.gallery.splice(actualIndex, 1);
                                    console.log(`✅ Tripzzy Fix: Image supprimée par index ${actualIndex}`);
                                } else {
                                    alert('Erreur : Impossible de déterminer quelle image supprimer.');
                                    return;
                                }
                            } else {
                                console.log(`✅ Tripzzy Fix: Image supprimée par ID (${beforeLength} -> ${afterLength})`);
                            }
                        } else {
                            const allPanels = $panel.parent().find('.tripzzy-panel-wrapper');
                            const actualIndex = allPanels.index($panel);
                            if (!isNaN(actualIndex) && actualIndex >= 0 && actualIndex < updatedData.trips.gallery.length) {
                                updatedData.trips.gallery.splice(actualIndex, 1);
                                console.log(`✅ Tripzzy Fix: Image supprimée par index ${actualIndex}`);
                            } else {
                                alert('Erreur : Impossible de déterminer quelle image supprimer.');
                                return;
                            }
                        }
                    } else if (isItinerary && updatedData.trips.itineraries && Array.isArray(updatedData.trips.itineraries)) {
                        console.log('📋 Tripzzy Fix: Suppression d\'un itinéraire');
                        if (panelId !== undefined && panelId !== null && panelId !== '') {
                            const beforeLength = updatedData.trips.itineraries.length;
                            const allPanels = $panel.parent().find('.tripzzy-panel-wrapper');
                            const actualIndex = allPanels.index($panel);
                            if (!isNaN(actualIndex) && actualIndex >= 0 && actualIndex < updatedData.trips.itineraries.length) {
                                updatedData.trips.itineraries.splice(actualIndex, 1);
                                console.log(`✅ Tripzzy Fix: Itinéraire supprimé par index ${actualIndex}`);
                            } else if (!isNaN(parseInt(panelId)) && parseInt(panelId) >= 0 && parseInt(panelId) < updatedData.trips.itineraries.length) {
                                updatedData.trips.itineraries.splice(parseInt(panelId), 1);
                                console.log(`✅ Tripzzy Fix: Itinéraire supprimé par data-id ${panelId}`);
                            } else {
                                updatedData.trips.itineraries = updatedData.trips.itineraries.filter(function(it, index) {
                                    return String(index) !== String(panelId) && (it && it.id && String(it.id) !== String(panelId));
                                });
                                const afterLength = updatedData.trips.itineraries.length;
                                if (beforeLength === afterLength) {
                                    alert('Erreur : Impossible de déterminer quel itinéraire supprimer.');
                                    return;
                                }
                                console.log(`✅ Tripzzy Fix: Itinéraire supprimé par ID (${beforeLength} -> ${afterLength})`);
                            }
                        } else {
                            const allPanels = $panel.parent().find('.tripzzy-panel-wrapper');
                            const actualIndex = allPanels.index($panel);
                            if (!isNaN(actualIndex) && actualIndex >= 0 && actualIndex < updatedData.trips.itineraries.length) {
                                updatedData.trips.itineraries.splice(actualIndex, 1);
                                console.log(`✅ Tripzzy Fix: Itinéraire supprimé par index ${actualIndex}`);
                            } else {
                                alert('Erreur : Impossible de déterminer quel itinéraire supprimer.');
                                return;
                            }
                        }
                    } else {
                        console.error('❌ Tripzzy Fix: Structure de données non reconnue', {
                            isGallery,
                            isItinerary,
                            hasGallery: !!updatedData.trips.gallery,
                            hasItineraries: !!updatedData.trips.itineraries
                        });
                        alert('Erreur : Structure de données non reconnue.');
                        return;
                    }
                    try {
                        updateTripData(updatedData);
                        $panel.fadeOut(300, function() {
                            $(this).remove();
                            console.log('✅ Tripzzy Fix: Élément retiré du DOM');
                        });
                    } catch (error) {
                        console.error('❌ Tripzzy Fix: Erreur lors de la mise à jour:', error);
                        alert('Erreur lors de la suppression.');
                    }
                }
                fixInitialized = true;
                console.log('✅ Tripzzy Fix: Correctif initialisé');
            } catch (error) {
                console.error('❌ Tripzzy Fix: Erreur:', error);
            }
        }
        setTimeout(initFix, 1000);
        setTimeout(function() {
            if (!fixInitialized) initFix();
        }, 3000);
    });
})(jQuery);
