# Komentáře

Lokální připravovaná změna: **ByYourself → Komentáře → Zakázat komentáře na celém webu**. Anglicky **ByYourself → Comments → Disable comments across the website**.

Volba je výchozí pro nové i dosavadní instalace, které ji ještě neuložily. Platí také pro dříve vytvořené příspěvky s povolenými komentáři, včetně ukázkového Hello world. WordPress volba pro komentáře u nových příspěvků sama staré příspěvky nemění.

Zapnutí zavře standardní WordPress komentáře, pingbacky a trackbacky. Skryje klasickou komentářovou šablonu, standardní bloky diskuze a widget posledních komentářů; veřejné komentářové RSS a anonymní REST čtení vrátí 403. Zablokuje také standardní přímé odeslání a nové odpovědi administrátora. Správci nadále mohou uložené komentáře číst, upravovat a moderovat v administraci.

Původní komentáře, hodnoty `comment_status` a `ping_status` ani výchozí nastavení WordPressu se nepřepisují. Po odškrtnutí a uložení se použijí původní pravidla jednotlivých příspěvků: původně uzavřené zůstanou uzavřené. Deaktivace pluginu rovněž odstraní toto omezení. Nastavení je samostatné pro daný web.

Po přepnutí vymažte cache webu/CDN a obnovte již otevřené stránky. Plugin nemůže odstranit dříve doručený obsah z cizích cache.

Rozsah: WordPress komentáře a standardní rozhraní. Externí diskuze, ručně vložené formuláře, vlastní SQL nebo interní zápisy důvěryhodných pluginů přes `wp_insert_comment()` tím nejsou bezpečnostně izolovány. Ostatní komentářové pluginy je nutné otestovat samostatně; recenze produktů mohou záviset na `comments_open`. Vlastní interní typy záznamů (například poznámky objednávky) filtr schvalování neblokuje.

## Ověření

Test úspěšně proběhl lokálně 10. 10. 2026. Změna zatím nebyla publikována na GitHub ani nasazena na produkční web.

`npm run test:comments` spouští dočasný WordPress 7.1.2, PHP 8.3 a Chrome. Ověřuje výchozí zákaz, původně otevřené i uzavřené příspěvky, zachování dat, formuláře, přímý POST, REST, RSS, pingbacky/trackbacky, nižší oprávnění, chybný nonce, skutečné uložení checkboxu v obou jazycích a návrat po vypnutí nebo deaktivaci pluginu.

Podklady: [WordPress comments_open](https://developer.wordpress.org/reference/functions/comments_open/), [pre_comment_approved](https://developer.wordpress.org/reference/hooks/pre_comment_approved/).
