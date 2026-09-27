(function () {
  'use strict';

  var hero = document.querySelector('[data-gdmb-hero]');
  if (!hero) return;

  var slides = Array.prototype.slice.call(hero.querySelectorAll('[data-hero-slide]'));
  var tabs = Array.prototype.slice.call(hero.querySelectorAll('[data-hero-go]'));
  var previous = hero.querySelector('[data-hero-prev]');
  var next = hero.querySelector('[data-hero-next]');
  var status = hero.querySelector('[data-hero-status]');
  var current = 0;
  var touchStartX = null;

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

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      showSlide(parseInt(tab.getAttribute('data-hero-go'), 10), true);
    });
  });

  previous.addEventListener('click', function () { showSlide(current - 1, true); });
  next.addEventListener('click', function () { showSlide(current + 1, true); });

  hero.addEventListener('keydown', function (event) {
    if (event.key === 'ArrowLeft') showSlide(current - 1, true);
    if (event.key === 'ArrowRight') showSlide(current + 1, true);
  });

  hero.addEventListener('touchstart', function (event) {
    touchStartX = event.changedTouches[0].clientX;
  }, { passive: true });

  hero.addEventListener('touchend', function (event) {
    if (touchStartX === null) return;
    var distance = event.changedTouches[0].clientX - touchStartX;
    touchStartX = null;
    if (Math.abs(distance) < 48) return;
    showSlide(current + (distance < 0 ? 1 : -1), true);
  }, { passive: true });
}());
