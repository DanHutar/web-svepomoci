(function () {
  'use strict';
  var header = document.querySelector('.sample-site-header');
  if (!header) return;
  var toggle = header.querySelector('.sample-site-header__toggle');
  var menu = header.querySelector('.sample-site-header__nav');
  if (!toggle || !menu) return;
  var mobile = window.matchMedia('(max-width: 720px)');

  function setOpen(open) {
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    menu.setAttribute('data-open', open ? 'true' : 'false');
  }

  function updateLayout() {
    var focusWasOnToggle = document.activeElement === toggle;
    var focusWasInMenu = menu.contains(document.activeElement);
    toggle.hidden = !mobile.matches;
    setOpen(!mobile.matches);
    if (mobile.matches && focusWasInMenu) toggle.focus();
    if (!mobile.matches && focusWasOnToggle) {
      var firstLink = menu.querySelector('a');
      if (firstLink) firstLink.focus();
    }
  }

  header.setAttribute('data-enhanced', 'true');
  toggle.addEventListener('click', function () {
    setOpen(toggle.getAttribute('aria-expanded') !== 'true');
  });
  header.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && mobile.matches && toggle.getAttribute('aria-expanded') === 'true') {
      setOpen(false);
      toggle.focus();
    }
  });
  menu.addEventListener('click', function (event) {
    if (mobile.matches && event.target.closest('a')) {
      setOpen(false);
      toggle.focus();
    }
  });
  if (mobile.addEventListener) mobile.addEventListener('change', updateLayout);
  else mobile.addListener(updateLayout);
  updateLayout();
}());
