# Audit pluginu a šablony – 9. října 2026

Rozsah: lokální kód ByYourself Builder a ByYourself Theme vycházející z 1.10.3: editor, ukládání, náhled, CSS/JS, SEO, prompty, WebP a aktualizace. Hosting, další pluginy a aktivní testování produkce jsou mimo rozsah. Cílená kontrola a regresní testy nejsou nezávislý penetrační test ani záruka bezchybnosti.

## Zjištění

1. **Důležité konstrukční riziko: správce publikuje spustitelný kód.** `includes/documents.php` vyžaduje `manage_options` i `unfiltered_html`. `frontend.php` vypisuje HTML bez obecné sanitizace a `scripts.php` poskytuje uložený JS. Validátor odmítá PHP a některé značky, ale například atributy `onerror` neodmítá, přestože je prompty zakazují. Není to doložené obejití oprávnění; škodlivý kód vložený správcem však může napadnout návštěvníky, včetně přihlášeného správce. Další krok: navrhnout omezený HTML režim, kontrolu před publikací a kompatibilní CSP. Zákaz několika řetězců ani upozornění není úplná ochrana.

2. **Náhled je izolovaný, ale má přístup k síti.** `assets/admin.js` používá `sandbox="allow-scripts"` bez `allow-same-origin`. Náhledová CSP zakazuje `connect-src` a formuláře, ale dovoluje obrázky, styly a fonty z HTTP/HTTPS. Náhled tedy může vytvářet externí požadavky i bez `fetch`. Nevkládat hesla ani tajné klíče. Další krok: zvážit přesnější seznam zdrojů při zachování Google Fonts a uživatelských obrázků.

3. **Aktualizace důvěřují GitHubu.** `github-updates.php` omezuje repozitář, názvy balíčků, verzi a manifest a používá HTTPS. Balíčky nemají nezávisle ověřovaný podpis. Kompromitace účtu nebo vydávacího procesu může zasáhnout instalace. Workflow používá akce přes proměnlivé značky `@v4`; testy a publikování sdílejí oprávnění zápisu. Další krok: připnout akce na ověřené commity, oddělit oprávnění testování a publikace, ověřit ochranu větve a účtu. Stav ochrany účtu nebyl kontrolován.

4. **Vestavěná správa cookies je vyřazena.** Odstraněno administrační menu, ukládací rozhraní, lišta, tlačítko a načítání souvisejících aktiv. Staré adresy měřicích skriptů odpovídají 410 bez kódu a bez cache, i při uloženém `external=false` a starém souhlasu. Nastavení zůstává beze změny v databázi. Značka `[aiwp_cookie_settings]` v AI obsahu nic nevypíše. Po nasazení vymazat cache a nastavit externí správu; skripty ani souhlasy se do Complianz automaticky nepřenášejí. Vypnutí modulu neblokuje měření vložené jinde.

## Kontrolované ochrany

- Ukládání AI polí kontroluje nonce, oprávnění ke konkrétnímu dokumentu a obě oprávnění pro kód. AI metadata nejsou v REST API. Ochrana zahrnuje revize a omezení metadat autosave pro nižší role.
- AJAX promptů kontroluje POST, nonce a oprávnění; výběr stránek kontroluje přístup k dokumentu. Data neposílá službě AI.
- CSS/JS odvozují obsah od WordPressem vyhodnocené stránky. Přihlášené, náhledové a heslem chráněné odpovědi používají `nocache_headers`; deklarují typ a `nosniff`.
- Obecné cizí shortcody se v AI HTML nevykonávají. Complianz dokumenty patří do standardních WordPress stránek s vypnutým AI editorem.
- SEO používá sanitizaci a escapování podle kontextu; URL se kvůli validaci serverově nestahují.
- WebP konverze ověřuje MIME, cestu a vytvořený obrázek; originál maže až po úspěchu. Animované PNG a nepodporované formáty ponechává. Systémové obrazové knihovny jsou mimo rozsah.

## Omezení a návrat

CSP/HSTS pro produkci, omezení publikovaného JS, změny updateru a náhledové politiky jsou návrhy pro další samostatnou úpravu. Google Fonts zůstávají. Complianz na produkci neinstalujeme ani nenastavujeme.

Před nasazením záloha souborů a databáze. Návrat ke starší verzi může znovu aktivovat dříve uloženou vlastní správu cookies: před návratem ověřit, že nepoběží současně s Complianz. Staré cookies ani již otevřené stránky tato změna automaticky nevyčistí.

## Ověření

- `npm run test:consent` – prošel na WordPressu 7.1.2 / PHP 8.3 / Chrome: staré nastavení a souhlas, oba jazyky, odstraněné menu a přímý přístup do bývalé administrace, HTTP GET/HEAD i chybný parametr starých skriptů, odpověď 410 bez kódu a s no-store, zachování databáze.
- `npm run test:languages` – prošel na WordPressu 7.1.2 / PHP 8.3 / Chrome: čeština a angličtina, ukázky stránek/headeru/footeru, mobilní náhledy, ukládání, zachování obsahu a samostatná šablona.
- `npm run test:updates` – prošel na WordPressu 6.8.3 / PHP 8.3: skutečná instalace ZIPů, zachování dat včetně starého nastavení cookies, ponechání správy cookies vypnuté, odmítnutí cizího repozitáře, neplatného manifestu, nehotového vydání a výpadku sítě. Používá dočasnou kopii zdrojů se simulovaným starším číslem verze, nikoli kompletní historickou instalaci se všemi jejími pluginy.
- Překladové katalogy byly znovu sestaveny; syntaktická kontrola upraveného JS a `git diff --check` prošly.

- `npm test` – kompletní integrační sada prošla na WordPressu 6.8.3 / PHP 8.3 / Chrome: oprávnění a nonce, revize/autosave, 62 kontrol generátoru promptů, AJAX a anonymní přístup, izolovaný náhled, Google Fonts, ochrana CSS i JS konceptů/soukromých/heslem chráněných stránek, cache, SEO a média, WebP a fungování šablony bez pluginu. V prověřených scénářích nebylo potvrzeno obejití oprávnění ani únik neveřejného AI obsahu. První běh narazil na zákaz spuštění Chrome v sandboxu (EPERM); opakovaný běh s oprávněním ke spuštění lokálního prohlížeče prošel.
- Připraveny lokální instalační ZIPy verze 1.10.4; ověřen aktuální obsah promptů v balíčku. Nebyly publikovány na GitHub ani nasazeny na produkci.

Kontroly nepokrývají všechny další pluginy, cache, multisite ani všechny podporované verze PHP/WordPressu. Závislosti vývojového Node prostředí a nastavení účtu GitHub nebyly kompletně auditovány.

Podklady: [WordPress – nonce a oprávnění](https://developer.wordpress.org/apis/security/nonces/), [escapování výstupu](https://developer.wordpress.org/apis/security/escaping/). Nonce nenahrazuje kontrolu oprávnění.
