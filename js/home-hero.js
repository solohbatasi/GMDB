(function () {
  'use strict';

  var hero = document.querySelector('[data-gdmb-hero]');
  if (!hero) return;

  var slides = Array.prototype.slice.call(hero.querySelectorAll('[data-hero-slide]'));
  var tabs = Array.prototype.slice.call(hero.querySelectorAll('[data-hero-go]'));
  var previous = hero.querySelector('[data-hero-prev]');
  var next = hero.querySelector('[data-hero-next]');
  var autoplayToggle = hero.querySelector('[data-hero-autoplay]');
  var status = hero.querySelector('[data-hero-status]');
  var autoplayDelay = 6000;
  var autoplayTimer = null;
  var current = 0;
  var focusInsideSlide = false;
  var pointerInside = false;
  var touchStartX = null;
  var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
  var userPaused = Boolean(reducedMotion && reducedMotion.matches);

  function showSlide(index, announce) {
    current = (index + slides.length) % slides.length;

    slides.forEach(function (slide, slideIndex) {
      var isActive = slideIndex === current;
      var link = slide.querySelector('a');
      slide.classList.toggle('is-active', isActive);
      slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
      if (link) link.setAttribute('tabindex', isActive ? '0' : '-1');
    });

    tabs.forEach(function (tab, tabIndex) {
      var isActive = tabIndex === current;
      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    if (announce && status) {
      status.textContent = 'Slide ' + (current + 1) + ' of ' + slides.length;
    }
  }

  function stopAutoplay() {
    if (autoplayTimer !== null) {
      window.clearTimeout(autoplayTimer);
      autoplayTimer = null;
    }
  }

  function updateAutoplayToggle() {
    if (!autoplayToggle) return;
    var icon = autoplayToggle.querySelector('i');
    autoplayToggle.setAttribute('aria-pressed', userPaused ? 'true' : 'false');
    autoplayToggle.setAttribute('aria-label', userPaused ? 'Start automatic slide rotation' : 'Pause automatic slide rotation');
    if (icon) icon.className = userPaused ? 'ion-play' : 'ion-pause';
  }

  function scheduleAutoplay() {
    stopAutoplay();
    if (userPaused || pointerInside || focusInsideSlide || document.hidden || slides.length < 2) return;
    autoplayTimer = window.setTimeout(function () {
      showSlide(current + 1, false);
      scheduleAutoplay();
    }, autoplayDelay);
  }

  function showManualSlide(index) {
    showSlide(index, true);
    scheduleAutoplay();
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      showManualSlide(parseInt(tab.getAttribute('data-hero-go'), 10));
    });
  });

  previous.addEventListener('click', function () { showManualSlide(current - 1); });
  next.addEventListener('click', function () { showManualSlide(current + 1); });

  if (autoplayToggle) {
    autoplayToggle.addEventListener('click', function () {
      userPaused = !userPaused;
      updateAutoplayToggle();
      scheduleAutoplay();
    });
  }

  hero.addEventListener('keydown', function (event) {
    if (event.key === 'ArrowLeft') showManualSlide(current - 1);
    if (event.key === 'ArrowRight') showManualSlide(current + 1);
  });

  hero.addEventListener('mouseenter', function () {
    pointerInside = true;
    stopAutoplay();
  });

  hero.addEventListener('mouseleave', function () {
    pointerInside = false;
    scheduleAutoplay();
  });

  hero.addEventListener('focusin', function (event) {
    if (!event.target.closest('[data-hero-slide]')) return;
    focusInsideSlide = true;
    stopAutoplay();
  });

  hero.addEventListener('focusout', function (event) {
    if (event.relatedTarget && event.relatedTarget.closest && event.relatedTarget.closest('[data-hero-slide]')) return;
    focusInsideSlide = false;
    scheduleAutoplay();
  });

  hero.addEventListener('touchstart', function (event) {
    touchStartX = event.changedTouches[0].clientX;
    stopAutoplay();
  }, { passive: true });

  hero.addEventListener('touchend', function (event) {
    if (touchStartX === null) return;
    var distance = event.changedTouches[0].clientX - touchStartX;
    touchStartX = null;
    if (Math.abs(distance) >= 48) {
      showManualSlide(current + (distance < 0 ? 1 : -1));
      return;
    }
    scheduleAutoplay();
  }, { passive: true });

  document.addEventListener('visibilitychange', scheduleAutoplay);

  if (reducedMotion) {
    var handleMotionPreference = function (event) {
      userPaused = event.matches;
      updateAutoplayToggle();
      scheduleAutoplay();
    };
    if (reducedMotion.addEventListener) {
      reducedMotion.addEventListener('change', handleMotionPreference);
    } else if (reducedMotion.addListener) {
      reducedMotion.addListener(handleMotionPreference);
    }
  }

  updateAutoplayToggle();
  scheduleAutoplay();
}());
