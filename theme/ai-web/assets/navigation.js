(function () {
  'use strict';
  var toggle = document.querySelector('.ai-web-menu-toggle');
  var navigation = document.getElementById('ai-web-navigation');
  if (!toggle || !navigation) return;

  var narrow = window.matchMedia('(max-width: 720px)');
  var expanded = false;
  function render() {
    navigation.hidden = narrow.matches && !expanded;
    toggle.setAttribute('aria-expanded', String(narrow.matches ? expanded : true));
  }
  toggle.hidden = false;
  toggle.addEventListener('click', function () {
    expanded = !expanded;
    render();
  });
  navigation.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && narrow.matches && expanded) {
      expanded = false;
      render();
      toggle.focus();
    }
  });
  navigation.addEventListener('click', function (event) {
    var link = event.target.closest('a');
    if (link && narrow.matches) {
      expanded = false;
      render();
    }
  });
  function resized() {
    expanded = false;
    render();
  }
  if (narrow.addEventListener) narrow.addEventListener('change', resized);
  else narrow.addListener(resized);
  render();
}());
