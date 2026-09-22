# Zadání 1: společný vzhled a typografie

## Načítání CSS od verze 1.5.0

Společné CSS i CSS stránky, hlavičky a patičky se načítá odděleně od HTML. Plugin připraví CSS odpověď pro právě zobrazenou stránku; na disku nevznikají veřejné kopie kódu konceptů. Adresa obsahuje otisk aktuálního obsahu, takže se po uložení CSS změní. Prohlížeč může uloženou odpověď znovu ověřit pomocí ETag; neveřejné náhledy a stránky s heslem se do cache neukládají.

Veřejná podoba má zkrácené mezery a odstraněný text komentářů. Textové řetězce, URL, mezery potřebné pro výpočty a oddělení tokenů zůstávají zachované. U neobvyklých escapovaných identifikátorů nebo nedokončených komentářů plugin ponechá původní CSS, aby zmenšení nezměnilo význam. V administraci a zadáních pro AI pracujete dál s původním čitelným kódem.

CSS není utajené: prohlížeč je potřebuje a návštěvník si je může stáhnout. Úprava odstraní vypisování našeho uloženého CSS přímo do zdrojového HTML. Jiné pluginy mohou vkládat vlastní styly nebo znovu vložit externí CSS do HTML. Po aktualizaci vymažte případnou cache webu; pokud používáte optimalizaci CSS, ověřte, že nezapíná vložení celého CSS přímo do stránky.

## Méně výchozího kódu WordPressu od verze 1.6.0

AI stránka s naší šablonou může vynechat výchozí styly bloků a barevné palety WordPressu, pokud její vlastní kód vystačí se společným a místním CSS. Plugin kontroluje uložené HTML, CSS a JS zobrazených částí, společné a dodatečné CSS i třídy přiřazených menu. Při rozpoznání tříd jako `wp-block-button`, `has-vivid-red-color` nebo proměnných `--wp--preset--…` výchozí styly ponechá.

Běžné stránky s bloky, formuláře chráněných stránek, vlastní šablony stránek, náhled Přizpůsobení a jiné aktivní šablony se takto neodlehčují. Doplněk třetí strany, který na AI stránku dynamicky vkládá bloky, může potřebovat standardní styly: vývojář je zachová filtrem `add_filter( 'aiwp_trim_core_styles', '__return_false' );` ve vlastním pluginu. Automatická kontrola uloženého kódu nemůže odhalit každý dynamicky vytvořený prvek.

Na veřejných stránkách naší šablony se také vypíná skript a CSS pro dodatečnou podporu emoji. Samotné znaky emoji zůstávají v obsahu a vykresluje je zařízení návštěvníka; starší zařízení nemusí zobrazit všechny nové znaky. Administrace, e-maily a RSS se touto změnou neupravují. SEO metadata a služby WordPressu zůstávají dostupné.

## Příprava společného vzhledu

Nejprve otevřete **Web svépomocí → Vzhled webu**, vyberte písmo, barvu a šířku a uložte nastavení. Od verze **1.4.0** pak můžete otevřít **Web svépomocí → Zadání pro AI**, vybrat **Společný vzhled a typografie** a popsat zaměření i požadovaný styl webu. Klikněte na **Připravit zadání** a **Zkopírovat zadání**. Doplní se uložený font, barva, šířka a současné společné CSS. [Podrobný postup](zadani-ve-wordpressu.md).

Dosavadní tlačítko **Zkopírovat zadání pro společné CSS** ve **Vzhled webu** zůstává dostupné; bere hodnoty přímo z rozepsaného formuláře. Nová sekce **Zadání pro AI** naproti tomu používá uložené nastavení. AI nic negeneruje přímo ve WordPressu: zadání jí vložíte a výsledek zkopírujete zpět.

Nová nabídka obsahuje Inter, Roboto, Open Sans, Montserrat, Nunito Sans, Source Sans 3, Lora a Merriweather. Jde o výběr veřejných Google Fonts, nikoli celý katalog. Font se načítá ze serverů Googlu přes CSS API bez API klíče, s `display=swap` a vahami 400, 500, 600, 700. Původní systémové písmo a Georgia zůstávají dostupné pro stávající weby a použití bez stahování fontu.

Pro ruční použití nahraďte údaje v tomto zadání:

```text
Navrhni společné CSS pro WordPress s web-svepomoci-plugin a web-svepomoci-sablona.
Font vybraný ve Vzhled webu: [PŘESNÝ NÁZEV]
Barva: [BARVA] přes var(--aiwp-accent).
Šířka: [ŠÍŘKA] přes var(--aiwp-width).
Účel webu, cílová skupina, nálada: [DOPLŇ]

Font načítá plugin. Všude používej var(--aiwp-font), nepřepisuj tuto
proměnnou a nevkládej @import, @font-face, link nebo další externí font.
Používej váhy 400, 500, 600 a 700.

Podle proporcí fontu navrhni H1, H2, H3, H4, H5, H6, body a small.
Pro KAŽDOU z osmi úrovní vytvoř font-size: clamp(...) a line-height: clamp(...).
Neexistuje jediná správná stupnice daná názvem fontu; svůj návrh zdůvodni.

Na :root definuj --aiwp-size-h1 až --aiwp-size-h6, --aiwp-size-body,
--aiwp-size-small a --aiwp-leading-h1 až --aiwp-leading-h6,
--aiwp-leading-body, --aiwp-leading-small. Všech 16 hodnot bude clamp().
Velikosti mají minimum a maximum v rem a prostřední člen calc(rem + vw).
U line-height použij kompatibilní délky, např.
clamp(1.15em, calc(1.1em + 0.2vw), 1.35em), a hodnoty vhodně uprav
pro danou úroveň a font. Nemíchej bezrozměrná čísla s délkovými jednotkami.
Připoj selektory, které proměnné skutečně používají; nestačí jen proměnné.
Řádkování přiřaď přímo jednotlivým úrovním, aby se nedědila nevhodná délka.

Styly omez na :where(.aiwp-content, .aiwp-header, .aiwp-footer).
Body znamená běžný text v těchto obalech. Styluj h1–h6, p, li a small
uvnitř nich; nezasahuj do administrace ani jiných částí webu.
Nepoužívej globální reset nebo !important. Písmo html neměň na pevné px.
Běžný text minimálně 1rem, small zpravidla minimálně 0.875rem;
small nepoužívej na podstatné informace. Nadpisy mohou zalamovat.
Ověř češtinu a diakritiku, šířky 320–1440 px a zvětšení textu na 200 %.
Nepoužívej pevné výšky textových bloků nebo opakované zmenšování seznamů.

Navrhni společné třídy .aiwp-button a .aiwp-card, s přístupným focusem.
Zachovej stávající třídy a funkce; případné konflikty vysvětli.
Vrať jeden úplný blok CSS bez style, ne HTML nebo JavaScript.
Vložím jej do Vzhled webu → Společné CSS místo předchozího obsahu.
Mimo kód stručně vysvětli stupnici a jak ji ověřit.

Současné společné CSS:
[VLOŽ CELÉ DOSAVADNÍ CSS, NEBO NAPIŠ PRÁZDNÉ]
```

Výsledek vložte do **Společného CSS** a klikněte na **Uložit vzhled webu**. Potom připravujte stránky, Header a Footer přes [Zadání pro AI ve WordPressu](zadani-ve-wordpressu.md), nebo použijte [ruční vzory](zadani-pro-ai.md). Tlačítko pro zadání v editoru stránky, Headeru i Footeru také připojí uložený font a společné CSS. Po změně společných nastavení znovu načtěte otevřené editory.

Volba jiného fontu sama nemění hodnoty clamp(). Pro nový font si nechte stupnici znovu navrhnout. Již uložené CSS stránek se automaticky nepřepisuje: vlastní font-family, font-size nebo line-height může přebít společný vzhled. Takové výjimky odstraňujte po kontrole jednotlivých stránek, nikoli hromadným mazáním CSS.

Načítání vychází z [oficiálního Google Fonts CSS API](https://developers.google.com/fonts/docs/css2).
