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
    var submitBtn = document.getElementById('tz-filter-form-submit-btn');
    var clearAllBtn = document.getElementById('tz-filter-clear-all');
    
    if (!submitBtn) return;

    // Masquer tripzzy-filter-found-posts
    if (filterPosts) {
      filterPosts.style.display = 'none';
    }

    var defaultText = 'Afficher les résultats';

    function getFilterCount() {
      // Chercher dans TOUS les inputs du formulaire qui ont une valeur avec (nombre)
      var allInputs = document.querySelectorAll('#tripzzy-filter-form input.tripzzy-input');
      var totalCount = 0;
      var hasCount = false;
      
      for (var i = 0; i < allInputs.length; i++) {
        var input = allInputs[i];
        var val = input.value || '';
        var placeholder = input.placeholder || '';
        
        // Ignorer placeholder ou vide
        if (!val || val === placeholder || val.toLowerCase().indexOf('select') !== -1 || val.toLowerCase().indexOf('sélect') !== -1) {
          continue;
        }
        
        // Chercher tous les (nombre) dans la valeur et les additionner
        var regex = /\((\d+)\)/g;
        var match;
        while ((match = regex.exec(val)) !== null) {
          var num = parseInt(match[1]);
          if (!isNaN(num)) {
            totalCount += num;
            hasCount = true;
          }
        }
      }
      
      return hasCount ? totalCount : null;
    }

    function hasActiveFilter() {
      var allInputs = document.querySelectorAll('#tripzzy-filter-form input.tripzzy-input');
      for (var i = 0; i < allInputs.length; i++) {
        var input = allInputs[i];
        var val = input.value || '';
        var placeholder = input.placeholder || '';
        if (val && val !== placeholder && val.toLowerCase().indexOf('select') === -1 && val.toLowerCase().indexOf('sélect') === -1) {
          // Vérifier qu'il y a un (nombre)
          if (/\(\d+\)/.test(val)) {
            return true;
          }
        }
      }
      return false;
    }

    function updateButton() {
      var count = getFilterCount();
      var hasFilter = hasActiveFilter();
      
      if (hasFilter && count !== null) {
        submitBtn.textContent = defaultText + ' (' + count + ')';
        if (clearAllBtn) clearAllBtn.style.display = 'block';
      } else {
        submitBtn.textContent = defaultText;
        if (clearAllBtn) clearAllBtn.style.display = 'none';
      }
    }

    // Polling toutes les 200ms pour détecter les changements
    setInterval(updateButton, 200);

    // Gérer le clic sur "tout effacer"
    if (clearAllBtn) {
      clearAllBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Reset le formulaire
        var form = document.getElementById('tripzzy-filter-form');
        if (form) {
          form.reset();
        }
        
        // Cliquer sur tous les boutons de suppression de tags
        var removeBtns = document.querySelectorAll('#tripzzy-filter-form .multiselect-dropdown .optext .optdel');
        removeBtns.forEach(function(btn) {
          btn.click();
        });
        
        // Attendre puis soumettre
        setTimeout(function() {
          var submitBtnEl = document.getElementById('tz-filter-form-submit-btn');
          if (submitBtnEl) {
            submitBtnEl.click();
          }
        }, 100);
      });
    }

    // Mise à jour initiale
    updateButton();
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
