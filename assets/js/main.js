/**
* Template Name: BizLand
* Template URL: https://bootstrapmade.com/bizland-bootstrap-business-template/
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
    if (!selectHeader) return;
    if (!selectHeader.classList.contains('scroll-up-sticky') && !selectHeader.classList.contains('sticky-top') && !selectHeader.classList.contains('fixed-top')) return;
    window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
  }

  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);

  /**
   * Mobile nav toggle
   */
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');

  function mobileNavToogle() {
    document.querySelector('body').classList.toggle('mobile-nav-active');
    mobileNavToggleBtn.classList.toggle('bi-list');
    mobileNavToggleBtn.classList.toggle('bi-x');
  }
  if (mobileNavToggleBtn) {
    mobileNavToggleBtn.addEventListener('click', mobileNavToogle);
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
  if (document.querySelector('.glightbox') && typeof GLightbox !== 'undefined') {
    GLightbox({
      selector: '.glightbox'
    });
  }

  /**
   * Animate the skills items on reveal
   */
  let skillsAnimation = document.querySelectorAll('.skills-animation');
  if (skillsAnimation.length && typeof Waypoint !== 'undefined') {
    skillsAnimation.forEach((item) => {
      new Waypoint({
        element: item,
        offset: '80%',
        handler: function(direction) {
          let progress = item.querySelectorAll('.progress .progress-bar');
          progress.forEach(el => {
            el.style.width = el.getAttribute('aria-valuenow') + '%';
          });
        }
      });
    });
  }

  /**
   * Initiate Pure Counter
   */
  if (document.querySelector('.purecounter') && typeof PureCounter !== 'undefined') {
    new PureCounter();
  }

  /**
   * Init swiper sliders
   */
  function initSwiper() {
    document.querySelectorAll(".init-swiper").forEach(function(swiperElement) {
      if (typeof Swiper === 'undefined') return;
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
   * Init isotope layout and filters
   */
  document.querySelectorAll('.isotope-layout').forEach(function(isotopeItem) {
    if (typeof imagesLoaded === 'undefined' || typeof Isotope === 'undefined') return;
    let layout = isotopeItem.getAttribute('data-layout') ?? 'masonry';
    let filter = isotopeItem.getAttribute('data-default-filter') ?? '*';
    let sort = isotopeItem.getAttribute('data-sort') ?? 'original-order';

    let initIsotope;
    imagesLoaded(isotopeItem.querySelector('.isotope-container'), function() {
      initIsotope = new Isotope(isotopeItem.querySelector('.isotope-container'), {
        itemSelector: '.isotope-item',
        layoutMode: layout,
        filter: filter,
        sortBy: sort
      });
    });

    isotopeItem.querySelectorAll('.isotope-filters li').forEach(function(filters) {
      filters.addEventListener('click', function() {
        isotopeItem.querySelector('.isotope-filters .filter-active').classList.remove('filter-active');
        this.classList.add('filter-active');
        initIsotope.arrange({
          filter: this.getAttribute('data-filter')
        });
        if (typeof aosInit === 'function') {
          aosInit();
        }
      }, false);
    });

  });

  /**
   * Frequently Asked Questions Toggle
   */
  document.querySelectorAll('.faq-item h3, .faq-item .faq-toggle').forEach((faqItem) => {
    faqItem.addEventListener('click', () => {
      faqItem.parentNode.classList.toggle('faq-active');
    });
  });

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

})();


/*===========================js de buscador===========================*/
// ==========================
// Abrir / cerrar buscador
// ==========================
const navSearchBtn = document.querySelector('.nav-search-toggle');
const navSearchDropdown = document.querySelector('.nav-search-dropdown');
const searchInput = document.getElementById('navSearchInput');
const searchForm = document.getElementById('navSearchForm');
const searchResults = document.getElementById('searchResults');

if (navSearchBtn && navSearchDropdown && searchInput && searchForm && searchResults) {
  navSearchBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    navSearchDropdown.classList.toggle('show');
    searchInput.focus();
  });

  document.addEventListener('click', () => {
    navSearchDropdown.classList.remove('show');
    searchResults.classList.add('d-none');
  });

  navSearchDropdown.addEventListener('click', (e) => {
    e.stopPropagation();
  });

  const services = [
    { name: "Landing Page Express", url: "web-inicial.html" },
    { name: "Diseño y Desarrollo Web", url: "desarrollo-web.html" },
    { name: "Configuración y soporte tecnológico", url: "configuracion-soporte-tecnico.html" },
    { name: "Asesorías Contables", url: "asesorías-contable.html" }
  ];

  searchInput.addEventListener('input', () => {
    const query = searchInput.value.toLowerCase().trim();
    searchResults.innerHTML = '';

    if (!query) {
      searchResults.classList.add('d-none');
      return;
    }

    const filtered = services.filter(s =>
      s.name.toLowerCase().includes(query)
    );

    if (filtered.length === 0) {
      searchResults.innerHTML = '<div class="result-item">No se encontraron resultados</div>';
    } else {
      filtered.forEach(s => {
        const item = document.createElement('a');
        item.href = s.url;
        item.textContent = s.name;
        item.classList.add('result-item');
        searchResults.appendChild(item);
      });
    }

    searchResults.classList.remove('d-none');
  });

  searchForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const first = searchResults.querySelector('a');
    if (first) first.click();
  });
}



const contactForm = document.getElementById('contactForm');

if (contactForm) {
  const loadingMessage = contactForm.querySelector('.loading');
  const errorMessage = contactForm.querySelector('.error-message');
  const sentMessage = contactForm.querySelector('.sent-message');
  const subjectField = contactForm.querySelector('[name="subject"]');
  const requestedService = new URLSearchParams(window.location.search).get('servicio');

  if (subjectField && requestedService && !subjectField.value) {
    subjectField.value = requestedService.slice(0, 200);
  }

  contactForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    errorMessage.classList.add('d-none');
    sentMessage.classList.add('d-none');
    const submitButton = contactForm.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    loadingMessage.classList.remove('d-none');

    try {
      const response = await fetch('forms/contact.php', {
        method: 'POST',
        body: new FormData(contactForm),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const result = await response.json();
      if (!response.ok) {
        throw new Error(result.message || 'No se pudo enviar el mensaje.');
      }
      sentMessage.textContent = result.message;
      sentMessage.classList.remove('d-none');
      contactForm.reset();
      if (subjectField && requestedService) {
        subjectField.value = requestedService.slice(0, 200);
      }
    } catch (error) {
      errorMessage.textContent = error instanceof Error ? error.message : 'Error al enviar el mensaje. Inténtalo nuevamente.';
      errorMessage.classList.remove('d-none');
    } finally {
      submitButton.disabled = false;
      loadingMessage.classList.add('d-none');
    }
  });
}
