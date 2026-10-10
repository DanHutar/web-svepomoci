# Bezpečnostní kontrola pluginu a šablony – 10. října 2026

Rozsah: lokální ByYourself Builder a ByYourself Theme 1.10.4, včetně připraveného zákazu komentářů. Výchozí commit `246672e5545829ff6d195492e431b5b22c458b73`. Kontrola nepokrývá hosting, ostatní pluginy ani kód uložený na produkčním webu. Produkce, GitHub, verze balíčků a Google Fonts se nemění.

PDF HostedScan obsahuje pasivní ZAP, síťový OpenVAS a Nmap. Pasivní ZAP nenahrazuje kontrolu zdrojového kódu a testování oprávnění. V tomto běhu nebyl prováděn nový aktivní ZAP sken.

## Navazující lokální oprava escapování

Přeložené prosté texty editoru, stavu stránky a chybových hlášení používají `esc_html__()`. Dvě přeložená hlášení s HTML odstavcem používají `wp_kses_post()`. Oprava společného updateru je shodná v pluginu i šabloně. Texty a překladové klíče zůstaly zachovány.

- Nativní PHPCS po opravě: **0 hlášení EscapeOutput** (původně 12). Zůstává 30 errors a 12 warnings z ostatních pravidel, posouzených níže; nebyly plošně skryty.
- `npm run test:security`: **45 PHP kontrol + 11 HTTP kontrol prošlo**. Čtyři nové kontroly vykreslují skutečný editor a hlášení pro nepovolené role s překlady obsahujícími `script` a `onerror`. Ověřují, že se nevypíše spustitelné HTML a u hlášení zůstane odstavec.
- Překladové katalogy byly znovu sestaveny; kopie updateru jsou shodné; `git diff --check` prošel.
- `npm test`: **celá integrační sada prošla (exit 0)** na WordPressu 6.8.3 / PHP 8.3. Ověřila editor a ukládání, mobilní a izolovaný náhled, Google Fonts a společnou typografii, 62 kontrol generátoru promptů a jeho prohlížečové ovládání, externí CSS/JS včetně ochrany neveřejného obsahu, zachování SEO, výběr obrázku z médií, funkci šablony po deaktivaci pluginu a převody WebP. Souhrn je v `test-results/results.json`.

Testovací prostředí: zachován jeden worker. Pokus se šesti workery vedl k nekonzistentnímu čtení metadat a nebyl ponechán. Integrační test vytváří pouze v dočasném WordPressu MU plugin, který vypne loopback cron a serverové HTTP požadavky; ověřování aktualizací má vlastní sadu. Navigace prohlížeče má limit 90 sekund místo 30, protože veřejná stránka načítá několik dynamických CSS/JS odpovědí sériově. Funkční assertions ani jejich očekávané výsledky se neuvolňují; nejde o test rychlosti produkčního hostingu. Google Fonts v produktu se nemění a test fontů nadále používá svou deterministickou odpověď.

## Výsledky původního auditu před opravou

- Dokončeno také 9 přímých HTTP kontrol neveřejného HTML/CSS/JS: koncept, soukromá stránka a stránka chráněná heslem. Anonymní odpovědi neobsahovaly kontrolní neveřejný obsah; skripty vracely 404 a CSS/JS správně deklarovaly `nosniff`. Celá samostatná sada `npm run test:security` skončila úspěšně.
- `npm run test:security`: 41 cílených kontrol na WordPressu 7.1.2 / PHP 8.3.33 prošlo. Ověřují anonymního uživatele, odběratele, přispěvatele, autora, redaktora a správce bez `unfiltered_html`; odmítnutí ukládání kódu i s vlastním platným nonce; zákaz editace/mazání chráněného dokumentu; odmítnutí změny přes REST; nepřítomnost AI metadat v REST; chybné nonce; velikost a typy vstupů; SEO URL bez serverových požadavků; odmítnutí cizí URL místo fontu. Dva další skutečné anonymní HTTP POST požadavky na AJAX promptů byly odmítnuty.
- Nativní PHP_CodeSniffer 3.13.6 / WordPress Coding Standards 3.4.1 / PHP 8.3.35: zkontrolováno 34 PHP souborů obou balíčků. Vybrána bezpečnostní pravidla `WordPress.Security.*` a pravidla připravených SQL dotazů. Výsledek: 42 errors a 12 warnings podle klasifikace nástroje, žádná automatická oprava. Tyto kategorie nejsou stupně závažnosti zranitelnosti.
- Plugin Check 2.1.0: výběr deseti kontrol se spustil, ale `late_escaping` a `safe_redirect` skončily na nepodporovaném zamykání souborů v PHP WebAssembly. Celý Plugin Check proto **není označen za úspěšný**. Escapování a bezpečné přesměrování pokrývá dokončený nativní PHPCS výše. Raw výstup neúplného pokusu je `test-results/plugin-check-security.json`.
- `npm test`: oba pokusy prošly PHP částí (syntaxe všech PHP souborů, ukládání, nonce, revize, autosave a oprávnění), ale úplný běh **neprošel**. První skončil časovým limitem načítání stránky v `design-browser.mjs:61`, druhý čekáním na navigaci po uložení v `browser.mjs:51`. Nejde o potvrzenou bezpečnostní chybu; bez dokončeného běhu však nelze potvrdit celou regresní sadu. Časové limity nebyly skryty prodloužením ani odstraněním kontrol.

## Posouzení původních 54 hlášení PHPCS

| Skupina | Počet | Posouzení |
| --- | ---: | --- |
| Escapování výstupu | 12 | Výpis překladů přes `__()` v administračních popiscích a chybových hlášeních. Doporučené zpřísnění: `esc_html__()` pro prostý text a `wp_kses_post()` pro překládané HTML. V kontrolovaných místech nebyl zjištěn vstup ovladatelný návštěvníkem či nižší rolí; nejde o potvrzené XSS. |
| NonceVerification.Missing | 8 | AJAX promptů volá společnou `aiwp_prompt_check_request()` před čtením polí. Ta ověřuje oprávnění, POST a nonce. Statická analýza tento přenos kontroly nerozpoznává. |
| NonceVerification.Recommended | 12 | Čtení parametrů veřejných CSS/JS odpovědí, vyřazeného endpointu cookies a výběru administrační stránky. Nejde o změnové operace, které by samy vyžadovaly nonce. Ochrana neveřejného obsahu se posuzuje samostatně. |
| InputNotSanitized | 16 | Převážně vstupy předávané vlastnímu validátoru a porovnání s povolenými hodnotami; URI pro lokální URL, ETag a metoda požadavku. U kódu správce nelze obecně použít textovou sanitizaci bez změny funkce editoru. Kontrola sledovala i následné použití, nikoli jen chybějící volání `sanitize_*`. |
| MissingUnslash | 6 | Přímá porovnání metody a pevných názvů stránky/skriptu. Přidané lomítko vede k nevyhovující hodnotě, nikoli k vykonání kódu. |

Příklady k dohledání: `includes/class-aiwp-admin.php:58,81,96,130`, `includes/prompts.php:156–201`, `includes/documents.php:167–175`, `includes/styles.php:79–118`, `includes/scripts.php:5–50`. Společný updater je přítomen v obou balíčcích, takže jeho hlášení je započteno dvakrát.

## Ruční kontrola a omezení závěru

V prověřených scénářích nebylo potvrzeno obejití oprávnění, únik neveřejného AI obsahu ani závažná zneužitelná chyba. Doporučené sjednocení escapování překladů bylo následně provedeno, viz navazující oprava výše. Tato zpráva sama není důvodem označit oba balíčky za bezchybné.

Ukládání AI kódu vyžaduje současně `manage_options` a `unfiltered_html`; oprávnění k dokumentu se ověřuje navíc. Nastavení používá WordPress Settings API. AJAX prompty neodesílají data AI službě. SEO URL se serverově nestahují. Šablona používá pro vyhledávání a výpisy standardní WordPress funkce a escapování. WebP hook navazuje na upload WordPressu; kontroluje skutečný MIME, výsledek převodu a zachovává originál při chybě. Updater přijímá manifest a balíčky z konkrétního GitHub repozitáře přes HTTPS; nemá nezávislé podepisování balíčků.

Zůstávají vlastnosti popsané v [předchozí kontrole](bezpecnostni-audit-2026-10-09.md): důvěra v oprávněného správce při publikaci JS, síťové požadavky obrázků/fontů v izolovaném náhledu a důvěra ve vydávací účet GitHubu. Nejde o nově prokázané obejití oprávnění.

Závěr se vztahuje jen na kontrolované scénáře. Není to certifikace, kompletní penetrační test ani důkaz nepřítomnosti zranitelností. Multisite, všechny podporované verze PHP/WordPressu a dodavatelský řetězec nejsou kompletně prověřeny. Nebyla provedena celá sada pravidel pro přijetí do WordPress.org katalogu.

## Opakování kontroly

Navazující vydání 1.10.5 přidává stránku 404. `npm run test:404` prošel: HTTP 404 pro HTML, společné CSS a skripty částí, vyloučení kódu domovské stránky, mobilní rozložení, návrat domů, čeština/angličtina a šablona bez pluginu. `npm run test:updates` ověřil skutečnou výměnu obou ZIP balíčků a zachování obsahu, nastavení i aktivace. Syntaxe všech PHP souborů prošla. Test 404 a bezpečnostní sada vypínají pouze v dočasné instalaci cron a externí serverové HTTP požadavky, stejně jako integrační sada; první pokus před touto izolací skončil časovým limitem HTTP kontroly.

`npm run test:security` spustí cílené dynamické kontroly v dočasném WordPressu. `npm test` spustí dosavadní integrační sadu. Výsledky jsou v ignorované složce `test-results/` a testovací nástroje nejsou součástí distribuovaných balíčků.

`node tools/security-static.mjs` spustí nativní PHPCS pro oba balíčky. Vyžaduje `AIWP_PHP_PATH` s cestou k PHP a `AIWP_PCP_PATH` s cestou k rozbalenému oficiálnímu Plugin Check, který obsahuje PHPCS a WPCS. Při této kontrole šlo o Plugin Check 2.1.0. Návratový kód 1 s validním JSON znamená nalezená hlášení, nikoli čistý výsledek. Chybějící nebo neplatný report je chyba nástroje. Skript nepoužívá AI analýzu ani neodesílá náš kód externí službě.

Při přípravě testů bylo nutné doplnit administrační funkce do testovacího bootstrapu a přejmenovat testovací proměnnou kolidující s globální `$page` WordPressu. Tyto dvě neúspěšné přípravné zkoušky nebyly chybami produktu. Úspěšný běh 41 kontrol je uložen samostatně.

Podklady: [Plugin Check](https://github.com/WordPress/plugin-check), [WordPress bezpečnostní nástroje](https://learn.wordpress.org/lesson/tools-to-detect-security-vulnerabilities/), [ZAP aktivní sken](https://www.zaproxy.org/docs/desktop/start/features/ascan/).
