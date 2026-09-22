# Ověření první verze

Ověřeno lokálně 19. 9. 2026 v dočasné instalaci WordPress Playground: **WordPress 6.8.3, PHP 8.3, SQLite**, Google Chrome přes Playwright. Nejde o test na vašem hostingu s jeho pluginy a konfigurací.

Automatické ověření zahrnuje:

- PHP syntaxi všech souborů šablony a pluginu.
- Ukládání přes skutečné formuláře WordPressu, včetně češtiny, uvozovek a zpětných lomítek.
- Uložení všech AI polí do nativní revize a jejich obnovení.
- Ochranu úprav oprávněními a nonce, včetně pokusu obejít editor automatickým ukládáním revize.
- Odmítnutí PHP, celého HTML dokumentu, značek script/style ve špatných polích a markdownového ohraničení.
- Obyčejné stránky i stránky s vlastním HTML, společnou hlavičku/patičku, jejich skrytí a načítání příslušného CSS.
- Respektování ochrany stránky heslem.
- SEO titulek, meta popis, jeden kanonický odkaz a možnost přenechat SEO jinému pluginu.
- Přepínání editorů, izolovaný náhled a mobilní šířku.
- Veřejné zobrazení na počítači a mobilu bez vodorovného přetékání a bez nezachycených JavaScriptových chyb.
- Funkčnost běžného obsahu šablony i po deaktivaci pluginu.

Výsledek běhu je v `test-results/results.json`, snímky v `test-results/`. Testy lze zopakovat pomocí `npm ci` a `npm test` v kořeni projektu; vyžadují nainstalovaný Google Chrome. Instalační ZIPy žádné testovací nástroje neobsahují.

Před použitím konkrétního webu ověřte na jeho testovací kopii skutečný obsah, odkazy, obrázky a soužití s používanými pluginy. Integrace s uvedenými SEO pluginy vypíná vlastní SEO výstup při jejich rozpoznání; není to náhrada testu všech jejich verzí. Multisite, všechny verze WordPressu/PHP od deklarovaného minima a jiné prohlížeče nebyly v tomto běhu samostatně otestovány.

Při návrhu ukládání vycházíme z [revizovaných metadat WordPressu](https://developer.wordpress.org/reference/functions/register_meta/) a chování jeho [automatického ukládání metadat](https://developer.wordpress.org/reference/functions/wp_autosave_post_revisioned_meta_fields/). Vlastní kód se ukládá ručním uložením stránky; rychlý náhled umožňuje zkontrolovat dosud neuložené změny.

## Oprava 1.0.1: vložení ukázky

Tlačítko **Vložit ukázkový obsah** nyní také zaškrtne použití vlastního kódu. Stav je viditelně popsaný u editoru a náhled upozorňuje na nutnost uložení. Ve verzi 1.0.0 mohlo být HTML i CSS vidět v rychlém náhledu, ale vypnutý režim ponechal na veřejné stránce původní obsah.

Samostatný test `npm run test:sample` provádí vložení ukázky tlačítkem na původně běžné stránce, uložení formuláře a kontrolu veřejné stránky. Porovnává skutečnou barvu pozadí s náhledem a kontroluje tři sloupce na počítači a jeden sloupec na mobilu.

## Verze 1.1.0: menu z WordPressu

Značky `[aiwp_menu location="primary"]` a `[aiwp_menu location="footer"]` propojují AI HTML s menu přiřazeným ve **Vzhled → Menu**. Značky nemají spouštět jiné WordPress shortcody ani PHP. Menu v rychlém náhledu vychází ze stavu při otevření editoru; změna menu v jiném okně vyžaduje nové načtení editoru.

Ověřeno lokálně 20. 9. 2026 ve WordPressu 6.8.3 / PHP 8.3 a Google Chrome příkazem `npm run test:menu`:

- Vykreslení přiřazeného hlavního i patičkového menu v HTML a rychlém náhledu.
- Promítnutí změn menu do veřejné stránky bez opětovného ukládání HTML.
- Zachování podnabídek a označení aktuální stránky včetně atributu `aria-current`.
- Prázdný výstup bez přiřazeného menu a nespouštění ostatních shortcodů.
- Odmítnutí neplatných parametrů značky a zachování značky zapsané ve dvojitých hranatých závorkách jako textu.
- Vložení značky tlačítkem na pozici kurzoru bez změny okolního HTML, CSS a JavaScriptu.
- Zachování uložených dat, nastavení a přiřazených menu po deaktivaci a opětovné aktivaci pluginu.

Prošla také celá integrační sada `npm test`, včetně ukládání, revizí, oprávnění, SEO a zobrazení na mobilní šířce. Nahrazení staré verze instalačním ZIPem na skutečném hostingu tento běh netestoval.

## Verze 1.2.0: nové názvy a aktualizace z GitHubu

Ověřeno lokálně 21. 9. 2026 ve WordPressu 6.8.3 / PHP 8.3. Prošla celá integrační sada `npm test` s Google Chrome a nová sada `npm run test:updates`:

- Skutečné nahrazení obou instalačních ZIPů přes WordPress `Plugin_Upgrader::bulk_upgrade` (stejný postup jako běžné tlačítko aktualizace pluginu) a `Theme_Upgrader::upgrade`.
- Zachování metadat stránky, hlavičky a patičky, společných nastavení, aktivace pluginu i šablony a přiřazení menu.
- Nové zobrazené názvy a verze obou součástí po výměně souborů.
- Nabídka novější verze přes nativní aktualizační funkce WordPressu; správné samostatné ZIPy pro plugin a šablonu; dialog podrobností pluginu.
- Stejná ani starší verze se nenabízí jako aktualizace. Cizí pluginy a šablony zůstávají nedotčené.
- Odmítnutí konceptu, předběžné verze, neúplných příloh, cizího repozitáře, neplatného manifestu a výpadku sítě; společná cache pro obě součásti.
- Aktualizační kód funguje, pokud je aktivní samotná šablona nebo samotný plugin.

Test pracuje s dočasnými kopiemi balíčků. Původní instalaci simuluje ponecháním původních identifikátorů, názvů a verzí a vypnutím nového updateru; nejde o archiv všech historických souborů verze 1.0.0. Odpovědi GitHubu pro budoucí verze jsou v automatickém testu řízené testovací odpovědi. Nasazení na konkrétní hosting zůstává samostatným krokem.

## Verze 1.3.0: Google Fonts a společné prompty

Lokálně prošla integrační sada `npm test` ve WordPressu 6.8.3 / PHP 8.3 a Chrome, rozšířená o `tests/design-browser.mjs`:

- Přijetí všech osmi povolených Google Fontů a odmítnutí cizího názvu či URL; zachování původních systémových voleb bez externího požadavku.
- Uložení fontu Lora a společného CSS skutečným formulářem WordPressu a jejich opětovné načtení.
- Kopírování promptu podle aktuálního výběru a CSS, včetně náhradního ručního kopírování při odmítnutí schránky.
- Požadavek na `clamp()` pro velikost i výšku řádku všech osmi textových úrovní; předání uloženého fontu a celého společného CSS do zadání stránky.
- Jeden odkaz na font na veřejné stránce, správná rodina písma v CSS a použití společných clamp hodnot na veřejné stránce i ve správném obalu náhledu.

Samostatné požadavky na skutečné Google Fonts CSS API ověřily dostupnost všech osmi rodin s vahami 400, 500, 600 a 700. Browser test používá pro odpověď tohoto API řízenou náhradu, aby nebyl závislý na síti; netestuje vzhled každého glyfu všech fontů. Konkrétní stupnici navrhuje až AI uživatele, proto její výsledné CSS vyžaduje kontrolu na daném webu.

## Verze 1.4.0: zadání pro AI v administraci

Ověřeno lokálně 22. 9. 2026 ve WordPressu 6.8.3 / PHP 8.3 a Google Chrome. Prošla integrační sada `npm test`, včetně 62 PHP kontrol přípravy zadání a ovládání nové sekce v prohlížeči:

- Všech pět druhů zadání, správná místa pro vložení výsledku a doplnění uloženého společného vzhledu i obsahu vybrané stránky nebo části webu.
- Oprávnění správce včetně oprávnění upravovat vybranou stránku; odmítnutí nepřihlášeného přístupu, neplatného nonce, jiné metody než POST a chybných vstupů AJAX požadavků.
- Hledání stránek podle názvu bez předávání jejich kódu v seznamu výsledků.
- Zobrazení uloženého HTML jako textu zadání bez jeho spuštění v administraci.
- Kopírování do schránky a nabídka ručního kopírování při odmítnutí přístupu ke schránce.
- Zneplatnění zadání po změně vstupů a odmítnutí opožděné odpovědi pro původní požadavek.
- Ovládání při mobilní šířce 390 px.

Prošla také sada `npm run test:updates`: skutečná výměna instalačních ZIPů v dočasném WordPressu a zachování uložených dat, nastavení, přiřazení menu a aktivace obou součástí. Tyto testy neověřují nasazení na konkrétní hosting ani kvalitu kódu, který následně vytvoří externí AI uživatele.
