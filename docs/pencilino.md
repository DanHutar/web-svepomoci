# ByYourself + Pencilino

Pencilino 2.0.4 z dodané složky je samostatný plugin. ByYourself nepřebírá jeho editor, REST API, oprávnění ani ukládání. Nainstalujte oba pluginy a ByYourself Theme. PHP do HTML editoru nepatří.

## HTML stránky, headeru i footeru

```html
<h1>[aiwp_text id="hero-title" label="Hlavní nadpis"]Vítejte[/aiwp_text]</h1>
[aiwp_richtext id="intro" label="Úvod"]<p>Popis nabídky.</p>[/aiwp_richtext]
[aiwp_button id="contact" url="/kontakt" class="moje-tlacitko"]Kontakt[/aiwp_button]
[aiwp_image id="hero-photo" attachment="123" class="moje-foto" /]
```

123 nahraďte skutečným ID obrázku z knihovny médií. Bez platného výchozího obrázku Pencilino obrazové pole nezobrazí. CSS zůstává v ByYourself. Nadpisy/texty nejsou automaticky rozpoznávány: existující HTML musí autor nejdřív označit. Prompt generovaný při aktivním Pencilinu již obsahuje pokyny pro tyto značky.

ID udržujte stabilní, jedinečná v rámci dokumentu a nevnořujte značky do sebe, atributů, JS nebo CSS. Nepoužívejte jeden ID pro různé typy. Interní ID je `byyourself-ID_DOKUMENTU.ID_POLE`. Header/footer proto sdílejí změny na všech stránkách, ale stejné ID na dvou stránkách se navzájem nepřepíše. Duplikovaná stránka má nové ID a začíná výchozím obsahem.

Po návštěvě stránky přihlášeným uživatelem s oprávněním Pencilino otevřete jeho editor. Uložení provádí Pencilino, ne ByYourself. Klientovi to nezpřístupňuje HTML/CSS/JS administraci ByYourself. Oprávnění Pencilina zůstávají podle jeho vlastního nastavení (není to izolace klientů mezi jednotlivými stránkami).

Menu zůstává spravováno ve Vzhled → Menu. Výchozí navigace šablony je označena jako region WordPressu; správce jej může otevřít v administraci. Soukromé připomínky Pencilina jsou oddělené od návštěvnických komentářů, takže zákaz komentářů ByYourself zůstává zapnutý.

## Důležité chování

- Pencilino ukládá hodnoty zvlášť od výchozího HTML. Změna výchozího textu v ByYourself nepřepíše již uloženou hodnotu Pencilina. Návrat/revize HTML nevrací historii Pencilina.
- Po vypnutí Pencilina zůstává web funkční a zobrazí původní hodnoty z HTML. Uložené změny Pencilina se znovu použijí po aktivaci se stejnými ID; nejde o export těchto změn do HTML.
- Náhled přímo v HTML editoru ByYourself není frontendový editor Pencilina; kontrolujte publikovanou stránku nebo standardní WP náhled.
- Cache serveru/CDN musí obcházet přihlášené uživatele a po úpravách být vyprázdněna podle použité cache. ByYourself neřídí externí cache.
- Společné CSS generujte se selektory omezenými na vlastní obal, nikoli globálními pravidly přepisujícími editor/media dialogy. V editorových relacích neodebíráme core styly WP.
- Nejsou povoleny libovolné shortcody ani spouštění PHP. Google Fonts zůstávají zachované.

Lokální integrace se týká obecného ByYourself Builderu a ByYourself Theme, nikoli samostatné šablony Daniel Hutár. Pencilino zdrojové soubory nebyly změněny. Nasazení a vydání na GitHub se provádí samostatně.
