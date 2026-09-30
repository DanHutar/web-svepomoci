# Languages / Jazyky

Open **ByYourself → Language**. Without the companion plugin, use **Appearance → Language**. The same preferences apply to both components:

- **Plugin and theme interface**: labels, help, editor messages and generated prompt instructions.
- **Public labels and cookie controls**: built-in visitor-facing labels. External consent plugins have their own settings.
- **Requested AI content language**: the language that generated prompts ask the AI to use for new visitor-facing content.

New installations default to English. Previously configured installations keep Czech during the upgrade. You can change each setting to English or Czech independently. Clear page and CDN caches after changing public labels.

Saved HTML, CSS, JavaScript, page titles, menus and custom service descriptions are never automatically translated. These settings do not change the WordPress dashboard language or create a multilingual website with translated URLs. WordPress core screens and third-party plugins retain their own language settings.

Both installation ZIPs contain text prompts in `prompty/en/` and `prompty/cs/`. Extract a copy of the ZIP to read them; upload the original ZIP to install the component. Fill in the real website details and the requested content language before using a prompt. The legal-document prompts assume a Czech/EU context even when written in English.

## Česky

Otevřete **ByYourself → Jazyk**. Bez aktivního pluginu najdete nastavení pod **Vzhled → Jazyk**. Volba platí společně pro plugin i šablonu:

- **Rozhraní pluginu a šablony**: popisky, nápověda, zprávy editoru a pokyny v generovaných promptech.
- **Veřejné popisky a ovládání cookies**: vlastní texty pro návštěvníky. Externí plugin pro cookies nastavujte samostatně.
- **Požadovaný jazyk obsahu od AI**: jazyk, ve kterém má AI vytvořit nový obsah pro návštěvníky.

Nová instalace začíná anglicky. Již nastavený web si při aktualizaci zachová češtinu. Všechny tři volby lze přepínat nezávisle. Po změně veřejných popisků vymažte cache webu a CDN.

Uložené HTML, CSS, JavaScript, názvy stránek, menu ani vlastní popisy služeb se automaticky nepřekládají. Nastavení nemění jazyk samotného WordPressu a nevytváří vícejazyčný web s přeloženými adresami. Ostatní pluginy mají vlastní jazyková nastavení.

Textové prompty najdete v obou instalačních ZIPech ve složkách `prompty/en/` a `prompty/cs/`. Před použitím doplňte skutečné údaje a požadovaný jazyk obsahu. Právní prompty i v angličtině předpokládají české/EU prostředí.

## Development

One implementation uses English gettext source strings. Czech translations are maintained in `translations/catalog.json` and `translations/additions.json`. After changing labels, run `npm run build:translations` and commit the generated MO/PO and JavaScript JSON catalogs. The build also synchronizes the shared language helper in the theme. Run `npm run test:languages` to check both languages, independent public labels, migration and the standalone theme. WordPress stores preferences in the shared `wsp_languages` option; do not delete it during updates.
