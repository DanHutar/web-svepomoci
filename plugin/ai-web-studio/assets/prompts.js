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
        style: 'AI navrhne společné CSS včetně velikostí písma a výšek řádků pomocí clamp() pro H1–H6, běžný text a small. Výsledek vložíte do Vzhled webu → Společné CSS.',
        page: 'AI připraví novou stránku podle uloženého společného vzhledu. Ve WordPressu pak vytvoříte stránku a její HTML, CSS a JavaScript vložíte do odpovídajících polí AI editoru.',
        header: 'AI připraví hlavičku podle společného vzhledu a uloženého kódu Headeru. Výsledek vložíte do HTML, CSS a JavaScriptu v položce Header. Odkazy se spravují ve Vzhled → Menu.',
        footer: 'AI připraví patičku podle společného vzhledu a uloženého kódu Footeru. Výsledek vložíte do HTML, CSS a JavaScriptu v položce Footer. Odkazy se spravují ve Vzhled → Menu.',
        edit: 'Vyberte stránku a popište změnu. Zadání zahrne její současný uložený kód. Odpověď od AI vložíte zpět do HTML, CSS a JavaScriptu této stránky; před uložením zkontrolujete náhled.'
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
            throw new Error('WordPress nevrátil odpověď. Obnovte stránku a zkuste to znovu.');
        }
        if (!response.ok || !payload || payload.success !== true) {
            const message = payload && payload.data && payload.data.message;
            throw new Error(typeof message === 'string' ? message : 'Požadavek se nepodařilo dokončit. Obnovte stránku a zkuste to znovu.');
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
        pageSelect.replaceChildren(new Option('Vyberte stránku', ''));
        pagesStatus.textContent = 'Načítám stránky…';
        updateGenerate();
        try {
            const data = await request('aiwp_prompt_pages', { search: pageSearch.value.trim() }, searchController.signal);
            if (thisSearch !== searchRevision || kind.value !== 'edit') return;
            if (!data || !Array.isArray(data.pages)) throw new Error('Seznam stránek se nepodařilo načíst. Zkuste vyhledávání znovu.');
            const pages = data.pages.filter(page => Number.isInteger(Number(page.id)) && Number(page.id) > 0 && typeof page.title === 'string');
            pages.forEach(page => pageSelect.add(new Option(page.title, String(page.id))));
            pageSelect.disabled = pages.length === 0;
            pagesStatus.textContent = pages.length === 0 ? 'Žádná dostupná stránka neodpovídá hledání. Zkuste jiný název.' :
                data.more ? 'Zobrazuje se prvních 20 stránek. Pro další stránky zpřesněte hledání podle názvu.' : 'Vyberte stránku ze seznamu. Její kód přidáme až při přípravě zadání.';
        } catch (error) {
            if (thisSearch !== searchRevision || error.name === 'AbortError') return;
            pagesStatus.textContent = error.message || 'Stránky se nepodařilo načíst. Zkuste to znovu.';
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
        pageSelect.replaceChildren(new Option('Nejprve vyhledejte stránku', ''));
        pageSelect.disabled = true;
        pagesStatus.textContent = 'Klikněte na Vyhledat stránky a potom vyberte stránku ze seznamu.';
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
        setStatus('Připravuji zadání z uložených údajů…');
        try {
            const data = await request('aiwp_build_prompt', fields, buildController.signal);
            if (thisRevision !== revision) return;
            if (!data || typeof data.prompt !== 'string' || !data.prompt || !data.destination) {
                throw new Error('Zadání se nepodařilo připravit. Zkuste to znovu.');
            }
            const destination = new URL(data.destination.url, window.location.href);
            if (destination.origin !== window.location.origin || !['http:', 'https:'].includes(destination.protocol)) {
                throw new Error('Odkaz do editoru se nepodařilo připravit. Obnovte stránku a zkuste to znovu.');
            }
            output.value = data.prompt;
            destinationLink.href = destination.href;
            destinationLink.textContent = typeof data.destination.label === 'string' ? data.destination.label : 'Otevřít editor';
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
            setStatus('Zadání je připravené níže. Prohlédněte si ho a klikněte na Zkopírovat zadání.');
        } catch (error) {
            if (thisRevision !== revision || error.name === 'AbortError') return;
            setStatus(error.message || 'Zadání se nepodařilo připravit. Zkuste to znovu.', true);
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
            if (thisRevision === revision) copyStatus.textContent = 'Zadání je zkopírované. Vložte ho do chatu s AI.';
        } catch (error) {
            if (thisRevision !== revision) return;
            output.focus();
            output.select();
            copyStatus.textContent = 'Automatické kopírování není dostupné. Zadání je označené; stiskněte Ctrl+C (na Macu ⌘C).';
        } finally {
            if (thisRevision === revision) copy.disabled = false;
        }
    });

    updateGenerate();
})();
