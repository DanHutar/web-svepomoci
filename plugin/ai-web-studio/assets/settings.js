(function () {
  'use strict';
  var button = document.querySelector('[data-aiwp-design-copy]');
  if (!button) return;
  button.addEventListener('click', function () {
    var font = document.getElementById('aiwp-font');
    var option = font.options[font.selectedIndex];
    var css = document.getElementById('aiwp-global-css').value;
    var prompt = [
      window.aiwpDesign.contentLanguageInstruction || '',
      wp.i18n.__("Create shared CSS for a website using the ByYourself Theme theme and ByYourself Builder plugin.", "ai-web-studio"),
      wp.i18n.__("Selected font: ", "ai-web-studio") + option.dataset.family + wp.i18n.__(". Use var(--aiwp-font).", "ai-web-studio"),
      wp.i18n.__("Accent colour: ", "ai-web-studio") + document.getElementById('aiwp-accent').value + wp.i18n.__(" (--aiwp-accent). Content width: ", "ai-web-studio") + document.getElementById('aiwp-width').value + 'px (--aiwp-width).',
      wp.i18n.__("My brief: [ADD the website purpose, target audience and visual style].", "ai-web-studio"),
      window.aiwpDesign.rules,
      wp.i18n.__("Design shared reusable .aiwp-button and .aiwp-card classes with accessible focus states. Do not change existing classes or functionality without explaining why. Shared selectors must not affect unrelated website elements.", "ai-web-studio"),
      wp.i18n.__("Return ONE complete CSS block without style tags, ready for Website design → Shared CSS. Do not create HTML or JS. Preserve existing CSS and incorporate changes; do not return just an addition. Outside the code, briefly explain the type scale and checks for mobile screens and enlarged text.", "ai-web-studio"),
      wp.i18n.__("Current shared CSS:\n", "ai-web-studio") + (css || wp.i18n.__("Currently empty.", "ai-web-studio"))
    ].join('\n\n');
    var status = document.querySelector('[data-aiwp-design-status]');
    function fallback() {
      var panel = document.querySelector('[data-aiwp-design-fallback]');
      panel.hidden = false;
      panel.open = true;
      var field = panel.querySelector('textarea');
      field.value = prompt;
      field.focus();
      field.select();
      status.textContent = wp.i18n.__("Copy the prompt using Ctrl+C (Cmd+C on Mac). It reflects the current form values.", "ai-web-studio");
    }
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(prompt).then(function () {
        status.textContent = wp.i18n.__("Prompt copied. Paste it into your AI, put the resulting CSS in Shared CSS and save the website design.", "ai-web-studio");
      }).catch(fallback);
    } else fallback();
  });
}());
