# Soukromí a cookies

V administraci otevřete **Web svépomocí → Soukromí a cookies**. Analytika a marketing jsou po instalaci vypnuté. Dokud nezapnete vyplněnou kategorii, lišta se automaticky nezobrazuje. Na konci webu je tlačítko Nastavení cookies.

## Až budete chtít měření

1. Vytvořte stránku s informacemi o soukromí a vložte její adresu do nastavení.
2. Vyplňte skutečného poskytovatele, účel, cookies a jejich dobu uchování. Informace doplňte i na stránku o soukromí, včetně provozovatele, práv návštěvníků a případného předávání údajů.
3. Do příslušné kategorie vložte spouštěcí JavaScript služby bez značek `<script>`. Identifikátor měření získáte od svého poskytovatele. Stejný kód nesmí být současně na stránce nebo v jiném pluginu.
4. Vyplňte názvy cookies a klíčů úložiště používaných službou. Cookies podporují koncovou hvězdičku, například `_ga_*`; klíče úložiště musí být přesné. Seznam zjistěte z dokumentace služby a ověřte v prohlížeči.
5. Zapněte kategorii a uložte. Vymažte cache webu. Ověřte přijmutí, odmítnutí i odvolání v anonymním okně a síťových požadavcích prohlížeče.

Návštěvník může přijmout vše, odmítnout volitelné služby nebo vybrat kategorie. Žádná volitelná kategorie není předem zaškrtnutá. Zavření lišty souhlas neuděluje. Web zůstává dostupný.

Volba je uložena 180 dnů v technické cookie `aiwp_consent_…`, spolu s časem a verzí nastavení, bez jedinečného ID návštěvníka. Změna nastavení zneplatní předchozí volbu při příštím načtení stránky. Nejde o centrální evidenci souhlasů. Technická položka localStorage stejného jména s příponou `_sync` synchronizuje změny mezi kartami.

## Změna a odvolání

Tlačítko **Nastavení cookies** zůstává na konci webu. Při odvolání plugin uloží novou volbu, odstraní uvedené dostupné cookies a položky localStorage/sessionStorage a obnoví stránku bez odmítnutých skriptů. Úložiště sdílené s nadále povolenou kategorií se nemaže. Doporučujeme každé službě přiřadit právě jednu kategorii.

Cookies jiných domén, HttpOnly cookies ani již odeslaná data nelze tímto JavaScriptem odstranit. U konkrétní služby je nutné ověřit její chování. Seznam k odstranění není automatický skener cookies.

## Rozsah a cache

Funkce blokuje pouze skripty vložené do této sekce. Kód stránek, další pluginy, vložená videa, mapy, externí fonty nebo tag manager vložený jinam automaticky neblokuje. Projděte skutečné síťové požadavky celého webu. Tato funkce sama o sobě nezaručuje právní soulad webu.

Skripty se načítají samostatným požadavkem `?aiwp_consent_script=analytics` nebo `marketing`. Server bez platné aktuální volby kód nevydá; odpovědi mají zákaz cache. **Tento parametr vylučte z cache CDN a hostingu**, pokud nerespektují hlavičky. Po změnách vymažte také cache HTML. JavaScript je po udělení souhlasu v prohlížeči čitelný; nepatří do něj tajné klíče.

Funkce není závislá na naší šabloně, ale šablona musí standardně volat `wp_head()` a `wp_footer()`. Bez JavaScriptu se volitelné služby spravované pluginem nespouštějí.

Oficiální informace: [ÚOOÚ – otázky a odpovědi ke cookies](https://uoou.gov.cz/verejnost/qa-otazky-a-odpovedi/cookies).
