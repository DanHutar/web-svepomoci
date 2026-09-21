# Zadání, které můžete zkopírovat do AI

Nahraďte údaje v hranatých závorkách a pošlete následující zadání AI. Jako první si nechte vytvořit obsah stránky. Hlavičku a patičku připravte samostatně podle doplňujících zadání níže.

```text
Vytvoř obsah stránky pro WordPress se šablonou web-svepomoci-sablona a pluginem web-svepomoci-plugin.

Web: [název a krátký popis]
Návštěvníci: [pro koho web je]
Cíl stránky: [například návštěvník mi napíše e-mail]
Obsah a sekce: [skutečné texty, služby, reference a kontakt]
Vizuální styl: [barvy, nálada a přibližné rozložení]
Povolené cíle odkazů: [skutečné adresy stránek a e-mail]
Obrázky: [adresy obrázků z knihovny médií; pokud je nemám, použij rozložení bez nich]

Vrať tři samostatné, jasně označené bloky: HTML, CSS a JavaScript.
Pole v administraci jsou oddělená a každý blok vložím do příslušného pole.

Pravidla:
1. HTML je pouze fragment těla stránky. Nevkládej html, head, body,
   meta, title, style, script, iframe ani PHP. Nevkládej inline události
   jako onclick ani odkazy javascript:. Hlavička a patička jsou společné
   a připravuji je zvlášť, proto je do obsahu stránky nepřidávej.
2. Celý obsah obal prvkem div s jedinečnou třídou ai-page-[nazev].
   Každý CSS selektor omez na tento obal. Nepřepisuj obecně body, html,
   header, footer, a nebo button. Proměnné CSS definuj na vlastním obalu.
   Použij jedinečné názvy případných animací.
3. CSS vrať bez značek style. JavaScript vrať bez značek script.
   Pokud JavaScript není potřebný, uveď prázdný blok nebo tuto skutečnost.
4. JavaScript používej čistý, bez knihoven a externích požadavků. Omez jeho
   selektory na vlastní obal. Kód uzavři do vlastní funkce, která se hned
   spustí. Nepoužívej dokumentové document.write ani globální proměnné.
5. Nepoužívej externí fonty, analytiku, CDN, balíčkové manažery, React,
   Vue, PHP ani API klíče. Nepoužívej WordPress shortcody s výjimkou vlastní
   značky aiwp_menu pro menu podle zadání níže. Kód nebude procházet buildem.
6. Rozložení musí fungovat na telefonu i počítači. Použij dostatečný
   kontrast, viditelný focus klávesnice a přístupné popisky ovládání.
   Animace mají respektovat prefers-reduced-motion.
7. Použij jediný hlavní nadpis h1 a navazující úrovně nadpisů.
   Odkazy musí mít skutečný cíl. Nevytvářej tlačítka bez funkce, nepravdivé
   reference, vymyšlené výsledky nebo formulář, který nic neodesílá.
   Pro kontakt můžeš použít mailto na dodanou adresu.
8. Chybějící údaje jasně označ v doprovodném textu. Nevkládej zástupné
   odkazy #, pokud nevedou na existující sekci na stránce.
9. Mimo bloky kódu navrhni SEO titulek a meta popis. Vložím je zvlášť
   do SEO polí, proto nevytvářej meta značky v HTML.
10. Vrať celé obsahy polí, které mohu přímo zkopírovat. Krátce vysvětli,
    které moje adresy nebo údaje je potřeba před zveřejněním doplnit.
```

## Zadání pro hlavičku

K pravidlům výše přidejte:

```text
Tentokrát vytvoř jen společnou hlavičku webu. Použij kořenový header
s třídou ai-site-header a vlastní nav s aria-label. Odkazy spravuji
ve WordPressu přes Vzhled → Menu v umístění Hlavní menu. Do nav vlož přesně:
[aiwp_menu location="primary"]
Tuto značku nenahrazuj ručně napsanými odkazy. Plugin web-svepomoci-plugin 1.2.0
ji vykreslí jako ul.aiwp-menu s položkami li > a a podnabídkami ul.sub-menu.
Navrhni CSS pro tyto seznamy včetně vynulování odrážek, okrajů a odsazení.
Podnabídky musí být přístupné klávesnicí i dotykem; mohou být stále viditelné.

Mobilní menu musí fungovat bez externích knihoven. Pro jeho přepínání
použij button type="button" s aria-expanded a aria-controls. Při vypnutém
JavaScriptu musí zůstat odkazy dostupné. Escape má menu zavřít a vrátit
focus na přepínač. Nepřidávej nadpis h1. Vrať HTML, CSS a JS zvlášť.
```

## Zadání pro patičku

```text
Tentokrát vytvoř jen společnou patičku webu. Použij kořenový footer
s třídou ai-site-footer.
Obsah: [název, kontakty a skutečné adresy odkazů].
Navigační odkazy spravuji přes Vzhled → Menu v umístění Menu v patičce.
Do nav s aria-label vlož přesně [aiwp_menu location="footer"].
Značku ponech v HTML. Připrav styly pro ul.aiwp-menu, li > a a ul.sub-menu.
Podnabídky mají zůstat přístupné klávesnicí i dotykem.
Nepřidávej nadpis h1 ani smyšlené právní texty. Vrať HTML, CSS a JS zvlášť.
Pokud není potřeba skript, může JS zůstat prázdný.
```

## Zadání pro změnu hotové stránky

```text
Uprav následující stránku pro Web svépomocí. Zachovej oddělení HTML, CSS a JS,
jedinečný obal stránky a všechny dosavadní funkční odkazy.
Pokud HTML obsahuje značky [aiwp_menu location="primary"] nebo
[aiwp_menu location="footer"], zachovej je a styly jejich seznamů.
Požadovaná změna: [konkrétně co změnit].
Vrať úplný nový obsah každého změněného pole, nikoli jen rozdíl.

Současné HTML:
[vložit HTML]

Současné CSS:
[vložit CSS]

Současný JavaScript:
[vložit JS]
```
