(() => {
    'use strict';

    const form = document.getElementById('aiwp-prompts-form');
    const config = window.aiwpPrompts;
    if (!form || !config) return;

    const kind = document.getElementById('aiwp-prompt-kind');
    const kindHelp = document.getElementById('aiwp-prompt-kind-help');
    const instructions = document.getElementById('aiwp-prompt-instructions');
    const pagePicker = document.getElementById('aiwp-prompt-page-picker');
    const pageSearch = document.getElementById('aiwp-prompt-page-search');
    const searchButton = document.getElementById('aiwp-prompt-page-search-button');
    const pageSelect = document.getElementById('aiwp-prompt-page-id');
    const pagesStatus = document.getElementById('aiwp-prompt-pages-status');
    const generate = document.getElementById('aiwp-prompt-generate');
    const status = document.getElementById('aiwp-prompt-status');
    const result = document.getElementById('aiwp-prompt-result');
    const output = document.getElementById('aiwp-prompt-output');
    const copy = document.getElementById('aiwp-prompt-copy');
    const copyStatus = document.getElementById('aiwp-prompt-copy-status');
    const notices = document.getElementById('aiwp-prompt-notices');
    const destinationLink = document.getElementById('aiwp-prompt-destination-link');
    const destinationHelp = document.getElementById('aiwp-prompt-destination-help');
    const explanations = {
        style: wp.i18n.__("AI will propose shared CSS, including clamp() font sizes and line heights for H1–H6, body text and small. Paste the result into Website design → Shared CSS.", "ai-web-studio"),
        page: wp.i18n.__("AI will prepare a new page using your saved shared design. Create a WordPress page and paste its HTML, CSS and JavaScript into the corresponding AI editor fields.", "ai-web-studio"),
        header: wp.i18n.__("AI will prepare a header using the shared design and saved Header code. Paste the result into the HTML, CSS and JavaScript fields under Header. Manage links in Appearance → Menus.", "ai-web-studio"),
        footer: wp.i18n.__("AI will prepare a footer using the shared design and saved Footer code. Paste the result into the HTML, CSS and JavaScript fields under Footer. Manage links in Appearance → Menus.", "ai-web-studio"),
        edit: wp.i18n.__("Select a page and describe the change. The prompt includes its currently saved code. Paste the AI response back into that page's HTML, CSS and JavaScript fields; check the preview before saving.", "ai-web-studio")
    };
    let revision = 0;
    let generating = false;
    let buildController = null;
    let searchController = null;
    let searchRevision = 0;
    let searching = false;

    function updateGenerate() {
        generate.disabled = generating || !instructions.value.trim() || instructions.value.length > 10000 ||
            (kind.value === 'edit' && (searching || !pageSelect.value));
    }

    function setStatus(message, error = false) {
        status.textContent = message;
        status.classList.toggle('is-error', error);
    }

    function invalidate() {
        revision += 1;
        if (buildController) buildController.abort();
        buildController = null;
        generating = false;
        generate.removeAttribute('aria-busy');
        result.hidden = true;
        output.value = '';
        copy.disabled = true;
        copyStatus.textContent = '';
        notices.replaceChildren();
        notices.hidden = true;
        destinationLink.removeAttribute('href');
        destinationHelp.textContent = '';
        setStatus('');
        updateGenerate();
    }

    function cancelSearch() {
        searchRevision += 1;
        if (searchController) searchController.abort();
        searchController = null;
        searching = false;
        searchButton.disabled = false;
    }

    async function request(action, fields, signal) {
        const response = await fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            signal,
            body: new URLSearchParams({ action, nonce: config.nonce, ...fields })
        });
        let payload;
        try {
            payload = await response.json();
        } catch (error) {
            throw new Error(wp.i18n.__("WordPress did not return a response. Reload the page and try again.", "ai-web-studio"));
        }
        if (!response.ok || !payload || payload.success !== true) {
            const message = payload && payload.data && payload.data.message;
            throw new Error(typeof message === 'string' ? message : wp.i18n.__("The request could not be completed. Reload the page and try again.", "ai-web-studio"));
        }
        return payload.data;
    }

    async function findPages() {
        if (kind.value !== 'edit') return;
        invalidate();
        cancelSearch();
        const thisSearch = searchRevision;
        searchController = new AbortController();
        searching = true;
        searchButton.disabled = true;
        pageSelect.disabled = true;
        pageSelect.replaceChildren(new Option(wp.i18n.__("Select a page", "ai-web-studio"), ''));
        pagesStatus.textContent = wp.i18n.__("Loading pages…", "ai-web-studio");
        updateGenerate();
        try {
            const data = await request('aiwp_prompt_pages', { search: pageSearch.value.trim() }, searchController.signal);
            if (thisSearch !== searchRevision || kind.value !== 'edit') return;
            if (!data || !Array.isArray(data.pages)) throw new Error(wp.i18n.__("The page list could not be loaded. Try searching again.", "ai-web-studio"));
            const pages = data.pages.filter(page => Number.isInteger(Number(page.id)) && Number(page.id) > 0 && typeof page.title === 'string');
            pages.forEach(page => pageSelect.add(new Option(page.title, String(page.id))));
            pageSelect.disabled = pages.length === 0;
            pagesStatus.textContent = pages.length === 0 ? wp.i18n.__("No available pages match your search. Try a different title.", "ai-web-studio") :
                data.more ? wp.i18n.__("Showing the first 20 pages. Refine your title search to find other pages.", "ai-web-studio") : wp.i18n.__("Select a page from the list. Its code will only be added when you generate the prompt.", "ai-web-studio");
        } catch (error) {
            if (thisSearch !== searchRevision || error.name === 'AbortError') return;
            pagesStatus.textContent = error.message || wp.i18n.__("Pages could not be loaded. Try again.", "ai-web-studio");
        } finally {
            if (thisSearch === searchRevision) {
                searching = false;
                searchController = null;
                searchButton.disabled = false;
                updateGenerate();
            }
        }
    }

    kind.addEventListener('change', () => {
        cancelSearch();
        invalidate();
        pagePicker.hidden = kind.value !== 'edit';
        kindHelp.textContent = explanations[kind.value] || '';
        if (kind.value === 'edit') findPages();
    });
    instructions.addEventListener('input', invalidate);
    pageSelect.addEventListener('change', invalidate);
    pageSearch.addEventListener('input', () => {
        cancelSearch();
        pageSelect.replaceChildren(new Option(wp.i18n.__("Search for a page first", "ai-web-studio"), ''));
        pageSelect.disabled = true;
        pagesStatus.textContent = wp.i18n.__("Click Search pages, then select a page from the list.", "ai-web-studio");
        invalidate();
    });
    searchButton.addEventListener('click', findPages);
    pageSearch.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            findPages();
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        updateGenerate();
        if (generate.disabled) return;
        invalidate();
        const thisRevision = revision;
        const fields = { kind: kind.value, instructions: instructions.value };
        if (kind.value === 'edit') fields.page_id = pageSelect.value;
        buildController = new AbortController();
        generating = true;
        generate.setAttribute('aria-busy', 'true');
        updateGenerate();
        setStatus(wp.i18n.__("Preparing a prompt from saved information…", "ai-web-studio"));
        try {
            const data = await request('aiwp_build_prompt', fields, buildController.signal);
            if (thisRevision !== revision) return;
            if (!data || typeof data.prompt !== 'string' || !data.prompt || !data.destination) {
                throw new Error(wp.i18n.__("The prompt could not be prepared. Try again.", "ai-web-studio"));
            }
            const destination = new URL(data.destination.url, window.location.href);
            if (destination.origin !== window.location.origin || !['http:', 'https:'].includes(destination.protocol)) {
                throw new Error(wp.i18n.__("The editor link could not be prepared. Reload the page and try again.", "ai-web-studio"));
            }
            output.value = data.prompt;
            destinationLink.href = destination.href;
            destinationLink.textContent = typeof data.destination.label === 'string' ? data.destination.label : wp.i18n.__("Open editor", "ai-web-studio");
            destinationHelp.textContent = typeof data.destination.help === 'string' ? data.destination.help : '';
            if (Array.isArray(data.notices)) {
                data.notices.filter(notice => typeof notice === 'string' && notice).forEach(notice => {
                    const item = document.createElement('li');
                    item.textContent = notice;
                    notices.append(item);
                });
            }
            notices.hidden = !notices.childElementCount;
            result.hidden = false;
            output.scrollTop = 0;
            output.scrollLeft = 0;
            copy.disabled = false;
            setStatus(wp.i18n.__("Your prompt is ready below. Review it and click Copy prompt.", "ai-web-studio"));
        } catch (error) {
            if (thisRevision !== revision || error.name === 'AbortError') return;
            setStatus(error.message || wp.i18n.__("The prompt could not be prepared. Try again.", "ai-web-studio"), true);
        } finally {
            if (thisRevision === revision) {
                generating = false;
                buildController = null;
                generate.removeAttribute('aria-busy');
                updateGenerate();
            }
        }
    });

    copy.addEventListener('click', async () => {
        if (copy.disabled || !output.value) return;
        const thisRevision = revision;
        const prompt = output.value;
        copy.disabled = true;
        try {
            if (!navigator.clipboard || !navigator.clipboard.writeText) throw new Error('clipboard');
            await navigator.clipboard.writeText(prompt);
            if (thisRevision === revision) copyStatus.textContent = wp.i18n.__("Prompt copied. Paste it into your AI chat.", "ai-web-studio");
        } catch (error) {
            if (thisRevision !== revision) return;
            output.focus();
            output.select();
            copyStatus.textContent = wp.i18n.__("Automatic copying is unavailable. The prompt is selected; press Ctrl+C (⌘C on Mac).", "ai-web-studio");
        } finally {
            if (thisRevision === revision) copy.disabled = false;
        }
    });

    updateGenerate();
})();
