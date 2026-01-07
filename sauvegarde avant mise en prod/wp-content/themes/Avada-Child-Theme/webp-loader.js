/**
 * WebP Loader - Remplace automatiquement les images JPG/PNG par leur version WebP
 * Version simplifiée sans requêtes fetch (évite les erreurs CORS)
 */

(function() {
    'use strict';

    // Vérifier si le navigateur supporte WebP
    function supportsWebP(callback) {
        const webP = new Image();
        webP.onload = webP.onerror = function() {
            callback(webP.height === 2);
        };
        webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
    }

    // Vérifier si une URL est une image convertible
    function isConvertibleImage(url) {
        if (!url) return false;
        
        // Exclure spécifiquement les URLs Google (en premier pour éviter toute conversion)
        if (url.indexOf('google.com') !== -1 || url.indexOf('googlelogo') !== -1 || url.indexOf('google') !== -1) {
            return false;
        }
        
        // Exclure les URLs externes (qui ne sont pas sur le même domaine)
        if (url.indexOf('http://') === 0 || url.indexOf('https://') === 0) {
            // Si l'URL ne contient pas le domaine du site, c'est une URL externe
            const siteUrl = window.location.origin;
            if (url.indexOf(siteUrl) !== 0) {
                return false;
            }
        }
        
        return /\/wp-content\/uploads\/.+\.(jpe?g|png)(\?.*)?$/i.test(url);
    }

    // Convertir l'URL en WebP
    function toWebPUrl(url) {
        if (!url) return url;
        return url.replace(/\.(jpe?g|png)(\?.*)?$/i, '.webp$2');
    }

    // Cache des images testées
    const testedImages = new Map();

    // Tester si une image WebP existe en essayant de la charger
    function testWebPImage(webpUrl, onSuccess, onError) {
        // Vérifier le cache
        if (testedImages.has(webpUrl)) {
            if (testedImages.get(webpUrl)) {
                onSuccess();
            }
            return;
        }

        const img = new Image();
        img.onload = function() {
            testedImages.set(webpUrl, true);
            onSuccess();
        };
        img.onerror = function() {
            testedImages.set(webpUrl, false);
            if (onError) onError();
        };
        img.src = webpUrl;
    }

    // Remplacer les images <img>
    function replaceImgTags() {
        document.querySelectorAll('img:not([data-webp-checked])').forEach(function(img) {
            // Vérifier explicitement l'attribut data-no-webp (priorité absolue)
            if (img.hasAttribute('data-no-webp') || img.getAttribute('data-no-webp') === '1' || img.getAttribute('data-no-webp') === '') {
                img.setAttribute('data-webp-checked', '1'); // Marquer comme vérifié pour éviter de retraiter
                return; // Ignorer cette image complètement
            }
            
            // Vérifier si c'est une image Google (même si l'attribut n'est pas présent)
            const imgSrc = img.src || img.dataset.src || img.getAttribute('src') || '';
            if (imgSrc.indexOf('google.com') !== -1 || imgSrc.indexOf('googlelogo') !== -1 || imgSrc.indexOf('/google/') !== -1) {
                img.setAttribute('data-webp-checked', '1'); // Marquer comme vérifié
                img.setAttribute('data-no-webp', '1'); // Ajouter l'attribut pour protection future
                return; // Ignorer les images Google
            }
            
            img.setAttribute('data-webp-checked', '1');

            // src
            if (img.src && isConvertibleImage(img.src)) {
                const originalSrc = img.src;
                const webpUrl = toWebPUrl(originalSrc);
                
                testWebPImage(webpUrl, function() {
                    img.src = webpUrl;
                });
            }

            // data-src (lazy loading)
            if (img.dataset.src && isConvertibleImage(img.dataset.src)) {
                const webpUrl = toWebPUrl(img.dataset.src);
                testWebPImage(webpUrl, function() {
                    img.dataset.src = webpUrl;
                });
            }
        });
    }

    // Remplacer les backgrounds CSS (inline styles)
    function replaceBackgrounds() {
        document.querySelectorAll('[style*="background"]:not([data-webp-bg-checked])').forEach(function(el) {
            el.setAttribute('data-webp-bg-checked', '1');
            
            const style = el.getAttribute('style');
            if (!style) return;

            // Trouver les URLs dans le background
            const urlRegex = /url\(['"]?([^'")\s]+)['"]?\)/gi;
            let match;
            
            while ((match = urlRegex.exec(style)) !== null) {
                const url = match[1];
                if (isConvertibleImage(url)) {
                    const webpUrl = toWebPUrl(url);
                    testWebPImage(webpUrl, function() {
                        const currentStyle = el.getAttribute('style');
                        if (currentStyle) {
                            el.setAttribute('style', currentStyle.replace(url, webpUrl));
                        }
                    });
                }
            }
        });

        // Data attributes
        document.querySelectorAll('[data-bg]:not([data-webp-data-checked]), [data-background]:not([data-webp-data-checked])').forEach(function(el) {
            el.setAttribute('data-webp-data-checked', '1');
            
            ['data-bg', 'data-background', 'data-bg-url'].forEach(function(attr) {
                const url = el.getAttribute(attr);
                if (url && isConvertibleImage(url)) {
                    const webpUrl = toWebPUrl(url);
                    testWebPImage(webpUrl, function() {
                        el.setAttribute(attr, webpUrl);
                    });
                }
            });
        });
    }

    // Remplacer dans les balises <style>
    function replaceStylesheetBackgrounds() {
        document.querySelectorAll('style:not([data-webp-style-checked])').forEach(function(styleEl) {
            styleEl.setAttribute('data-webp-style-checked', '1');
            
            let css = styleEl.textContent;
            if (!css) return;

            const urlRegex = /url\(['"]?([^'")\s]+\/wp-content\/uploads\/[^'")\s]+\.(jpe?g|png))['"]?\)/gi;
            let match;
            const replacements = [];
            
            while ((match = urlRegex.exec(css)) !== null) {
                const url = match[1];
                const webpUrl = toWebPUrl(url);
                replacements.push({ original: url, webp: webpUrl });
            }

            // Tester et remplacer chaque URL
            replacements.forEach(function(r) {
                testWebPImage(r.webp, function() {
                    styleEl.textContent = styleEl.textContent.replace(new RegExp(r.original.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), r.webp);
                });
            });
        });
    }

    // Restaurer les images Google qui ont été modifiées par erreur
    function restoreGoogleImages() {
        document.querySelectorAll('img[src*="google"]').forEach(function(img) {
            const src = img.src || '';
            // Si l'URL a été modifiée en .webp mais que c'est une image Google, restaurer l'original
            if (src.indexOf('googlelogo') !== -1 && src.indexOf('.webp') !== -1 && src.indexOf('.png') === -1) {
                // Restaurer l'URL originale en .png
                const originalUrl = src.replace(/\.webp(\?|$)/i, '.png$1');
                img.src = originalUrl;
                img.setAttribute('data-no-webp', '1');
                img.setAttribute('data-webp-checked', '1');
            }
        });
    }

    // Exécuter le remplacement
    function runReplacement() {
        // D'abord restaurer les images Google si nécessaire
        restoreGoogleImages();
        // Ensuite traiter les autres images
        replaceImgTags();
        replaceBackgrounds();
        replaceStylesheetBackgrounds();
    }

    // Initialisation
    supportsWebP(function(supported) {
        if (!supported) {
            return;
        }

        // Exécuter au chargement du DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runReplacement);
        } else {
            runReplacement();
        }

        // Réexécuter après le chargement complet
        window.addEventListener('load', function() {
            setTimeout(runReplacement, 500);
        });

        // Observer les mutations du DOM
        const observer = new MutationObserver(function(mutations) {
            let shouldRun = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    shouldRun = true;
                }
            });
            if (shouldRun) {
                setTimeout(runReplacement, 100);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });

})();
