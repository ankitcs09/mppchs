/**
* Template Name: Medilab
* Template URL: https://bootstrapmade.com/medilab-free-medical-bootstrap-theme/
* Updated: Aug 07 2024 with Bootstrap v5.3.3
* Author: BootstrapMade.com
* License: https://bootstrapmade.com/license/
*/

(function() {
  "use strict";

  /**
   * Apply .scrolled class to the body as the page is scrolled down
   */
  function toggleScrolled() {
    const selectBody = document.querySelector('body');
    const selectHeader = document.querySelector('#header');
    if (!selectHeader.classList.contains('scroll-up-sticky') && !selectHeader.classList.contains('sticky-top') && !selectHeader.classList.contains('fixed-top')) return;
    window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
  }

  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);

  /**
   * Mobile nav toggle
   */
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');
  const mobileNavToggleIcon = mobileNavToggleBtn ? mobileNavToggleBtn.querySelector('i') : null;

  function mobileNavToogle() {
    document.body.classList.toggle('mobile-nav-active');
    if (mobileNavToggleIcon) {
      mobileNavToggleIcon.classList.toggle('fa-bars');
      mobileNavToggleIcon.classList.toggle('fa-xmark');
    }
  }
  if (mobileNavToggleBtn) {
    mobileNavToggleBtn.addEventListener('click', mobileNavToogle);
  }

  /**
   * Accessibility toolbar: font scale + screen reader mode
   */
  const FONT_SCALE_KEY = 'mppgcl-font-scale';
  const SCREEN_READER_KEY = 'mppgcl-screen-reader';
  const fontScaleButtons = document.querySelectorAll('[data-font-scale]');
  const screenReaderToggle = document.querySelector('[data-screen-reader-toggle]');

  function applyFontScale(scale) {
    document.body.classList.remove('font-scale-sm', 'font-scale-lg');
    if (scale === 'sm') {
      document.body.classList.add('font-scale-sm');
    } else if (scale === 'lg') {
      document.body.classList.add('font-scale-lg');
    }
    fontScaleButtons.forEach((btn) => {
      const isActive = btn.dataset.fontScale === scale;
      btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
  }

  if (fontScaleButtons.length) {
    const savedScale = localStorage.getItem(FONT_SCALE_KEY) || 'md';
    applyFontScale(savedScale);
    fontScaleButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const targetScale = btn.dataset.fontScale || 'md';
        localStorage.setItem(FONT_SCALE_KEY, targetScale);
        applyFontScale(targetScale);
      });
    });
  }

  function applyScreenReader(enabled) {
    document.body.classList.toggle('screen-reader-mode', enabled);
    if (screenReaderToggle) {
      screenReaderToggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
      screenReaderToggle.dataset.screenReaderToggle = enabled ? 'true' : 'false';
    }
  }

  if (screenReaderToggle) {
    const savedSrPref = localStorage.getItem(SCREEN_READER_KEY) === 'true';
    applyScreenReader(savedSrPref);
    screenReaderToggle.addEventListener('click', () => {
      const nextValue = !document.body.classList.contains('screen-reader-mode');
      localStorage.setItem(SCREEN_READER_KEY, nextValue ? 'true' : 'false');
      applyScreenReader(nextValue);
    });
  }

  /**
   * Hide mobile nav on same-page/hash links
   */
  document.querySelectorAll('#navmenu a').forEach(navmenu => {
    navmenu.addEventListener('click', () => {
      if (document.querySelector('.mobile-nav-active')) {
        mobileNavToogle();
      }
    });

  });

  /**
   * Toggle mobile nav dropdowns
   */
  document.querySelectorAll('.navmenu .toggle-dropdown').forEach(navmenu => {
    navmenu.addEventListener('click', function(e) {
      e.preventDefault();
      this.parentNode.classList.toggle('active');
      this.parentNode.nextElementSibling.classList.toggle('dropdown-active');
      e.stopImmediatePropagation();
    });
  });

  /**
   * Preloader
   */
  const preloader = document.querySelector('#preloader');
  if (preloader) {
    window.addEventListener('load', () => {
      preloader.remove();
    });
  }

  /**
   * Scroll top button
   */
  let scrollTop = document.querySelector('.scroll-top');

  function toggleScrollTop() {
    if (scrollTop) {
      window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
    }
  }
  if (scrollTop) {
    scrollTop.addEventListener('click', (e) => {
      e.preventDefault();
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  window.addEventListener('load', toggleScrollTop);
  document.addEventListener('scroll', toggleScrollTop);

  /**
   * Animation on scroll function and init
   */
  function aosInit() {
    AOS.init({
      duration: 600,
      easing: 'ease-in-out',
      once: true,
      mirror: false
    });
  }
  window.addEventListener('load', aosInit);

  /**
   * Initiate glightbox
   */
  const glightbox = GLightbox({
    selector: '.glightbox'
  });

  /**
   * Initiate Pure Counter
   */
  new PureCounter();

  /**
   * Frequently Asked Questions Toggle
   */
  document.querySelectorAll('.faq-item h3, .faq-item .faq-toggle').forEach((faqItem) => {
    faqItem.addEventListener('click', () => {
      faqItem.parentNode.classList.toggle('faq-active');
    });
  });

  /**
   * Init swiper sliders
   */
  function initSwiper() {
    document.querySelectorAll(".init-swiper").forEach(function(swiperElement) {
      let config = JSON.parse(
        swiperElement.querySelector(".swiper-config").innerHTML.trim()
      );

      if (swiperElement.classList.contains("swiper-tab")) {
        initSwiperWithCustomPagination(swiperElement, config);
      } else {
        new Swiper(swiperElement, config);
      }
    });
  }

  window.addEventListener("load", initSwiper);

  /**
   * Correct scrolling position upon page load for URLs containing hash links.
   */
  window.addEventListener('load', function(e) {
    if (window.location.hash) {
      if (document.querySelector(window.location.hash)) {
        setTimeout(() => {
          let section = document.querySelector(window.location.hash);
          let scrollMarginTop = getComputedStyle(section).scrollMarginTop;
          window.scrollTo({
            top: section.offsetTop - parseInt(scrollMarginTop),
            behavior: 'smooth'
          });
        }, 100);
      }
    }
  });

  /**
   * Navmenu Scrollspy
   */
  let navmenulinks = document.querySelectorAll('.navmenu a');

  function navmenuScrollspy() {
    navmenulinks.forEach(navmenulink => {
      if (!navmenulink.hash) return;
      let section = document.querySelector(navmenulink.hash);
      if (!section) return;
      let position = window.scrollY + 200;
      if (position >= section.offsetTop && position <= (section.offsetTop + section.offsetHeight)) {
        document.querySelectorAll('.navmenu a.active').forEach(link => link.classList.remove('active'));
        navmenulink.classList.add('active');
      } else {
        navmenulink.classList.remove('active');
      }
    })
  }
  window.addEventListener('load', navmenuScrollspy);
  document.addEventListener('scroll', navmenuScrollspy);

  /**
   * FAQ filters & search
   */
  function initFaqFilters() {
    const searchInput = document.querySelector('#faq-search');
    const chips = document.querySelectorAll('.faq-chip');
    const faqItems = document.querySelectorAll('.faq-item');
    const categories = document.querySelectorAll('.faq-category');
    const emptyState = document.querySelector('#faq-empty');
    const hindiToggles = document.querySelectorAll('.faq-hindi-toggle');
    if (!faqItems.length) return;

    let activeCategory = 'all';

    function applyFilters() {
      const term = (searchInput?.value || '').trim().toLowerCase();
      let visibleCount = 0;
      const categoryCounts = {};

      faqItems.forEach((item) => {
        const matchesCategory = activeCategory === 'all' || item.dataset.category === activeCategory;
        const matchesSearch = !term || (item.dataset.search || '').includes(term);
        const shouldShow = matchesCategory && matchesSearch;
        item.classList.toggle('d-none', !shouldShow);
        if (shouldShow) {
          visibleCount++;
          const cat = item.dataset.category;
          categoryCounts[cat] = (categoryCounts[cat] || 0) + 1;
        }
      });

      const matchingCategories = [];
      categories.forEach((categoryEl) => {
        const catId = categoryEl.dataset.categoryId;
        const hasMatches = !!categoryCounts[catId];
        categoryEl.classList.toggle('d-none', !hasMatches);
        if (hasMatches) {
          matchingCategories.push(categoryEl);
        } else {
          const collapseEl = categoryEl.querySelector('.accordion-collapse');
          if (collapseEl && typeof bootstrap !== 'undefined') {
            const instance = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
            instance.hide();
          }
        }
      });

      if (emptyState) {
        emptyState.classList.toggle('d-none', visibleCount !== 0);
      }

      if (term && matchingCategories.length) {
        const firstCollapse = matchingCategories[0].querySelector('.accordion-collapse');
        if (firstCollapse && typeof bootstrap !== 'undefined' && !firstCollapse.classList.contains('show')) {
          bootstrap.Collapse.getOrCreateInstance(firstCollapse, { toggle: false }).show();
        }
      }
    }

    chips.forEach((chip) => {
      chip.addEventListener('click', () => {
        chips.forEach((btn) => btn.classList.remove('active'));
        chip.classList.add('active');
        activeCategory = chip.dataset.filter || 'all';
        if (searchInput) {
          searchInput.value = searchInput.value.trim();
        }
        applyFilters();
      });
    });

    if (searchInput) {
      searchInput.addEventListener('input', () => {
        applyFilters();
      });
    }

    hindiToggles.forEach((btn) => {
      btn.addEventListener('click', () => {
        const target = document.querySelector(btn.dataset.target);
        if (!target) return;
        target.classList.toggle('d-none');
        btn.textContent = target.classList.contains('d-none') ? 'हिंदी देखें' : 'Hide Hindi';
      });
    });

    applyFilters();
  }

  window.addEventListener('load', initFaqFilters);

})();

