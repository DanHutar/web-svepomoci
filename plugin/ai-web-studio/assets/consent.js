(function () {
    'use strict';
    function init() {
        var config = window.aiwpConsent;
        var panel = document.getElementById('aiwp-consent-panel');
        var openers = document.querySelectorAll('[data-aiwp-consent-open]');
        var opener = openers[0];
        if (!config || !panel || !opener) return;
        var keys = Object.keys(config.categories);
        var loaded = {};
        var lifetime = 180 * 86400;
        var details = document.getElementById('aiwp-consent-details');
        var status = panel.querySelector('[data-aiwp-consent-status]');
        var timer;
        function read() {
            try {
                var item = document.cookie.split('; ').find(function (part) { return part.indexOf(config.name + '=') === 0; });
                var value = JSON.parse(decodeURIComponent(item ? item.slice(config.name.length + 1) : ''));
                var now = Math.floor(Date.now() / 1000);
                return value.v === config.version && Number.isInteger(value.t) && value.t <= now + 60 && value.t > now - lifetime && keys.every(function (key) { return typeof value[key] === 'boolean'; }) ? value : null;
            } catch (error) { return null; }
        }
        function allowed(record, key) { return !!(record && record[key] && config.categories[key].active); }
        function matches(name, pattern) { return pattern.endsWith('*') ? name.indexOf(pattern.slice(0, -1)) === 0 : name === pattern; }
        function cleanup(record) {
            var paths = new Set(['/', config.path]);
            var parts = location.pathname.split('/');
            while (parts.length) { paths.add(parts.join('/') || '/'); paths.add((parts.join('/') || '') + '/'); parts.pop(); }
            var domains = [''];
            var host = location.hostname.split('.');
            while (host.length > 1) { domains.push(host.join('.')); host.shift(); }
            keys.filter(function (key) { return !allowed(record, key); }).forEach(function (key) {
                var category = config.categories[key];
                document.cookie.split(';').forEach(function (pair) {
                    var name = pair.trim().split('=')[0];
                    if (/^(aiwp|wordpress|wp-settings|PHPSESSID)/i.test(name)) return;
                    if (!category.cookies.some(function (pattern) { return matches(name, pattern); })) return;
                    if (keys.some(function (other) { return allowed(record, other) && config.categories[other].cookies.some(function (pattern) { return matches(name, pattern); }); })) return;
                    paths.forEach(function (path) { domains.forEach(function (domain) {
                        document.cookie = name + '=; Max-Age=0; Path=' + path + (domain ? '; Domain=' + domain : '') + '; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
                    }); });
                });
                category.storage.forEach(function (name) {
                    if (/^(aiwp|wordpress|wp-settings|PHPSESSID)/i.test(name)) return;
                    if (keys.some(function (other) { return allowed(record, other) && config.categories[other].storage.indexOf(name) !== -1; })) return;
                    try { localStorage.removeItem(name); } catch (error) { /* Storage may be disabled. */ }
                    try { sessionStorage.removeItem(name); } catch (error) { /* Storage may be disabled. */ }
                });
            });
        }
        function apply(record) {
            cleanup(record);
            if (keys.some(function (key) { return loaded[key] && !allowed(record, key); })) { location.reload(); return; }
            keys.forEach(function (key) {
                var checkbox = panel.querySelector('[data-aiwp-consent-category="' + key + '"]');
                if (checkbox) checkbox.checked = allowed(record, key);
                if (!allowed(record, key) || loaded[key]) return;
                loaded[key] = true;
                var script = document.createElement('script');
                script.src = config.categories[key].url;
                script.async = false;
                document.head.appendChild(script);
            });
            clearTimeout(timer);
            if (record) timer = setTimeout(sync, Math.min(2147483647, Math.max(1000, (record.t + lifetime) * 1000 - Date.now())));
        }
        function show(advanced) {
            panel.hidden = false;
            openers.forEach(function (control) { control.setAttribute('aria-expanded', 'true'); });
            details.hidden = !advanced;
            var button = panel.querySelector('[data-aiwp-consent-action="settings"]');
            if (button) button.setAttribute('aria-expanded', String(advanced));
        }
        function close() {
            panel.hidden = true;
            openers.forEach(function (control) { control.setAttribute('aria-expanded', 'false'); });
            opener.focus({ preventScroll: true });
        }
        function sync() {
            var record = read();
            apply(record);
            if (!record && keys.some(function (key) { return config.categories[key].active; })) show(false);
        }
        function save(action) {
            var record = { v: config.version, t: Math.floor(Date.now() / 1000) };
            keys.forEach(function (key) {
                var input = panel.querySelector('[data-aiwp-consent-category="' + key + '"]');
                record[key] = !!(config.categories[key].active && (action === 'accept' || (action === 'save' && input && input.checked)));
            });
            document.cookie = config.name + '=' + encodeURIComponent(JSON.stringify(record)) + '; Path=' + config.path + '; Max-Age=' + lifetime + '; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
            var stored = read();
            if (!stored || JSON.stringify(stored) !== JSON.stringify(record)) {
                status.textContent = wp.i18n.__("Your choice could not be saved. Allow necessary cookies in your browser; optional services will remain off.", "ai-web-studio");
                return;
            }
            try { localStorage.setItem(config.name + '_sync', JSON.stringify(record) + Date.now()); } catch (error) { /* Focus also synchronizes tabs. */ }
            close();
            apply(stored);
        }
        openers.forEach(function (control) {
            control.hidden = false;
            control.addEventListener('click', function () { opener = control; apply(read()); show(true); panel.querySelector('button').focus(); });
        });
        panel.addEventListener('click', function (event) {
            var button = event.target.closest('[data-aiwp-consent-action]');
            if (!button) return;
            var action = button.getAttribute('data-aiwp-consent-action');
            if (action === 'close') close();
            else if (action === 'settings') show(details.hidden);
            else save(action);
        });
        panel.addEventListener('keydown', function (event) { if (event.key === 'Escape') close(); });
        window.addEventListener('storage', function (event) { if (event.key === config.name + '_sync') sync(); });
        window.addEventListener('focus', sync);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) sync(); });
        sync();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
