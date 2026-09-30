# Soukromí a cookies

V administraci otevřete **Web svépomocí → Soukromí a cookies**. Od verze 1.9.2 je bez uložené volby zaškrtnuto **Souhlas spravuje externí plugin**: naše lišta, tlačítka a skripty se nenačítají. Již uložená volba zůstává zachovaná. Zaškrtnutí samo žádný externí plugin nezapíná.

Pro vlastní správu zrušte zaškrtnutí a uložte. Analytika a marketing zůstávají vypnuté, dokud je sami nenastavíte. Lišta se automaticky zobrazí až po zapnutí vyplněné kategorie.

## Tlačítko přímo v patičce

Do HTML svého Footeru vložte na požadované místo samostatnou značku:

```text
[aiwp_cookie_settings]
```

Vytvoří tlačítko `button.aiwp-cookie-settings`. Jeho vzhled můžete upravit v CSS patičky. Nevkládejte značku do dalšího odkazu nebo tlačítka a nepřepisujte atribut `hidden`; další JS není potřeba. Na stránce mohou být i dvě ovládání, obě fungují. Po zavření nastavení se fokus vrátí na použité tlačítko.

Pokud je značka vykreslená, samostatný blok pod patičkou se nepřidá. Odkazy na informační stránky vložte do menu v patičce. Výchozí patička naší šablony již tlačítko obsahuje. U vlastní patičky bez značky, skryté patičky nebo jiné šablony zůstává záložní ovládání na konci stránky. V náhledu AI editoru je tlačítko pouze neaktivní ukázka; otevírání a změnu souhlasu testujte na webu.

V externím režimu značka nic nevypíše. Ovládání Complianz či jiného správce nastavte v příslušném pluginu.

## Až budete chtít měření

Pokud používáte Complianz nebo jiného správce souhlasu, nejprve postupujte podle oddílu „Externí správce“ níže. Následující kroky jsou určené pro vlastní správu našeho pluginu.

1. Vytvořte stránku s informacemi o soukromí a vložte její adresu do nastavení.
2. Vyplňte skutečného poskytovatele, účel, cookies a jejich dobu uchování. Informace doplňte i na stránku o soukromí, včetně provozovatele, práv návštěvníků a případného předávání údajů.
3. Do příslušné kategorie vložte spouštěcí JavaScript služby bez značek `<script>`. Identifikátor měření získáte od svého poskytovatele. Stejný kód nesmí být současně na stránce nebo v jiném pluginu.
4. Vyplňte názvy cookies a klíčů úložiště používaných službou. Cookies podporují koncovou hvězdičku, například `_ga_*`; klíče úložiště musí být přesné. Seznam zjistěte z dokumentace služby a ověřte v prohlížeči.
5. Zapněte kategorii a uložte. Vymažte cache webu. Ověřte přijmutí, odmítnutí i odvolání v anonymním okně a síťových požadavcích prohlížeče.

Návštěvník může přijmout vše, odmítnout volitelné služby nebo vybrat kategorie. Žádná volitelná kategorie není předem zaškrtnutá. Zavření lišty souhlas neuděluje. Web zůstává dostupný.

Volba je uložena 180 dnů v technické cookie `aiwp_consent_…`, spolu s časem a verzí nastavení, bez jedinečného ID návštěvníka. Změna nastavení zneplatní předchozí volbu při příštím načtení stránky. Nejde o centrální evidenci souhlasů. Technická položka localStorage stejného jména s příponou `_sync` synchronizuje změny mezi kartami.

## Změna a odvolání

Tlačítko **Nastavení cookies** je ve vlastní patičce nebo v záložním ovládání na konci stránky. Při odvolání plugin uloží novou volbu, odstraní uvedené dostupné cookies a položky localStorage/sessionStorage a obnoví stránku bez odmítnutých skriptů. Úložiště sdílené s nadále povolenou kategorií se nemaže. Doporučujeme každé službě přiřadit právě jednu kategorii.

Cookies jiných domén, HttpOnly cookies ani již odeslaná data nelze tímto JavaScriptem odstranit. U konkrétní služby je nutné ověřit její chování. Seznam k odstranění není automatický skener cookies.

## Rozsah a cache

Funkce blokuje pouze skripty vložené do této sekce. Kód stránek, další pluginy, vložená videa, mapy, externí fonty nebo tag manager vložený jinam automaticky neblokuje. Projděte skutečné síťové požadavky celého webu. Tato funkce sama o sobě nezaručuje právní soulad webu.

Skripty se načítají samostatným požadavkem `?aiwp_consent_script=analytics` nebo `marketing`. Server bez platné aktuální volby kód nevydá; odpovědi mají zákaz cache. **Tento parametr vylučte z cache CDN a hostingu**, pokud nerespektují hlavičky. Po změnách vymažte také cache HTML. JavaScript je po udělení souhlasu v prohlížeči čitelný; nepatří do něj tajné klíče.

Funkce není závislá na naší šabloně, ale šablona musí standardně volat `wp_head()` a `wp_footer()`. Bez JavaScriptu se volitelné služby spravované pluginem nespouštějí.

Oficiální informace: [ÚOOÚ – otázky a odpovědi ke cookies](https://uoou.gov.cz/verejnost/qa-otazky-a-odpovedi/cookies).

## Externí správce (Complianz a podobné pluginy)

Od verze 1.9.1 můžete zaškrtnout **Souhlas spravuje externí plugin** a uložit. Naše lišta, tlačítko i odkaz na soukromí v patičce a jejich CSS/JS se přestanou zobrazovat a načítat. Server přestane vydávat zde uložené měřicí skripty i návštěvníkům se starým souhlasem. Editor, SEO a další funkce zůstávají dostupné.

Přepínač externí plugin neinstaluje ani nenastavuje. V Complianz samostatně nastavte služby, dokumenty a ovládání změny souhlasu. Kódy ani záznamy souhlasu se nepřenášejí. Měření nevkládejte do obou systémů současně. Nastavení naší správy zůstane uchované; dokud je externí režim zaškrtnutý, jeho ostatní pole se při uložení nemění.

Po přepnutí vymažte cache HTML, CDN i optimalizačních pluginů a otevřete nové anonymní okno. Stránky již otevřené u návštěvníků mohou až do obnovení používat dříve načtené skripty. Přepnutí samo nemaže dříve uložené cookies měření; jejich správu a odvolání ověřte v novém správci. V externím režimu náš JavaScript nezasahuje do úložiště externích služeb.

Při návratu nejprve vypněte souhlas a měření v externím pluginu, zrušte naše zaškrtnutí, ověřte původní nastavení a uložte. Vymažte cache. Staré souhlasy našeho pluginu se po návratu znovu nepoužijí; návštěvník provede novou volbu.

Dokumenty generované Complianz ponechte na běžných WordPress stránkách s vypnutým AI editorem. Náš HTML editor neumí jejich shortcody. Z patičky na dokumenty odkažte přes menu. Pokud chcete ručně vytvořené informační stránky, textové prompty najdete ve složce `prompty` obou instalačních ZIPů a [zde v repozitáři](../prompty/README.txt).
