(function () {
  function initLoadMore() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.rdv-articles-load-more');
      if (!btn || btn.disabled) return;

      e.preventDefault();

      var wrapper = btn.closest('.rdv-articles-wrapper') || document.getElementById('rdv-articles-app');
      var grid = wrapper ? wrapper.querySelector('[data-rdv-grid]') : null;
      if (!grid || typeof rdvArticles === 'undefined') return;

      btn.disabled = true;
      btn.classList.add('is-loading');
      var originalText = btn.getAttribute('data-load-more-label') || btn.textContent;
      btn.textContent = (rdvArticles.i18n && rdvArticles.i18n.loading) ? rdvArticles.i18n.loading : 'Chargement…';

      var formData = new FormData();
      formData.append('action', 'rdv_articles_load_more');
      formData.append('nonce', rdvArticles.nonce);
      formData.append('view', btn.getAttribute('data-view') || 'all');
      formData.append('offset', btn.getAttribute('data-offset') || '0');

      var categorie = btn.getAttribute('data-categorie');
      var pays = btn.getAttribute('data-pays');
      var exclude = btn.getAttribute('data-exclude');
      if (categorie) formData.append('categorie', categorie);
      if (pays) formData.append('pays', pays);
      if (exclude) formData.append('exclude', exclude);

      fetch(rdvArticles.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (!data.success) {
            btn.disabled = false;
            btn.classList.remove('is-loading');
            btn.textContent = originalText;
            return;
          }

          if (data.data.html) {
            grid.insertAdjacentHTML('beforeend', data.data.html);
          }

          var wrap = btn.closest('.rdv-articles-load-more-wrap');
          if (data.data.has_more && data.data.button && wrap) {
            wrap.outerHTML = data.data.button;
          } else if (wrap) {
            wrap.remove();
          }
        })
        .catch(function () {
          btn.disabled = false;
          btn.classList.remove('is-loading');
          btn.textContent = originalText;
        });
    });
  }

  function setActiveTocLink(links, activeLink) {
    links.forEach(function (link) {
      link.classList.toggle('toc-sidebar__link--active', link === activeLink);
    });
  }

  function getTocScrollOffset() {
    return getHeaderOffset() + 24;
  }

  function scrollToTocTarget(target) {
    var top = target.getBoundingClientRect().top + window.scrollY - getTocScrollOffset();
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
  }

  function updateActiveTocFromScroll(headings, links) {
    if (!headings.length) {
      return;
    }

    var offset = getTocScrollOffset();
    var current = headings[0].link;

    for (var i = headings.length - 1; i >= 0; i--) {
      if (headings[i].el.getBoundingClientRect().top <= offset) {
        current = headings[i].link;
        break;
      }
    }

    setActiveTocLink(links, current);
  }

  var TOC_WIDTH = 240;
  var TOC_GAP = 40;
  var TOC_MIN_EDGE = 24;
  var TOC_GUTTER_NEEDED = TOC_WIDTH + TOC_GAP + TOC_MIN_EDGE;

  function updateTocLayoutMode(sidebar) {
    var layout = sidebar.closest('.rdv-single-article-layout');

    if (!layout || isTocMobile()) {
      if (layout) {
        layout.classList.remove('rdv-single-article-layout--gutter');
        layout.classList.remove('rdv-single-article-layout--gutter-right');
      }
      sidebar.style.marginLeft = '';
      sidebar.style.marginRight = '';
      return;
    }

    var rect = layout.getBoundingClientRect();
    var isRight = layout.classList.contains('rdv-single-article-layout--toc-right');
    var leftGutter = rect.left;
    var rightGutter = window.innerWidth - rect.right;

    layout.classList.remove('rdv-single-article-layout--gutter');
    layout.classList.remove('rdv-single-article-layout--gutter-right');
    sidebar.style.marginLeft = '';
    sidebar.style.marginRight = '';

    if (!isRight && leftGutter >= TOC_GUTTER_NEEDED) {
      layout.classList.add('rdv-single-article-layout--gutter');
      sidebar.style.marginLeft = (TOC_MIN_EDGE - leftGutter) + 'px';
      return;
    }

    if (isRight && rightGutter >= TOC_GUTTER_NEEDED) {
      layout.classList.add('rdv-single-article-layout--gutter-right');
      sidebar.style.marginRight = (TOC_MIN_EDGE - rightGutter) + 'px';
    }
  }

  function isTocMobile() {
    return window.matchMedia('(max-width: 992px)').matches;
  }

  function toggleTocSidebar(sidebar, btn) {
    if (!sidebar || !btn || !isTocMobile()) {
      return;
    }

    var isOpen = sidebar.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  }

  function bindTocToggle(sidebar, btn) {
    if (!btn || btn.dataset.rdvTocBound === '1') {
      return;
    }

    btn.dataset.rdvTocBound = '1';

    sidebar.addEventListener('click', function (e) {
      if (!isTocMobile()) {
        return;
      }

      if (!e.target.closest('.toc-sidebar__toggle')) {
        return;
      }

      e.preventDefault();
      toggleTocSidebar(sidebar, btn);
    });
  }

  function initTocSidebar() {
    var sidebars = document.querySelectorAll('.toc-sidebar');
    if (!sidebars.length) return;

    sidebars.forEach(function (sidebar) {
      var btn = sidebar.querySelector('.toc-sidebar__toggle');
      var panel = sidebar.querySelector('.toc-sidebar__panel');
      var links = panel ? panel.querySelectorAll('.toc-sidebar__link[href^="#"]') : [];
      var headings = [];
      var scrollTicking = false;

      links.forEach(function (link) {
        var id = link.getAttribute('href').slice(1);
        var target = document.getElementById(id);
        if (target) headings.push({ link: link, el: target });
      });

      if (btn) {
        bindTocToggle(sidebar, btn);
      }

      links.forEach(function (link) {
        link.addEventListener('click', function (e) {
          var id = link.getAttribute('href').slice(1);
          var target = document.getElementById(id);
          if (!target) return;

          e.preventDefault();
          setActiveTocLink(links, link);
          scrollToTocTarget(target);

          if (history.pushState) {
            history.pushState(null, '', '#' + id);
          }

          if (isTocMobile() && btn) {
            sidebar.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
          }
        });
      });

      if (headings.length) {
        var onScroll = function () {
          if (scrollTicking) {
            return;
          }

          scrollTicking = true;
          window.requestAnimationFrame(function () {
            updateActiveTocFromScroll(headings, links);
            scrollTicking = false;
          });
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        onScroll();
      }

      updateTocLayoutMode(sidebar);
    });

    var onTocLayoutChange = function () {
      sidebars.forEach(function (sidebar) {
        updateTocLayoutMode(sidebar);

        var btn = sidebar.querySelector('.toc-sidebar__toggle');
        if (!isTocMobile()) {
          sidebar.classList.remove('is-open');
          if (btn) btn.setAttribute('aria-expanded', 'true');
        } else if (btn) {
          btn.setAttribute('aria-expanded', sidebar.classList.contains('is-open') ? 'true' : 'false');
        }
      });
    };

    window.addEventListener('resize', onTocLayoutChange, { passive: true });
    window.addEventListener('load', onTocLayoutChange);
    onTocLayoutChange();
  }

  function getAdminBarOffset() {
    var bar = document.getElementById('wpadminbar');
    return bar ? bar.offsetHeight : 0;
  }

  function isPageTitleBarElement(el) {
    if (!el || !el.closest) {
      return false;
    }

    return !!el.closest(
      '.avada-page-titlebar-wrapper, .fusion-page-title-bar, .fusion-page-title-wrapper, .fusion-page-title-row'
    );
  }

  function getHeaderOffset() {
    var offset = getAdminBarOffset();
    var maxBottom = offset;
    var selectors = [
      '.fusion-secondary-header',
      '.fusion-header-wrapper'
    ];

    selectors.forEach(function (selector) {
      document.querySelectorAll(selector).forEach(function (el) {
        if (!el || isPageTitleBarElement(el)) {
          return;
        }

        var style = window.getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden') {
          return;
        }

        var rect = el.getBoundingClientRect();

        if (style.position === 'fixed' || style.position === 'sticky') {
          if (rect.top <= offset + 8) {
            maxBottom = Math.max(maxBottom, rect.bottom);
          }
          return;
        }

        // En-tête site en haut de page uniquement (pas le hero « Nos articles »).
        if (rect.top <= offset + 8 && rect.bottom <= 220) {
          maxBottom = Math.max(maxBottom, Math.ceil(rect.bottom));
        }
      });
    });

    return maxBottom;
  }

  function getArticlesStickyOffset() {
    var nav = document.querySelector('.rdv-articles-sticky-nav');
    return getHeaderOffset() + (nav ? nav.offsetHeight : 0) + 12;
  }

  var countryScrollLockUntil = 0;

  function lockCountryScrollSpy(ms) {
    countryScrollLockUntil = Date.now() + (ms || 900);
  }

  function isCountryScrollSpyLocked() {
    return Date.now() < countryScrollLockUntil;
  }

  function scrollToCountrySection(target, behavior) {
    if (!target) {
      return;
    }

    lockCountryScrollSpy(behavior === 'smooth' ? 1100 : 200);
    var top = target.getBoundingClientRect().top + window.pageYOffset - getArticlesStickyOffset();
    window.scrollTo({ top: Math.max(0, top), behavior: behavior || 'smooth' });

    if ('onscrollend' in window) {
      window.addEventListener(
        'scrollend',
        function () {
          lockCountryScrollSpy(200);
        },
        { once: true }
      );
    }
  }

  function setActiveCountryPill(pills, activePill) {
    pills.forEach(function (p) {
      p.classList.toggle('is-active', p === activePill);
    });
  }

  function getActiveCountryItem(sections) {
    if (!sections.length) {
      return null;
    }

    var anchorDocY = window.pageYOffset + getArticlesStickyOffset() + 24;
    var active = sections[0];

    for (var i = 0; i < sections.length; i++) {
      var sectionTop = sections[i].el.getBoundingClientRect().top + window.pageYOffset;
      if (sectionTop <= anchorDocY) {
        active = sections[i];
      }
    }

    return active;
  }

  function markArticlesFilterNavigation() {
    try {
      sessionStorage.setItem('rdvArticlesFilterNav', '1');
    } catch (e) {}
  }

  function consumeArticlesFilterNavigation() {
    try {
      if (sessionStorage.getItem('rdvArticlesFilterNav') === '1') {
        sessionStorage.removeItem('rdvArticlesFilterNav');
        return true;
      }
    } catch (e) {}
    return false;
  }

  function resetArticlesHubScroll(behavior) {
    var target =
      document.querySelector('.rdv-articles-sticky-nav') ||
      document.getElementById('rdv-articles-app');

    if (!target) {
      window.scrollTo(0, 0);
      return;
    }

    var top = target.getBoundingClientRect().top + window.pageYOffset - getHeaderOffset();
    window.scrollTo({
      top: Math.max(0, Math.round(top)),
      behavior: behavior || 'smooth'
    });
  }

  function initArticlesFilterScrollReset() {
    var app = document.getElementById('rdv-articles-app');
    if (!app) {
      return;
    }

    if ('scrollRestoration' in history) {
      history.scrollRestoration = 'manual';
    }

    app.querySelectorAll('.rdv-nav-filter[href]').forEach(function (link) {
      link.addEventListener('click', markArticlesFilterNavigation);
    });

    if (!consumeArticlesFilterNavigation()) {
      return;
    }

    var didScroll = false;
    var scrollToArticlesSmooth = function () {
      if (didScroll) {
        return;
      }
      didScroll = true;
      resetArticlesHubScroll('smooth');
    };

    // Partir du haut pour que la transition vers les articles soit visible.
    window.scrollTo(0, 0);

    var runAfterLayout = function () {
      window.requestAnimationFrame(function () {
        window.requestAnimationFrame(scrollToArticlesSmooth);
      });
    };

    if (document.readyState === 'complete') {
      runAfterLayout();
    } else {
      window.addEventListener('load', runAfterLayout);
    }

    window.addEventListener('pageshow', function (event) {
      if (!event.persisted) {
        return;
      }
      didScroll = false;
      window.scrollTo(0, 0);
      runAfterLayout();
    });
  }

  function shouldNavBeStuck(sentinel, headerTop) {
    if (window.scrollY < 1) {
      return false;
    }

    return sentinel.getBoundingClientRect().top < headerTop - 1;
  }

  function initArticlesStickyNav() {
    var nav = document.querySelector('.rdv-articles-sticky-nav');
    if (!nav) return;

    var sentinel = document.createElement('div');
    sentinel.className = 'rdv-articles-sticky-sentinel';
    sentinel.setAttribute('aria-hidden', 'true');
    nav.parentNode.insertBefore(sentinel, nav);

    var syncStuckFromLayout = function () {
      var headerTop = getHeaderOffset();
      nav.classList.toggle('is-stuck', shouldNavBeStuck(sentinel, headerTop));
    };

    var updateTop = function () {
      var top = getHeaderOffset();
      nav.style.setProperty('--rdv-sticky-top', top + 'px');
      document.documentElement.style.setProperty('--rdv-articles-scroll-offset', getArticlesStickyOffset() + 'px');
      syncStuckFromLayout();
    };

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          var headerTop = getHeaderOffset();
          var stuck = !entry.isIntersecting && shouldNavBeStuck(sentinel, headerTop);
          nav.classList.toggle('is-stuck', stuck);
        });
      },
      { root: null, threshold: 0, rootMargin: '-' + getHeaderOffset() + 'px 0px 0px 0px' }
    );

    var observe = function () {
      observer.disconnect();
      updateTop();
      observer.observe(sentinel);
      syncStuckFromLayout();
    };

    observe();
    window.addEventListener('resize', observe, { passive: true });
    window.addEventListener('scroll', updateTop, { passive: true });
    document.addEventListener('fusion-sticky-change', observe);
    window.addEventListener('load', observe);
    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(syncStuckFromLayout);
    });
  }

  function initCountryStrip() {
    var strip = document.querySelector('.rdv-country-strip');
    if (!strip) return;

    var wrap = strip.closest('.rdv-country-strip-wrap');
    var leftArrow = wrap ? wrap.querySelector('.rdv-country-strip__arrow--left') : null;
    var rightArrow = wrap ? wrap.querySelector('.rdv-country-strip__arrow--right') : null;

    var pills = strip.querySelectorAll('.rdv-country-pill[href^="#"]');
    var sections = [];

    pills.forEach(function (pill) {
      var id = pill.getAttribute('href').slice(1);
      var section = document.getElementById(id);
      if (section) {
        sections.push({ pill: pill, el: section });
      }

      pill.addEventListener('click', function (e) {
        var targetId = pill.getAttribute('href').slice(1);
        var target = document.getElementById(targetId);
        if (!target) return;

        e.preventDefault();
        setActiveCountryPill(pills, pill);
        scrollToCountrySection(target, 'smooth');

        if (history.pushState) {
          history.pushState(null, '', '#' + targetId);
        }
      });
    });

    if (!sections.length) return;

    var updateScrollableState = function () {
      if (!wrap) return;
      var isScrollable = strip.scrollWidth > strip.clientWidth + 4;
      wrap.classList.toggle('is-scrollable', isScrollable);
    };

    var scrollByAmount = function (dir) {
      var amount = Math.max(220, Math.floor(strip.clientWidth * 0.6));
      strip.scrollBy({ left: dir * amount, behavior: 'smooth' });
    };

    if (leftArrow) {
      leftArrow.addEventListener('click', function () { scrollByAmount(-1); });
    }
    if (rightArrow) {
      rightArrow.addEventListener('click', function () { scrollByAmount(1); });
    }

    if (window.location.hash) {
      var hashPill = strip.querySelector('.rdv-country-pill[href="' + window.location.hash + '"]');
      var hashTarget = document.getElementById(window.location.hash.slice(1));
      if (hashPill) {
        setActiveCountryPill(pills, hashPill);
      }
      if (hashTarget) {
        window.requestAnimationFrame(function () {
          scrollToCountrySection(hashTarget, 'auto');
        });
      }
    } else if (pills.length) {
      pills[0].classList.add('is-active');
    }

    var scrollTicking = false;
    var onScroll = function () {
      if (isCountryScrollSpyLocked()) {
        return;
      }

      if (scrollTicking) {
        return;
      }

      scrollTicking = true;
      window.requestAnimationFrame(function () {
        var activeItem = getActiveCountryItem(sections);
        if (activeItem) {
          setActiveCountryPill(pills, activeItem.pill);
        }
        scrollTicking = false;
      });
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // Scroll arrows visibility
    updateScrollableState();
    window.addEventListener('resize', updateScrollableState, { passive: true });
  }

  function initMobileNavSelects() {
    document.querySelectorAll('.rdv-nav-mobile__select').forEach(function (select) {
      select.addEventListener('change', function () {
        var value = select.value;
        if (!value) return;

        if (value.charAt(0) === '#') {
          var target = document.getElementById(value.slice(1));
          if (!target) return;
          scrollToCountrySection(target, 'smooth');
          var strip = document.querySelector('.rdv-country-strip');
          if (strip) {
            var hashPill = strip.querySelector('.rdv-country-pill[href="' + value + '"]');
            if (hashPill) {
              setActiveCountryPill(strip.querySelectorAll('.rdv-country-pill[href^="#"]'), hashPill);
            }
          }
          if (history.pushState) {
            history.pushState(null, '', value);
          }
          return;
        }

        markArticlesFilterNavigation();
        window.location.href = value;
      });
    });
  }

  var articlesHubHeroObserver = null;

  function resetArticlesHubHeroPadding(el) {
    if (!el || !el.style) {
      return;
    }
    el.style.setProperty('padding-top', '0', 'important');
    el.style.setProperty('padding-bottom', '0', 'important');
    el.style.setProperty('margin-top', '0', 'important');
    el.style.setProperty('margin-bottom', '0', 'important');
  }

  function applyArticlesHubFusionHeroStyles() {
    if (!document.body.classList.contains('rdv-articles-hub')) {
      return;
    }

    if (!window.matchMedia('(max-width: 992px)').matches) {
      return;
    }

    document
      .querySelectorAll('body.rdv-articles-hub .hundred-percent-height, body.rdv-articles-hub .hundred-percent-fullwidth.hundred-percent-height')
      .forEach(function (hero) {
        hero.style.setProperty('height', '50vh', 'important');
        hero.style.setProperty('min-height', '50vh', 'important');
        hero.style.setProperty('max-height', '50vh', 'important');
        hero.style.setProperty('position', 'relative', 'important');
        hero.style.setProperty('overflow', 'hidden', 'important');
        resetArticlesHubHeroPadding(hero);
      });

    document.querySelectorAll('body.rdv-articles-hub .hundred-percent-height .fusion-fullwidth-center-content').forEach(function (el) {
      el.style.setProperty('position', 'absolute', 'important');
      el.style.setProperty('top', '0', 'important');
      el.style.setProperty('right', '0', 'important');
      el.style.setProperty('bottom', '0', 'important');
      el.style.setProperty('left', '0', 'important');
      el.style.setProperty('height', '100%', 'important');
      el.style.setProperty('min-height', '100%', 'important');
      el.style.setProperty('max-height', '100%', 'important');
      el.style.setProperty('display', 'flex', 'important');
      el.style.setProperty('align-items', 'center', 'important');
      el.style.setProperty('justify-content', 'center', 'important');
      resetArticlesHubHeroPadding(el);
    });

    document.querySelectorAll('body.rdv-articles-hub .hundred-percent-height .fusion-builder-row').forEach(function (el) {
      el.style.setProperty('width', '100%', 'important');
      el.style.setProperty('max-width', '100%', 'important');
      el.style.setProperty('margin', '0', 'important');
      el.style.setProperty('height', 'auto', 'important');
      resetArticlesHubHeroPadding(el);
    });

    document
      .querySelectorAll(
        'body.rdv-articles-hub .hundred-percent-height .fusion-layout-column, ' +
          'body.rdv-articles-hub .hundred-percent-height .fusion-column-wrapper, ' +
          'body.rdv-articles-hub .hundred-percent-height .fusion-column-content-centered, ' +
          'body.rdv-articles-hub .hundred-percent-height .fusion-column-content, ' +
          'body.rdv-articles-hub .hundred-percent-height .fusion-title.Titre-Homepage'
      )
      .forEach(function (el) {
        resetArticlesHubHeroPadding(el);
        if (el.classList.contains('fusion-column-content-centered') || el.classList.contains('fusion-column-content')) {
          el.style.setProperty('display', 'block', 'important');
          el.style.setProperty('height', 'auto', 'important');
        }
      });

    document.querySelectorAll('body.rdv-articles-hub .hundred-percent-height .fusion-title.Titre-Homepage, body.rdv-articles-hub .hundred-percent-height .Titre-Homepage').forEach(function (el) {
      resetArticlesHubHeroPadding(el);
      el.style.setProperty('--awb-margin-bottom', '0px', 'important');
      el.style.setProperty('position', 'absolute', 'important');
      el.style.setProperty('top', '50%', 'important');
      el.style.setProperty('left', '0', 'important');
      el.style.setProperty('right', '0', 'important');
      el.style.setProperty('width', '100%', 'important');
      el.style.setProperty('transform', 'translateY(-50%)', 'important');
      el.style.setProperty('z-index', '2', 'important');
      el.style.setProperty('opacity', '1', 'important');
    });
  }

  function initArticlesHubFusionHero() {
    applyArticlesHubFusionHeroStyles();

    if (!document.body.classList.contains('rdv-articles-hub')) {
      return;
    }

    if (!window.matchMedia('(max-width: 992px)').matches) {
      return;
    }

    [100, 500, 1500, 2500, 4000].forEach(function (delay) {
      window.setTimeout(applyArticlesHubFusionHeroStyles, delay);
    });

    if (articlesHubHeroObserver) {
      return;
    }

    var heroNodes = document.querySelectorAll(
      'body.rdv-articles-hub .hundred-percent-height .fusion-title.Titre-Homepage'
    );
    if (!heroNodes.length || typeof MutationObserver === 'undefined') {
      return;
    }

    articlesHubHeroObserver = new MutationObserver(function () {
      applyArticlesHubFusionHeroStyles();
    });

    heroNodes.forEach(function (node) {
      articlesHubHeroObserver.observe(node, {
        attributes: true,
        attributeFilter: ['style', 'class'],
      });
    });

    document.querySelectorAll('body.rdv-articles-hub .hundred-percent-height').forEach(function (hero) {
      articlesHubHeroObserver.observe(hero, {
        attributes: true,
        attributeFilter: ['style', 'class'],
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initArticlesFilterScrollReset();
      initArticlesHubFusionHero();
      initLoadMore();
      initTocSidebar();
      initArticlesStickyNav();
      initMobileNavSelects();
      initCountryStrip();
      window.addEventListener('load', initArticlesHubFusionHero);
      window.addEventListener('resize', applyArticlesHubFusionHeroStyles);
      window.addEventListener('orientationchange', applyArticlesHubFusionHeroStyles);
    });
  } else {
    initArticlesFilterScrollReset();
    initArticlesHubFusionHero();
    initLoadMore();
    initTocSidebar();
    initArticlesStickyNav();
    initMobileNavSelects();
    initCountryStrip();
    window.addEventListener('load', initArticlesHubFusionHero);
    window.addEventListener('resize', applyArticlesHubFusionHeroStyles);
    window.addEventListener('orientationchange', applyArticlesHubFusionHeroStyles);
  }
})();
