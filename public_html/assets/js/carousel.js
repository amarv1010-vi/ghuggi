/* JSD Construction - draggable horizontal carousel for the Photos section.
   Cursor drag, touch swipe, arrow keys, scroll-snap and prefers-reduced-motion
   respected. Progressive enhancement: works as a plain scroller without JS. */
(function () {
  'use strict';

  var root = document.querySelector('[data-carousel]');
  if (!root) return;

  var track = root.querySelector('[data-carousel-track]');
  var prev = root.querySelector('[data-carousel-prev]');
  var next = root.querySelector('[data-carousel-next]');
  if (!track) return;

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function pageStep() {
    var item = track.querySelector('.carousel__item');
    var gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || '0') || 0;
    return item ? item.getBoundingClientRect().width + gap : track.clientWidth * 0.8;
  }

  function scrollByStep(dir) {
    track.scrollBy({ left: dir * pageStep(), behavior: reduceMotion ? 'auto' : 'smooth' });
  }

  /* ----- Arrow buttons ----- */
  function updateButtons() {
    if (!prev || !next) return;
    var max = track.scrollWidth - track.clientWidth - 1;
    var overflowing = max > 1;
    prev.hidden = !overflowing;
    next.hidden = !overflowing;
    if (overflowing) {
      prev.disabled = track.scrollLeft <= 1;
      next.disabled = track.scrollLeft >= max;
      prev.style.opacity = prev.disabled ? '0.4' : '';
      next.style.opacity = next.disabled ? '0.4' : '';
    }
  }
  if (prev) prev.addEventListener('click', function () { scrollByStep(-1); });
  if (next) next.addEventListener('click', function () { scrollByStep(1); });
  track.addEventListener('scroll', updateButtons, { passive: true });
  window.addEventListener('resize', updateButtons);

  /* ----- Keyboard ----- */
  track.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowRight') { e.preventDefault(); scrollByStep(1); }
    else if (e.key === 'ArrowLeft') { e.preventDefault(); scrollByStep(-1); }
    else if (e.key === 'Home') { e.preventDefault(); track.scrollTo({ left: 0, behavior: reduceMotion ? 'auto' : 'smooth' }); }
    else if (e.key === 'End') { e.preventDefault(); track.scrollTo({ left: track.scrollWidth, behavior: reduceMotion ? 'auto' : 'smooth' }); }
  });

  /* ----- Pointer drag (mouse) ----- */
  var isDown = false, startX = 0, startScroll = 0, moved = false;

  track.addEventListener('pointerdown', function (e) {
    if (e.pointerType === 'mouse' && e.button !== 0) return;
    if (e.pointerType !== 'mouse') return; // touch uses native scroll
    isDown = true; moved = false;
    startX = e.clientX; startScroll = track.scrollLeft;
    track.classList.add('is-dragging');
  });
  track.addEventListener('pointermove', function (e) {
    if (!isDown) return;
    var dx = e.clientX - startX;
    if (Math.abs(dx) > 3) moved = true;
    track.scrollLeft = startScroll - dx;
  });
  function endDrag() {
    if (!isDown) return;
    isDown = false;
    track.classList.remove('is-dragging');
  }
  track.addEventListener('pointerup', endDrag);
  track.addEventListener('pointercancel', endDrag);
  track.addEventListener('pointerleave', endDrag);
  // Prevent click-through after a drag.
  track.addEventListener('click', function (e) {
    if (moved) { e.preventDefault(); }
  }, true);
  track.addEventListener('dragstart', function (e) { e.preventDefault(); });

  updateButtons();
})();
