(function () {
  'use strict';
  var button = document.querySelector('[data-aiwp-design-copy]');
  if (!button) return;
  button.addEventListener('click', function () {
    var font = document.getElementById('aiwp-font');
    var option = font.options[font.selectedIndex];
    var css = document.getElementById('aiwp-global-css').value;
    var prompt = [
      'Vytvoř společné CSS pro web se šablonou web-svepomoci-sablona a pluginem web-svepomoci-plugin.',
      'Zvolený font: ' + option.dataset.family + '. Používej var(--aiwp-font).',
      'Hlavní barva: ' + document.getElementById('aiwp-accent').value + ' (--aiwp-accent). Šířka obsahu: ' + document.getElementById('aiwp-width').value + 'px (--aiwp-width).',
      'Moje zadání: [DOPLŇ účel webu, cílovou skupinu a náladu vzhledu].',
      window.aiwpDesign.rules,
      'Navrhni společné opakovaně použitelné třídy .aiwp-button a .aiwp-card včetně přístupného focusu. Neměň existující třídy ani funkce bez vysvětlení. Společné selektory nesmějí zasahovat do cizích částí webu.',
      'Vrať JEDEN úplný blok CSS bez značek style, který vložím do Vzhled webu → Společné CSS. Nevytvářej HTML ani JS. Stávající CSS zachovej a zapracuj úpravy; nevracej jen doplněk. Mimo kód stručně popiš stupnici a kontrolu na mobilu a při zvětšení textu.',
      'Současné společné CSS:\n' + (css || 'Zatím prázdné.')
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
      status.textContent = 'Zadání zkopírujte pomocí Ctrl+C (na Macu Cmd+C). Odpovídá aktuálním hodnotám formuláře.';
    }
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(prompt).then(function () {
        status.textContent = 'Zadání je zkopírované. Vložte je do AI, výsledné CSS vložte do Společného CSS a uložte vzhled webu.';
      }).catch(fallback);
    } else fallback();
  });
}());
