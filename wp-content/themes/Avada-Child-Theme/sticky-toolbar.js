(function(){
  function initStickyToolbar(){
    var toolbar = document.querySelector('.tz-toolbar');
    if (!toolbar) return;

    var toolbarOffset = toolbar.offsetTop;
    var wasSticky = false;

    function checkSticky() {
      if (window.innerWidth > 768) return;
      
      var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      var isSticky = scrollTop > toolbarOffset;

      if (isSticky && !wasSticky) {
        toolbar.style.transform = 'translateY(0)';
        setTimeout(function() {
          toolbar.classList.add('is-sticky');
        }, 10);
        wasSticky = true;
      } else if (!isSticky && wasSticky) {
        toolbar.style.transform = 'translateY(-100%)';
        setTimeout(function() {
          toolbar.classList.remove('is-sticky');
          toolbar.style.transform = '';
        }, 300);
        wasSticky = false;
      }
    }

    window.addEventListener('scroll', checkSticky);
    window.addEventListener('resize', function() {
      toolbarOffset = toolbar.offsetTop;
      checkSticky();
    });
    checkSticky();
  }

  function initFilterResultsInButton() {
    var filterPosts = document.getElementById('tripzzy-filter-found-posts');
    var clearAllBtn = document.getElementById('tz-filter-clear-all');
    
    // Masquer tripzzy-filter-found-posts
    if (filterPosts) {
      filterPosts.style.display = 'none';
    }

    // La mise à jour du bouton est maintenant gérée par TripFilter.php
    // On garde juste la détection des filtres actifs pour afficher/masquer le bouton "Réinitialiser"
    function hasActiveFilter() {
      var hasActive = false;
      
      // Vérifier les selects avec des options sélectionnées
      var selects = document.querySelectorAll('#tripzzy-filter-form select.tripzzy-filter-dropdown');
      selects.forEach(function(select) {
        if (select.multiple) {
          var selected = Array.from(select.selectedOptions).filter(function(opt) {
            return opt.value && opt.value !== '';
          });
          if (selected.length > 0) {
            hasActive = true;
          }
        } else if (select.value && select.value !== '') {
          hasActive = true;
        }
      });
      
      // Vérifier les tags sélectionnés dans les multiselects (affichage visuel)
      var selectedTags = document.querySelectorAll('#tripzzy-filter-form .multiselect-dropdown .optext');
      if (selectedTags.length > 0) {
        hasActive = true;
      }
      
      // Vérifier les sliders (prix et durée)
      var priceSlider = document.querySelector('#tripzzy-filter-form [name="tripzzy_price"]');
      if (priceSlider && priceSlider.noUiSlider) {
        var priceValues = priceSlider.noUiSlider.get();
        var priceMin = priceSlider.noUiSlider.options.range.min;
        var priceMax = priceSlider.noUiSlider.options.range.max;
        if (priceValues[0] != priceMin || priceValues[1] != priceMax) {
          hasActive = true;
        }
      }
      
      var durationSlider = document.querySelector('#tripzzy-filter-form [name="tripzzy_trip_duration"]');
      if (durationSlider && durationSlider.noUiSlider) {
        var durationValues = durationSlider.noUiSlider.get();
        var durationMin = durationSlider.noUiSlider.options.range.min;
        var durationMax = durationSlider.noUiSlider.options.range.max;
        if (durationValues[0] != durationMin || durationValues[1] != durationMax) {
          hasActive = true;
        }
      }
      
      return hasActive;
    }

    // Mettre à jour la visibilité du bouton "Réinitialiser" toutes les 200ms
    setInterval(function() {
      var hasFilter = hasActiveFilter();
      if (clearAllBtn) {
        clearAllBtn.style.display = hasFilter ? 'block' : 'none';
      }
    }, 200);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      initStickyToolbar();
      initFilterResultsInButton();
    });
  } else {
    initStickyToolbar();
    initFilterResultsInButton();
  }
})();
