(function () {
  'use strict';

  function ready() {
    var studio = document.querySelector('.aiwp-studio');
    if (!studio) return;
    var config = window.aiwpAdmin || {};
    var codeEditors = {};
    var fields = {};
    var tabs = Array.prototype.slice.call(studio.querySelectorAll('[data-aiwp-tab]'));
    var actionStatus = studio.querySelector('[data-aiwp-action-status]');
    var previewStatus = studio.querySelector('[data-aiwp-preview-status]');
    var frame = studio.querySelector('iframe');
    var previewRendered = false;
    var dirty = false;

    function value(key) {
      return codeEditors[key] ? codeEditors[key].getValue() : fields[key].value;
    }

    function updateCodeStatus(key) {
      dirty = true;
      studio.querySelector('[data-aiwp-filled="' + key + '"]').hidden = !value(key).trim();
      if (previewRendered) previewStatus.textContent = 'Obsah se změnil. Obnovte náhled.';
      studio.querySelector('[data-aiwp-code-status]').textContent = 'Změny uložte ve WordPressu.';
    }

    ['html', 'css', 'js'].forEach(function (key) {
      fields[key] = document.getElementById('aiwp-' + key);
      if (window.wp && window.wp.codeEditor && config.editors && config.editors[key]) {
        var editor = window.wp.codeEditor.initialize(fields[key], config.editors[key]);
        if (editor && editor.codemirror) {
          codeEditors[key] = editor.codemirror;
          editor.codemirror.on('change', function () {
            editor.codemirror.save();
            fields[key].dispatchEvent(new Event('change', { bubbles: true }));
            updateCodeStatus(key);
          });
        }
      }
      fields[key].addEventListener('input', function () { updateCodeStatus(key); });
    });

    function activateTab(key, focus) {
      tabs.forEach(function (tab) {
        var active = tab.dataset.aiwpTab === key;
        tab.classList.toggle('is-active', active);
        tab.setAttribute('aria-selected', String(active));
        tab.tabIndex = active ? 0 : -1;
        document.getElementById('aiwp-panel-' + tab.dataset.aiwpTab).hidden = !active;
        if (active && focus) tab.focus();
      });
      if (codeEditors[key]) codeEditors[key].refresh();
    }

    tabs.forEach(function (tab, index) {
      tab.addEventListener('click', function () { activateTab(tab.dataset.aiwpTab, false); });
      tab.addEventListener('keydown', function (event) {
        var next = index;
        if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
        else if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = tabs.length - 1;
        else return;
        event.preventDefault();
        activateTab(tabs[next].dataset.aiwpTab, true);
      });
    });
    activateTab('html', false);

    var postForm = document.getElementById('post');
    function updateMode() {
      var enabled = document.getElementById('aiwp-enabled').checked;
      document.body.classList.toggle('aiwp-code-active', studio.dataset.aiwpKind === 'page' && enabled);
      var status = studio.querySelector('[data-aiwp-mode-status]');
      if (status) {
        status.textContent = enabled ? 'Použití vlastního kódu je zapnuté. Změny se na web projeví po uložení nebo publikování.' : 'Použití vlastního kódu je vypnuté. Náhled ho může zobrazit, ale na web se neprojeví, dokud tuto volbu nezapnete a neuložíte.';
        status.classList.toggle('is-disabled', !enabled);
      }
    }
    document.getElementById('aiwp-enabled').addEventListener('change', updateMode);
    updateMode();
    if (postForm) postForm.addEventListener('submit', function (event) {
      Object.keys(codeEditors).forEach(function (key) { codeEditors[key].save(); });
      var error = validationMessage(value('html'), value('css'), value('js'));
      if (error) {
        event.preventDefault();
        actionStatus.textContent = error;
        actionStatus.scrollIntoView({ block: 'center' });
        return;
      }
      dirty = false;
    });
    document.querySelectorAll('[name^="aiwp["]').forEach(function (field) {
      field.addEventListener('input', function () { dirty = true; });
      field.addEventListener('change', function () { dirty = true; });
    });
    window.addEventListener('beforeunload', function (event) {
      if (dirty) { event.preventDefault(); event.returnValue = ''; }
    });

    function promptText() {
      var part = studio.dataset.aiwpKind !== 'page';
      var location = studio.dataset.aiwpKind === 'footer' ? 'footer' : 'primary';
      return [
        'Vytvoř ' + (part ? (location === 'footer' ? 'společnou patičku' : 'společnou hlavičku') : 'obsah stránky') + ' pro WordPress s šablonou web-svepomoci-sablona a pluginem web-svepomoci-plugin.',
        'Moje zadání: [DOPLŇ účel, texty, barvy, cílové publikum a požadované sekce].',
        'Vrať přesně tři oddělené části označené HTML, CSS a JS, které zkopíruji do samostatných polí.',
        'HTML je jen fragment obsahu: bez doctype, html, head, body, style, script, PHP, on* atributů a javascript: URL. ' + (part ? 'Vrať pouze požadovanou hlavičku nebo patičku.' : 'Hlavičku a patičku spravuji zvlášť, nevytvářej je. Začni jedním hlavním nadpisem H1; nepřidávej další značku main.'),
        'CSS vrať bez značek style. Všechny selektory omez na jedinečnou kořenovou třídu tohoto fragmentu; nepoužívej globální body, h1, button apod. K dispozici jsou proměnné --aiwp-accent, --aiwp-width a --aiwp-font.',
        config.designContext || '',
        'Menu spravuji přes Vzhled → Menu. ' + (part ? 'Do nav vlož přesně [aiwp_menu location="' + location + '"].' : 'Pokud potřebuji menu uvnitř obsahu, použij [aiwp_menu location="primary"] nebo [aiwp_menu location="footer"].') + ' Tato jediná podporovaná značka vytvoří ul.aiwp-menu s li.menu-item a odkazy a; případné podmenu je ul.sub-menu. CSS přizpůsob této struktuře a zahrň přístupné zobrazení podmenu. Odkazy ručně nevypisuj. Jiné shortcody nejsou podporované.',
        'JS je nepovinný čistý JavaScript bez značek script, bez knihoven a externích skriptů. Selektory omez na kořen fragmentu; kód uzavři do IIFE a počítej s načteným DOM. Nepoužívej PHP ani volání WordPress funkcí.',
        'Návrh musí být responzivní, čitelný a přístupný z klávesnice. Dodrž kontrast, přidej popisy ovládání a respektuj prefers-reduced-motion.',
        'Používej skutečné dodané URL obrázků; pokud chybějí, vytvoř vzhled bez fotografií. Nevymýšlej neexistující funkční formulář, platební bránu ani odesílání e-mailů.',
        'Texty napiš česky a označ místa, kde mám doplnit vlastní údaje. Připoj navržený SEO titulek a meta popis mimo bloky kódu.'
      ].join('\n\n');
    }

    studio.querySelector('[data-aiwp-copy-prompt]').addEventListener('click', function () {
      var prompt = promptText();
      function fallback() {
        var panel = studio.querySelector('[data-aiwp-prompt-fallback]');
        panel.hidden = false;
        panel.open = true;
        var textarea = panel.querySelector('textarea');
        textarea.value = prompt;
        textarea.focus();
        textarea.select();
        actionStatus.textContent = 'Označené zadání zkopírujte pomocí Ctrl+C (na Macu Cmd+C).';
      }
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(prompt).then(function () {
          actionStatus.textContent = 'Zadání je zkopírované. Vložte ho do své AI a doplňte svou představu.';
        }).catch(fallback);
      } else fallback();
    });

    var insertMenuButton = studio.querySelector('[data-aiwp-insert-menu]');
    if (insertMenuButton) insertMenuButton.addEventListener('click', function () {
      var location = insertMenuButton.dataset.aiwpLocation === 'footer' ? 'footer' : 'primary';
      var marker = '[aiwp_menu location="' + location + '"]';
      activateTab('html', false);
      if (codeEditors.html) {
        codeEditors.html.replaceSelection(marker, 'end');
        codeEditors.html.focus();
      } else {
        var htmlField = fields.html;
        htmlField.setRangeText(marker, htmlField.selectionStart, htmlField.selectionEnd, 'end');
        htmlField.dispatchEvent(new Event('input', { bubbles: true }));
        htmlField.dispatchEvent(new Event('change', { bubbles: true }));
        htmlField.focus();
      }
      actionStatus.textContent = 'Značka menu je vložená do HTML. Umístěte ji dovnitř nav místo původních odkazů. Použití vlastního kódu musí být zapnuté; změny uložte tlačítkem Aktualizovat.';
    });

    studio.querySelector('[data-aiwp-sample]').addEventListener('click', function () {
      if (['html', 'css', 'js'].some(function (key) { return value(key).trim(); })) {
        if (!window.confirm('Ukázka nahradí aktuální HTML, CSS a JavaScript v editoru. Pokračovat? Změna se na web uloží až tlačítkem Aktualizovat nebo Publikovat.')) return;
      }
      var part = studio.dataset.aiwpKind !== 'page';
      var location = studio.dataset.aiwpKind === 'footer' ? 'footer' : 'primary';
      var sample = {
        html: part ? '<div class="moje-cast">\n  <a class="moje-cast__brand" href="/">Název vašeho webu</a>\n  <nav aria-label="Navigace webu">[aiwp_menu location="' + location + '"]</nav>\n</div>' : '<section class="moje-stranka">\n  <div class="moje-stranka__obsah">\n    <p class="moje-stranka__stitky">VÁŠ NOVÝ ZAČÁTEK</p>\n    <h1>Velké nápady začínají první stránkou.</h1>\n    <p>Představte návštěvníkům, co děláte a s čím jim pomůžete. Tento text nahraďte vlastním příběhem.</p>\n    <a class="moje-stranka__tlacitko" href="#vice">Zjistit více <span aria-hidden="true">↗</span></a>\n  </div>\n  <div id="vice" class="moje-stranka__karty">\n    <article><span>01</span><h2>Váš příběh</h2><p>Co vás přivedlo k tomu, co dnes děláte?</p></article>\n    <article><span>02</span><h2>Vaše služby</h2><p>Popište konkrétní pomoc, kterou nabízíte.</p></article>\n    <article><span>03</span><h2>Další krok</h2><p>Řekněte návštěvníkům, jak vás mohou kontaktovat.</p></article>\n  </div>\n</section>',
        css: part ? '.moje-cast { max-width: var(--aiwp-width, 1200px); margin: auto; padding: 24px; display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; font-family: var(--aiwp-font, sans-serif); }\n.moje-cast a { color: inherit; text-decoration: none; }\n.moje-cast a:hover { text-decoration: underline; }\n.moje-cast a:focus-visible { outline: 3px solid var(--aiwp-accent, #2563eb); outline-offset: 5px; }\n.moje-cast__brand { font-size: 22px; font-weight: 750; }\n.moje-cast nav { display: flex; flex-wrap: wrap; gap: 24px; }' : '.moje-stranka { font-family: var(--aiwp-font, sans-serif); color: #1b2838; background: #f6f8fb; padding: clamp(32px, 7vw, 96px) 24px; }\n.moje-stranka * { box-sizing: border-box; }\n.moje-stranka__obsah, .moje-stranka__karty { max-width: var(--aiwp-width, 1200px); margin-inline: auto; }\n.moje-stranka__stitky { font-size: 12px; font-weight: 700; letter-spacing: .14em; color: #425570; }\n.moje-stranka h1 { font-size: clamp(34px, 5.5vw, 72px); line-height: 1.08; letter-spacing: -.045em; max-width: 850px; margin: 20px 0 24px; }\n.moje-stranka__obsah > p:not(.moje-stranka__stitky) { max-width: 600px; font-size: 19px; line-height: 1.7; color: #48576a; }\n.moje-stranka__tlacitko { display: inline-flex; gap: 20px; align-items: center; margin-top: 16px; padding: 15px 22px; color: #fff; background: #1d4ed8; border-radius: 8px; text-decoration: none; font-weight: 650; }\n.moje-stranka__tlacitko:hover { background: #1e40af; }\n.moje-stranka__tlacitko:focus-visible { outline: 3px solid #111827; outline-offset: 4px; }\n.moje-stranka__karty { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; margin-top: 64px; }\n.moje-stranka article { background: #fff; padding: 28px; border: 1px solid #e2e8f0; border-radius: 14px; }\n.moje-stranka article > span { color: #5b6b82; font-size: 13px; }\n.moje-stranka h2 { font-size: 22px; line-height: 1.3; margin: 24px 0 12px; }\n.moje-stranka article p { color: #48576a; line-height: 1.65; margin-bottom: 0; }\n@media (max-width: 700px) { .moje-stranka__karty { grid-template-columns: 1fr; margin-top: 40px; } }',
        js: ''
      };
      Object.keys(sample).forEach(function (key) {
        if (codeEditors[key]) codeEditors[key].setValue(sample[key]);
        else { fields[key].value = sample[key]; fields[key].dispatchEvent(new Event('input', { bubbles: true })); }
      });
      var enabledField = document.getElementById('aiwp-enabled');
      enabledField.checked = true;
      enabledField.dispatchEvent(new Event('change', { bubbles: true }));
      activateTab('html', false);
      actionStatus.textContent = 'Ukázka je vložená a použití vlastního kódu je zapnuté. ' + (part ? 'Odkazy nastavte přes Vzhled → Menu. ' : 'Upravte texty a odkazy. ') + 'Potom klikněte na Aktualizovat nebo Publikovat. Samotný náhled změny neukládá.';
    });

    function validationMessage(html, css, js) {
      if ([html, css, js].some(function (code) { return /<\?(?:php|=)?/i.test(code); })) return 'PHP do editoru nepatří. Nechte si od AI připravit HTML, CSS a JavaScript.';
      if (/<!doctype|<\s*\/?\s*(?:html|head|body|meta|title|link|base|script|style)\b/i.test(html)) return 'V HTML ponechte jen obsah stránky. CSS patří do záložky CSS a JavaScript do JS; odstraňte obalové značky dokumentu a metadata.';
      if ([css, js].some(function (code) { return /<\s*\/?\s*(?:style|script)\b/i.test(code); })) return 'Z CSS a JS odstraňte obalové značky style a script.';
      if ([html, css, js].some(function (code) { return /^\s*```/m.test(code); })) return 'Odstraňte značky ``` kolem kódu z odpovědi AI.';
      return '';
    }

    studio.querySelector('[data-aiwp-refresh]').addEventListener('click', function () {
      var html = value('html');
      var css = value('css');
      var js = value('js');
      var error = validationMessage(html, css, js);
      if (error) { previewStatus.textContent = error; return; }
      html = html.replace(/(\[?)\[aiwp_menu\s+location\s*=\s*(["'])(primary|footer)\2\s*\](\]?)/g, function (match, escapeOpen, quote, location, escapeClose) {
        if (escapeOpen && escapeClose) return match.slice(1, -1);
        var menu = config.menuPreviews && config.menuPreviews[location];
        var label = location === 'footer' ? 'Menu v patičce' : 'Hlavní menu';
        return escapeOpen + (menu || '<p class="aiwp-menu-preview-notice">Pro umístění „' + label + '“ není připravené menu. Vyberte menu s odkazy ve Vzhled → Menu, uložte ho a znovu načtěte tento editor.</p>') + escapeClose;
      });
      var settings = config.settings || {};
      var accent = /^#[a-f\d]{6}$/i.test(settings.accent) ? settings.accent : '#2563eb';
      var width = Math.max(640, Math.min(1920, parseInt(settings.width, 10) || 1200));
      var font = config.font ? config.font.stack : 'system-ui, sans-serif';
      var baseCss = ':root{--aiwp-accent:' + accent + ';--aiwp-width:' + width + 'px;--aiwp-font:' + font + ';}*{box-sizing:border-box}body{margin:0;font-family:var(--aiwp-font);color:#1b2838;line-height:1.6}img,video{max-width:100%;height:auto}';
      baseCss += '.aiwp-menu,.aiwp-menu .sub-menu{list-style:none;margin:0;padding:0}.aiwp-menu{display:flex;flex-wrap:wrap;align-items:flex-start;gap:12px 24px}.aiwp-menu li{margin:0}.aiwp-menu a{display:inline-block}.aiwp-menu .sub-menu{display:grid;gap:4px;margin-top:6px;padding-inline-start:16px}@media(max-width:720px){.aiwp-menu{flex-direction:column}}.aiwp-menu-preview-notice{padding:12px;border:1px solid #e0c574;background:#fff8e6;color:#6b4700;font-size:14px}';
      var csp = "default-src 'none'; img-src https: http: data: blob:; media-src https: http:; font-src https: http: data:; style-src 'unsafe-inline' https: http:; script-src 'unsafe-inline'; connect-src 'none'; form-action 'none'; base-uri 'none';";
      var previewCss = baseCss + '\n' + (config.globalCss || settings.css || '') + '\n' + css;
      var fontLink = config.font && config.font.url ? '<link rel="stylesheet" href="' + config.font.url.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '">' : '';
      var wrapper = studio.dataset.aiwpKind === 'page' ? 'aiwp-content' : (studio.dataset.aiwpKind === 'footer' ? 'aiwp-footer' : 'aiwp-header');
      // srcdoc stays in an opaque sandbox origin; never insert user content into the admin DOM.
      frame.srcdoc = '<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="Content-Security-Policy" content="' + csp + '"><title>Náhled obsahu</title>' + fontLink + '<style>' + previewCss.replace(/<\/style/gi, '<\\/style') + '</style></head><body><div class="' + wrapper + '">' + html + '</div><script>' + js.replace(/<\/script/gi, '<\\/script') + '<\/script></body></html>';
      frame.hidden = false;
      studio.querySelector('[data-aiwp-preview-empty]').hidden = true;
      previewRendered = true;
      previewStatus.textContent = document.getElementById('aiwp-enabled').checked ? 'Náhled byl obnoven. Pro zobrazení změn na webu stránku uložte.' : 'Náhled byl obnoven, ale použití vlastního kódu je vypnuté. Zapněte ho a stránku uložte.';
    });

    studio.querySelectorAll('[data-aiwp-device]').forEach(function (button) {
      button.addEventListener('click', function () {
        studio.querySelector('[data-aiwp-preview-stage]').classList.toggle('is-mobile', button.dataset.aiwpDevice === 'mobile');
        studio.querySelectorAll('[data-aiwp-device]').forEach(function (item) {
          var active = item === button;
          item.classList.toggle('is-active', active);
          item.setAttribute('aria-pressed', String(active));
        });
      });
    });

    document.querySelectorAll('[data-aiwp-counter]').forEach(function (counter) {
      var input = document.getElementById(counter.dataset.aiwpCounter);
      if (!input) return;
      function update() { counter.textContent = String(Array.from(input.value).length); }
      input.addEventListener('input', update);
      update();
    });

    var mediaButton = document.querySelector('[data-aiwp-media]');
    var mediaFrame;
    if (mediaButton) mediaButton.addEventListener('click', function () {
      if (!window.wp || !window.wp.media) return;
      if (!mediaFrame) {
        mediaFrame = window.wp.media({ title: 'Vybrat obrázek pro sdílení', button: { text: 'Použít tento obrázek' }, library: { type: 'image' }, multiple: false });
        mediaFrame.on('select', function () {
          var selection = mediaFrame.state().get('selection').first();
          if (!selection) return;
          var input = document.getElementById('aiwp-seo-image');
          input.value = selection.toJSON().url || '';
          input.dispatchEvent(new Event('change', { bubbles: true }));
        });
      }
      mediaFrame.open();
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
  else ready();
}());
