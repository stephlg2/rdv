/**
 * RDV Pop-up Devis - JavaScript
 */

// Message de chargement immédiat (avant jQuery)
console.log('[RDV Popup] 📦 Script popup.js chargé');

(function($) {
    'use strict';
    
    // Vérifier que $ est défini
    if (typeof $ === 'undefined' || $ === null) {
        console.error('[RDV Popup] ❌ jQuery ($) n\'est pas disponible dans la closure');
        return;
    }
    
    const RDVPopup = {
        initialized: false,
        shown: false,
        conditions: {
            timeReached: false,
            pagesViewed: false,
            scrollReached: false
        },
        pageCount: 0,
        scrollPercent: 0,
        
        init: function() {
            // Debug (seulement si activé)
            const debug = typeof rdvPopup !== 'undefined' && rdvPopup.debug_mode === 1;
            const log = debug ? console.log.bind(console) : function() {};
            const error = console.error.bind(console); // Toujours afficher les erreurs
            const warn = console.warn.bind(console); // Toujours afficher les avertissements
            
            // Toujours afficher le début de l'initialisation
            console.log('[RDV Popup] 🔄 Initialisation...');
            
            // Vérifier si rdvPopup est défini
            if (typeof rdvPopup === 'undefined') {
                error('[RDV Popup] ❌ ERREUR: rdvPopup n\'est pas défini !');
                error('[RDV Popup] Le script PHP n\'a pas correctement passé les paramètres.');
                return;
            }
            
            // Toujours afficher l'état (même sans debug)
            console.log('[RDV Popup] 📊 État:', {
                enabled: rdvPopup.enabled,
                time_delay: rdvPopup.time_delay,
                pages_viewed: rdvPopup.pages_viewed,
                scroll_percent: rdvPopup.scroll_percent
            });
            
            // Vérifier si le pop-up est activé (strict check)
            if (this.initialized) {
                warn('[RDV Popup] ⚠️ Déjà initialisé');
                return;
            }
            
            // Convertir enabled en nombre pour la comparaison (peut être '1' ou 1)
            const enabled = parseInt(rdvPopup.enabled, 10);
            if (enabled !== 1) {
                warn('[RDV Popup] ⚠️ Pop-up DÉSACTIVÉ (enabled =', rdvPopup.enabled, '->', enabled, ')');
                warn('[RDV Popup] Activez le pop-up dans: Réglages > Pop-up Devis');
                return;
            }
            
            console.log('[RDV Popup] ✅ Pop-up activé, démarrage du tracking...');
            this.initialized = true;
            this.setupTracking();
            this.setupEventListeners();
            this.checkCookie();
        },
        
        /**
         * Vérifier si le cookie existe (pop-up déjà vu)
         */
        checkCookie: function() {
            // Si cookie_duration > 0, vérifier si le cookie existe
            if (rdvPopup.cookie_duration > 0) {
                const cookieName = 'rdv_popup_devis_seen';
                const cookies = document.cookie.split(';');
                
                for (let i = 0; i < cookies.length; i++) {
                    let cookie = cookies[i].trim();
                    if (cookie.indexOf(cookieName + '=') === 0) {
                        // Cookie trouvé, ne pas afficher
                        return;
                    }
                }
            }
            
            // Pas de cookie ou cookie désactivé, démarrer le tracking
            this.startTracking();
        },
        
        /**
         * Démarrer le tracking
         */
        startTracking: function() {
            const debug = rdvPopup.debug_mode === 1;
            const log = debug ? console.log.bind(console) : function() {};
            
            // Toujours afficher le démarrage du tracking
            console.log('[RDV Popup] 🎯 Démarrage du tracking', {
                time_delay: rdvPopup.time_delay,
                pages_viewed: rdvPopup.pages_viewed,
                scroll_percent: rdvPopup.scroll_percent
            });
            
            // Réinitialiser les conditions
            this.conditions = {
                timeReached: false,
                pagesViewed: false,
                scrollReached: false
            };
            
            // Vérifier le nombre de pages vues depuis le début de la session
            this.pageCount = this.getPageCount();
            log('[RDV Popup] Pages vues:', this.pageCount);
            
            // Si déjà assez de pages vues, marquer la condition
            if (rdvPopup.pages_viewed > 0 && this.pageCount >= rdvPopup.pages_viewed) {
                this.conditions.pagesViewed = true;
                log('[RDV Popup] Condition pages vues remplie');
            }
            
            // Démarrer le timer - C'EST LA CONDITION PRINCIPALE
            if (rdvPopup.time_delay > 0) {
                // Timer activé : attendre le temps configuré
                log('[RDV Popup] Timer activé:', rdvPopup.time_delay, 'secondes');
                setTimeout(() => {
                    log('[RDV Popup] Timer terminé');
                    this.conditions.timeReached = true;
                    this.checkAndShow();
                }, rdvPopup.time_delay * 1000);
            } else {
                // Timer désactivé (0) : attendre quand même un délai minimum de 2 secondes
                // pour éviter l'affichage instantané
                log('[RDV Popup] Timer désactivé, délai minimum de 2 secondes');
                setTimeout(() => {
                    log('[RDV Popup] Délai minimum terminé');
                    this.conditions.timeReached = true; // Considérer comme "prêt" après le délai min
                    this.checkAndShow();
                }, 2000);
            }
            
            // Démarrer le tracking du scroll (mais ne pas déclencher immédiatement)
            if (rdvPopup.scroll_percent > 0) {
                log('[RDV Popup] Tracking scroll activé:', rdvPopup.scroll_percent, '%');
                this.trackScroll();
            }
        },
        
        /**
         * Tracker le scroll
         */
        trackScroll: function() {
            const self = this;
            
            function updateScrollPercent() {
                const windowHeight = window.innerHeight;
                const documentHeight = document.documentElement.scrollHeight;
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
                const scrollableHeight = documentHeight - windowHeight;
                
                // Calculer le pourcentage de scroll
                if (scrollableHeight > 0) {
                    // Pourcentage basé sur la hauteur scrollable
                    self.scrollPercent = Math.round((scrollTop / scrollableHeight) * 100);
                } else {
                    // Page plus courte que la fenêtre = considérer comme 100% scrollé
                    self.scrollPercent = 100;
                }
                
                // Limiter à 100%
                if (self.scrollPercent > 100) {
                    self.scrollPercent = 100;
                }
                
                const debug = rdvPopup.debug_mode === 1;
                const log = debug ? console.log.bind(console) : function() {};
                
                log('[RDV Popup] Scroll:', {
                    scrollTop: scrollTop,
                    scrollableHeight: scrollableHeight,
                    scrollPercent: self.scrollPercent + '%',
                    threshold: rdvPopup.scroll_percent + '%'
                });
                
                // Convertir scroll_percent en nombre pour la comparaison
                const scrollThreshold = parseInt(rdvPopup.scroll_percent, 10);
                
                if (self.scrollPercent >= scrollThreshold && !self.conditions.scrollReached) {
                    log('[RDV Popup] ✅ Condition scroll remplie:', self.scrollPercent + '% >= ' + scrollThreshold + '%');
                    self.conditions.scrollReached = true;
                    // Vérifier si on peut afficher (le timer doit être prêt si activé)
                    self.checkAndShow();
                }
            }
            
            // Ne pas vérifier immédiatement au chargement de la page
            // Attendre au moins 1 seconde pour éviter l'affichage instantané si la page est déjà scrollée
            setTimeout(() => {
                updateScrollPercent();
            }, 1000);
            
            // Écouter les événements de scroll (avec throttling)
            let ticking = false;
            $(window).on('scroll.rdvpopup', function() {
                if (!ticking) {
                    window.requestAnimationFrame(function() {
                        updateScrollPercent();
                        ticking = false;
                    });
                    ticking = true;
                }
            });
        },
        
        /**
         * Obtenir le nombre de pages vues dans la session
         */
        getPageCount: function() {
            // Utiliser sessionStorage pour compter les pages
            let count = sessionStorage.getItem('rdv_popup_page_count');
            
            if (count === null) {
                // Première page de la session
                count = 1;
            } else {
                // Incrémenter
                count = parseInt(count, 10) + 1;
            }
            
            // Sauvegarder
            sessionStorage.setItem('rdv_popup_page_count', count);
            
            return count;
        },
        
        /**
         * Vérifier les conditions et afficher si nécessaire
         */
        checkAndShow: function() {
            const debug = rdvPopup.debug_mode === 1;
            const log = debug ? console.log.bind(console) : function() {};
            
            if (this.shown) {
                log('[RDV Popup] Déjà affiché');
                return; // Déjà affiché
            }
            
            // Vérifier quelles conditions sont activées
            const timeActive = rdvPopup.time_delay > 0;
            const pagesActive = rdvPopup.pages_viewed > 0;
            const scrollActive = rdvPopup.scroll_percent > 0;
            
            log('[RDV Popup] Vérification des conditions', {
                timeActive: timeActive,
                pagesActive: pagesActive,
                scrollActive: scrollActive,
                conditions: this.conditions
            });
            
            // Si aucune condition n'est activée, ne pas afficher
            if (!timeActive && !pagesActive && !scrollActive) {
                log('[RDV Popup] Aucune condition activée');
                return;
            }
            
            // Si le délai de temps est activé, il doit TOUJOURS être respecté avant d'afficher
            if (timeActive && !this.conditions.timeReached) {
                log('[RDV Popup] Délai de temps non encore écoulé, attente...');
                return; // Ne pas afficher tant que le délai n'est pas écoulé
            }
            
            // Vérifier si au moins une condition activée est remplie
            const conditionsMet = [];
            
            if (timeActive && this.conditions.timeReached) {
                conditionsMet.push('time');
            }
            if (pagesActive && this.conditions.pagesViewed) {
                conditionsMet.push('pages');
            }
            if (scrollActive && this.conditions.scrollReached) {
                conditionsMet.push('scroll');
            }
            
            log('[RDV Popup] Conditions remplies:', conditionsMet);
            
            // Afficher si au moins une condition est remplie (et le délai de temps est respecté si activé)
            if (conditionsMet.length > 0) {
                log('[RDV Popup] Affichage du pop-up...');
                this.show();
            } else {
                log('[RDV Popup] Aucune condition remplie, attente...');
            }
        },
        
        /**
         * Afficher le pop-up
         */
        show: function() {
            const debug = rdvPopup.debug_mode === 1;
            const log = debug ? console.log.bind(console) : function() {};
            const error = console.error.bind(console);
            
            if (this.shown) {
                log('[RDV Popup] Déjà affiché');
                return;
            }
            
            this.shown = true;
            const $popup = $('#rdv-popup-devis');
            
            log('[RDV Popup] Tentative d\'affichage, popup trouvé:', $popup.length);
            
            if ($popup.length === 0) {
                error('[RDV Popup] ERREUR: Le pop-up HTML n\'existe pas dans la page !');
                error('[RDV Popup] Vérifiez que render_popup() est appelé dans wp_footer');
                return;
            }
            
            log('[RDV Popup] Affichage du pop-up...');
            
            // Afficher le pop-up
            $popup.css('display', 'flex').attr('aria-hidden', 'false');
            
            // Empêcher le scroll du body
            $('body').css('overflow', 'hidden');
            
            // Focus sur le bouton de fermeture pour l'accessibilité
            $popup.find('.rdv-popup-close').focus();
            
            log('[RDV Popup] Pop-up affiché avec succès');
        },
        
        /**
         * Fermer le pop-up
         */
        hide: function() {
            const $popup = $('#rdv-popup-devis');
            
            if ($popup.length === 0) {
                return;
            }
            
            // Animation de fermeture
            $popup.addClass('rdv-popup-closing');
            
            setTimeout(() => {
                $popup.css('display', 'none').attr('aria-hidden', 'true').removeClass('rdv-popup-closing');
                $('body').css('overflow', '');
                
                // Définir le cookie si nécessaire
                this.setCookie();
            }, 200);
        },
        
        /**
         * Définir le cookie
         */
        setCookie: function() {
            if (rdvPopup.cookie_duration === 0) {
                return; // Pas de cookie
            }
            
            const days = rdvPopup.cookie_duration;
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = 'expires=' + date.toUTCString();
            
            document.cookie = 'rdv_popup_devis_seen=1; ' + expires + '; path=/; SameSite=Lax';
        },
        
        /**
         * Configurer les event listeners
         */
        setupEventListeners: function() {
            const self = this;
            
            // Fermer au clic sur l'overlay
            $(document).on('click', '.rdv-popup-overlay', function(e) {
                if (e.target === this) {
                    self.hide();
                }
            });
            
            // Fermer au clic sur le bouton de fermeture
            $(document).on('click', '.rdv-popup-close, .rdv-popup-close-text', function(e) {
                e.preventDefault();
                self.hide();
            });
            
            // Fermer avec la touche Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.shown) {
                    self.hide();
                }
            });
            
            // Empêcher la propagation sur le container
            $(document).on('click', '.rdv-popup-container', function(e) {
                e.stopPropagation();
            });
        },
        
        /**
         * Configurer le tracking initial
         */
        setupTracking: function() {
            const debug = rdvPopup.debug_mode === 1;
            const log = debug ? console.log.bind(console) : function() {};
            
            // Vérifier si on est sur une page valide (pas admin, pas 404, etc.)
            const isValid = this.isValidPage();
            
            // Toujours afficher si la page est valide ou non
            if (isValid) {
                console.log('[RDV Popup] ✅ Page valide:', window.location.pathname);
                // Démarrer le tracking après un court délai pour laisser la page se charger
                log('[RDV Popup] Démarrage du tracking dans 500ms...');
                setTimeout(() => {
                    this.startTracking();
                }, 500);
            } else {
                console.warn('[RDV Popup] ⚠️ Page non valide (exclue):', window.location.pathname);
                console.warn('[RDV Popup] Le pop-up ne s\'affichera pas sur cette page.');
                return;
            }
        },
        
        /**
         * Vérifier si la page est valide pour afficher le pop-up
         */
        isValidPage: function() {
            // Ne pas afficher sur certaines pages par défaut
            const path = window.location.pathname;
            const excludedPaths = [
                '/wp-admin/',
                '/wp-login.php',
                '/demande-de-devis/',
                '/contact/',
                '/espace-client/'
            ];
            
            for (let i = 0; i < excludedPaths.length; i++) {
                if (path.indexOf(excludedPaths[i]) !== -1) {
                    return false;
                }
            }
            
            // Vérifier si PHP a autorisé l'affichage sur cette page
            if (typeof rdvPopup !== 'undefined' && rdvPopup.hasOwnProperty('can_display_on_page')) {
                const canDisplay = parseInt(rdvPopup.can_display_on_page, 10);
                if (canDisplay !== 1) {
                    console.log('[RDV Popup] ⚠️ Page non autorisée selon les réglages d\'affichage');
                    return false;
                }
            }
            
            return true;
        }
    };
    
    // Vérifier que jQuery est disponible
    if (typeof jQuery === 'undefined') {
        console.error('[RDV Popup] ❌ ERREUR: jQuery n\'est pas disponible !');
        // Essayer d'attendre un peu
        setTimeout(function() {
            if (typeof jQuery !== 'undefined') {
                console.log('[RDV Popup] ✅ jQuery chargé, initialisation...');
                jQuery(document).ready(function() {
                    RDVPopup.init();
                });
            } else {
                console.error('[RDV Popup] ❌ jQuery toujours indisponible après attente');
            }
        }, 1000);
    } else {
        // Initialiser quand le DOM est prêt
        jQuery(document).ready(function() {
            // Message de démarrage (toujours affiché)
            console.log('[RDV Popup] 🚀 Script chargé, vérification...');
            
            // Vérifier que rdvPopup est défini
            if (typeof rdvPopup === 'undefined') {
                console.error('[RDV Popup] ❌ ERREUR: rdvPopup n\'est pas défini !');
                console.error('[RDV Popup] Le script PHP n\'a pas correctement passé les paramètres.');
                return;
            }
            
            // Vérifier que le HTML du pop-up existe
            const $popup = jQuery('#rdv-popup-devis');
            if ($popup.length === 0) {
                console.error('[RDV Popup] ⚠️ ERREUR: Le HTML du pop-up n\'existe pas dans la page !');
                console.error('[RDV Popup] Vérifiez que:');
                console.error('[RDV Popup] 1. Le plugin est activé');
                console.error('[RDV Popup] 2. Le pop-up est activé dans les réglages');
                console.error('[RDV Popup] 3. render_popup() est appelé dans wp_footer');
                return;
            } else {
                console.log('[RDV Popup] ✅ HTML du pop-up trouvé dans la page');
            }
            
            // Afficher les paramètres si mode débogage
            if (rdvPopup.debug_mode === 1) {
                console.log('[RDV Popup] 📊 Paramètres:', {
                    enabled: rdvPopup.enabled,
                    time_delay: rdvPopup.time_delay,
                    pages_viewed: rdvPopup.pages_viewed,
                    scroll_percent: rdvPopup.scroll_percent,
                    cookie_duration: rdvPopup.cookie_duration
                });
            }
            
            RDVPopup.init();
        });
    }
    
    // Réinitialiser si nécessaire (pour les SPA ou navigation AJAX)
    if (typeof jQuery !== 'undefined') {
        jQuery(window).on('popstate', function() {
            if (!RDVPopup.shown) {
                RDVPopup.initialized = false;
                RDVPopup.init();
            }
        });
    }
    
})(typeof jQuery !== 'undefined' ? jQuery : null);
