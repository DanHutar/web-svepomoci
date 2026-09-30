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
      if (previewRendered) previewStatus.textContent = wp.i18n.__("Content has changed. Refresh the preview.", "ai-web-studio");
      studio.querySelector('[data-aiwp-code-status]').textContent = wp.i18n.__("Save your changes in WordPress.", "ai-web-studio");
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
        status.textContent = enabled ? wp.i18n.__("Custom code is enabled. Save or publish to apply your changes to the website.", "ai-web-studio") : wp.i18n.__("Custom code is disabled. You can preview it, but it will not appear on the website until you enable it and save.", "ai-web-studio");
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
      // Consent is managed globally, never by generated page fragments.
      var part = studio.dataset.aiwpKind !== 'page';
      var location = studio.dataset.aiwpKind === 'footer' ? 'footer' : 'primary';
      return [
        config.contentLanguageInstruction || '',
        wp.i18n.__("Do not add analytics, marketing scripts or a cookie banner to the page. Configure optional services in the selected consent manager: Website Builder → Privacy and cookies, or an external manager such as Complianz. Do not duplicate tracking code between managers.", "ai-web-studio"),
        wp.i18n.__("Create ", "ai-web-studio") + (part ? (location === 'footer' ? wp.i18n.__("a shared footer", "ai-web-studio") : wp.i18n.__("a shared header", "ai-web-studio")) : wp.i18n.__("page content", "ai-web-studio")) + wp.i18n.__(" for WordPress with the web-svepomoci-sablona theme and web-svepomoci-plugin plugin.", "ai-web-studio"),
        wp.i18n.__("My brief: [ADD the purpose, copy, colours, target audience and required sections].", "ai-web-studio"),
        wp.i18n.__("Return exactly three separate sections labelled HTML, CSS and JS, which I will copy into separate fields.", "ai-web-studio"),
        wp.i18n.__("HTML must be a content fragment: no doctype, html, head, body, style, script, PHP, on* attributes or javascript: URLs. ", "ai-web-studio") + (part ? wp.i18n.__("Return only the requested header or footer.", "ai-web-studio") : wp.i18n.__("I manage the header and footer separately; do not create them. Start with one main H1 heading; do not add another main element.", "ai-web-studio")),
        wp.i18n.__("Return CSS without style tags. Scope every selector to the fragment's unique root class; do not use global body, h1, button, etc. The variables --aiwp-accent, --aiwp-width and --aiwp-font are available.", "ai-web-studio"),
        config.designContext || '',
        wp.i18n.__("I manage navigation in Appearance → Menus. ", "ai-web-studio") + (part ? wp.i18n.__("Inside nav, insert exactly [aiwp_menu location=\"", "ai-web-studio") + location + '"].' : wp.i18n.__("If navigation is needed within the content, use [aiwp_menu location=\"primary\"] or [aiwp_menu location=\"footer\"].", "ai-web-studio")) + wp.i18n.__(" This marker produces ul.aiwp-menu with li.menu-item and a links, with ul.sub-menu for nested navigation. Adapt CSS to this structure and include accessible submenus. Do not write navigation links manually. [aiwp_cookie_settings] is also supported for footer cookie controls (button.aiwp-cookie-settings); it produces no output in external mode. Other shortcodes are not supported.", "ai-web-studio"),
        wp.i18n.__("JS is optional plain JavaScript without script tags, libraries or external scripts. Scope selectors to the fragment root, wrap the code in an IIFE and assume the DOM is loaded. Do not use PHP or call WordPress functions.", "ai-web-studio"),
        wp.i18n.__("The design must be responsive, readable and keyboard accessible. Provide sufficient contrast, control labels and support for prefers-reduced-motion.", "ai-web-studio"),
        wp.i18n.__("Use the real image URLs supplied; if none are available, design without photographs. Do not invent functional forms, payment gateways or email delivery.", "ai-web-studio"),
        wp.i18n.__("Write the copy in the requested content language and mark where I need to add my own details. Include a proposed SEO title and meta description outside the code blocks.", "ai-web-studio")
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
        actionStatus.textContent = wp.i18n.__("Copy the selected prompt using Ctrl+C (Cmd+C on Mac).", "ai-web-studio");
      }
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(prompt).then(function () {
          actionStatus.textContent = wp.i18n.__("Prompt copied. Paste it into your AI and add your requirements.", "ai-web-studio");
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
      actionStatus.textContent = wp.i18n.__("The menu marker has been inserted into HTML. Place it inside nav instead of the existing links. Enable custom code and save using Update.", "ai-web-studio");
    });

    studio.querySelector('[data-aiwp-sample]').addEventListener('click', function () {
      if (['html', 'css', 'js'].some(function (key) { return value(key).trim(); })) {
        if (!window.confirm(wp.i18n.__("The sample will replace the current HTML, CSS and JavaScript in the editor. Continue? It will only be saved to the website when you click Update or Publish.", "ai-web-studio"))) return;
      }
      var part = studio.dataset.aiwpKind !== 'page';
      var location = studio.dataset.aiwpKind === 'footer' ? 'footer' : 'primary';
      var sample = {
        html: part ? wp.i18n.__("<div class=\"moje-cast\">\n  <a class=\"moje-cast__brand\" href=\"/\">Your website name</a>\n  <nav aria-label=\"Website navigation\">[aiwp_menu location=\"", "ai-web-studio") + location + '"]</nav>\n</div>' : wp.i18n.__("<section class=\"moje-stranka\">\n  <div class=\"moje-stranka__obsah\">\n    <p class=\"moje-stranka__stitky\">YOUR NEW BEGINNING</p>\n    <h1>Great ideas start with a first page.</h1>\n    <p>Tell visitors what you do and how you can help them. Replace this text with your own story.</p>\n    <a class=\"moje-stranka__tlacitko\" href=\"#vice\">Learn more <span aria-hidden=\"true\">↗</span></a>\n  </div>\n  <div id=\"vice\" class=\"moje-stranka__karty\">\n    <article><span>01</span><h2>Your story</h2><p>What led you to what you do today?</p></article>\n    <article><span>02</span><h2>Your services</h2><p>Describe the specific help you offer.</p></article>\n    <article><span>03</span><h2>The next step</h2><p>Tell visitors how to get in touch.</p></article>\n  </div>\n</section>", "ai-web-studio"),
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
      actionStatus.textContent = wp.i18n.__("The sample has been inserted and custom code is enabled. ", "ai-web-studio") + (part ? wp.i18n.__("Configure navigation in Appearance → Menus. ", "ai-web-studio") : 'Upravte texty a odkazy. ') + wp.i18n.__("Then click Update or Publish. Previewing alone does not save changes.", "ai-web-studio");
    });

    function validationMessage(html, css, js) {
      if ([html, css, js].some(function (code) { return /<\?(?:php|=)?/i.test(code); })) return wp.i18n.__("PHP is not supported in the editor. Ask your AI for HTML, CSS and JavaScript.", "ai-web-studio");
      if (/<!doctype|<\s*\/?\s*(?:html|head|body|meta|title|link|base|script|style)\b/i.test(html)) return wp.i18n.__("Keep only page content in HTML. Put CSS in the CSS tab and JavaScript in JS; remove document wrappers and metadata.", "ai-web-studio");
      if ([css, js].some(function (code) { return /<\s*\/?\s*(?:style|script)\b/i.test(code); })) return wp.i18n.__("Remove the style and script wrapper tags from CSS and JS.", "ai-web-studio");
      if ([html, css, js].some(function (code) { return /^\s*```/m.test(code); })) return wp.i18n.__("Remove the ``` fences around the AI-generated code.", "ai-web-studio");
      return '';
    }

    studio.querySelector('[data-aiwp-refresh]').addEventListener('click', function () {
      var html = value('html');
      var css = value('css');
      var js = value('js');
      var error = validationMessage(html, css, js);
      if (error) { previewStatus.textContent = error; return; }
      html = html.replace(/(\[?)\[aiwp_cookie_settings\s*\](\]?)/g, function (match, escapeOpen, escapeClose) {
        if (escapeOpen && escapeClose) return match.slice(1, -1);
        return escapeOpen + (config.consentExternal ? '' : wp.i18n.__("<button type=\"button\" class=\"aiwp-cookie-settings\" disabled title=\"Test consent controls on the website\">Cookie settings</button>", "ai-web-studio")) + escapeClose;
      });
      html = html.replace(/(\[?)\[aiwp_menu\s+location\s*=\s*(["'])(primary|footer)\2\s*\](\]?)/g, function (match, escapeOpen, quote, location, escapeClose) {
        if (escapeOpen && escapeClose) return match.slice(1, -1);
        var menu = config.menuPreviews && config.menuPreviews[location];
        var label = location === 'footer' ? wp.i18n.__("Footer menu", "ai-web-studio") : wp.i18n.__("Primary menu", "ai-web-studio");
        return escapeOpen + (menu || wp.i18n.__("<p class=\"aiwp-menu-preview-notice\">No menu is assigned to “", "ai-web-studio") + label + wp.i18n.__("”. Assign a menu with links in Appearance → Menus, save it and reload this editor.</p>", "ai-web-studio")) + escapeClose;
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
      frame.srcdoc = '<!doctype html><html lang="' + (config.contentLanguage === 'cs' ? 'cs' : 'en') + '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="Content-Security-Policy" content="' + csp + wp.i18n.__("\"><title>Content preview</title>", "ai-web-studio") + fontLink + '<style>' + previewCss.replace(/<\/style/gi, '<\\/style') + '</style></head><body><div class="' + wrapper + '">' + html + '</div><script>' + js.replace(/<\/script/gi, '<\\/script') + '<\/script></body></html>';
      frame.hidden = false;
      studio.querySelector('[data-aiwp-preview-empty]').hidden = true;
      previewRendered = true;
      previewStatus.textContent = document.getElementById('aiwp-enabled').checked ? wp.i18n.__("Preview refreshed. Save the page to apply changes to the website.", "ai-web-studio") : wp.i18n.__("Preview refreshed, but custom code is disabled. Enable it and save the page.", "ai-web-studio");
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
      if (!input || typeof input.value !== 'string') return;
      function update() { counter.textContent = String(Array.from(input.value).length); }
      input.addEventListener('input', update);
      update();
    });

    var mediaButton = document.querySelector('[data-aiwp-media]');
    var mediaStatus = document.querySelector('[data-aiwp-media-status]');
    var mediaFrame;
    if (mediaButton) mediaButton.addEventListener('click', function () {
      if (mediaStatus) { mediaStatus.hidden = true; mediaStatus.textContent = ''; }
      if (!window.wp || typeof window.wp.media !== 'function') {
        if (mediaStatus) {
          mediaStatus.textContent = wp.i18n.__("The media library did not load. Reload the page or paste the image URL directly into the field.", "ai-web-studio");
          mediaStatus.hidden = false;
        }
        return;
      }
      if (!mediaFrame) {
        mediaFrame = window.wp.media({ title: wp.i18n.__("Choose a sharing image", "ai-web-studio"), button: { text: wp.i18n.__("Use this image", "ai-web-studio") }, library: { type: 'image' }, multiple: false });
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
