# První web krok za krokem

## 1. Připravte si obsah

Napište si název webu, pro koho je určený, jaké služby nabízíte a jak vás mají návštěvníci kontaktovat. Připravte vlastní texty a fotografie. Ukázka dodaná s projektem používá fiktivní ateliér a ukázkovou e-mailovou adresu; před zveřejněním je nahraďte.

Otevřete svou oblíbenou AI a vložte [zadání pro AI](zadani-pro-ai.md). Doplňte údaje v hranatých závorkách. AI vám má vrátit zvlášť HTML, CSS a JS. Samotná instalace Web svépomocí žádnou AI službu nevolá.

## 2. Vytvořte první stránku

1. V administraci zvolte **Stránky → Vytvořit stránku**.
2. Zadejte název, například „Úvod“.
3. V nastavení Web svépomocí u stránky zapněte použití vlastního kódu.
4. Do pole **HTML** vložte pouze HTML od AI. Do **CSS** vložte styly a do **JS** skript. Prázdné pole JS je v pořádku, pokud stránka žádný skript nepotřebuje.
5. Uložte koncept. Zkontrolujte rychlý náhled a potom běžný náhled WordPressu.
6. Až stránka odpovídá představě, publikujte ji.

Do polí nekopírujte trojité zpětné apostrofy, označení jazyka ani vysvětlení kolem kódu. HTML patří do pole pro HTML, nikoli do běžného vizuálního editoru. Při aktivním režimu Web svépomocí se běžný obsah stránky nezobrazuje; po jeho vypnutí se opět použije.

Pro první pokus můžete vložit obsah `examples/page.html`, `examples/page.css` a `examples/page.js`. E-mail `ahoj@example.com` v ukázce je zástupný; nahraďte jej vlastní adresou včetně odkazů začínajících `mailto:`.

Jednodušší ukázku vloží přímo tlačítko **Vložit ukázkový obsah**. Od verze pluginu 1.0.1 tím zároveň zapnete použití vlastního kódu. Potom stále musíte kliknout na **Aktualizovat / Publikovat**; tlačítko **Obnovit náhled** nic neukládá. Ve verzi 1.0.0 zapněte volbu **Zobrazovat obsah z AI editoru** ručně.

V nabídce **Nastavení → Čtení** pak vyberte statickou úvodní stránku a nastavte na ni právě vytvořený „Úvod“.

V přehledu **Web svépomocí** můžete upravit společnou barvu, písmo, šířku obsahu a globální CSS. Aby je používal i kód od AI, požádejte ji o použití proměnných `--aiwp-accent`, `--aiwp-font` a `--aiwp-width`. Výslovně zadané barvy nebo rozměry v CSS stránky mají přednost; dodaná ukázka si například určuje vlastní paletu.

## 3. Přidejte společnou hlavičku a patičku

1. Otevřete **Header** v levém menu administrace.
2. Vložte HTML, CSS a JS hlavičky. Pro ukázku použijte soubory `examples/header.*`.
3. Zapněte použití vlastní hlavičky a uložte změny.
4. Stejným způsobem vyplňte **Footer** ze souborů `examples/footer.*`.

Hlavička a patička jsou společné pro web. Jakmile jsou zapnuté, jejich uložení nebo publikování mění i veřejný web. Před větší úpravou si uložte kopii původního kódu a použijte testovací instalaci.

### Propojte hlavičku s Vzhled → Menu

Funkce byla přidána v AI Web Studio 1.1.0 a je součástí přejmenovaného **web-svepomoci-plugin 1.2.0**. Starší instalaci aktualizujte podle [návodu k aktualizaci](../README.md#aktualizace-existujícího-testovacího-webu-na-120). Šablona se nyní jmenuje **web-svepomoci-sablona**.

1. Otevřete **Vzhled → Menu**, napište název menu a klikněte na **Vytvořit menu**.
2. V levé části vyberte své stránky a použijte **Přidat do menu**. Položky můžete přetahováním seřadit; odsazením pod jinou položku vytvoříte podnabídku.
3. V nastavení umístění zaškrtněte **Hlavní menu** a klikněte na **Uložit menu**.
4. Otevřete **Header → HTML**. Pokud už máte ukázku z `examples/header.html` verze 1.1.0, značku obsahuje. Ve starší hlavičce nahraďte pouze odkazy uvnitř navigace touto značkou:

```html
<nav class="sample-site-header__nav" id="sample-primary-menu" aria-label="Hlavní navigace">
  [aiwp_menu location="primary"]
</nav>
```

5. Ostatní HTML, zejména tlačítko mobilního menu, ponechte. U vlastní odlišné hlavičky zachovejte její třídy a ID navigace. Zkontrolujte volbu **Používat tuto vlastní část webu** a klikněte na **Aktualizovat**; hlavička musí být publikovaná.
6. Otevřete veřejnou stránku. Další názvy, pořadí a adresy odkazů už měňte pouze ve **Vzhled → Menu**. Nové HTML není potřeba vkládat.

Menu se vykresluje jako seznam `.aiwp-menu` s položkami `<li>` a případnými podnabídkami `.sub-menu`. Pokud používáte starší ukázkové CSS, nahraďte ho aktuálním `examples/header.css`; u vlastního vzhledu požádejte AI o styly pro tyto seznamy. Ukázka zobrazuje podnabídky přímo, takže fungují i bez myši. Mobilní skript `examples/header.js` zůstává stejný.

Pro patičku vytvořte další menu, nebo stejné menu přiřaďte také k umístění **Menu v patičce**. Do HTML **Footer** uvnitř `<nav>` vložte `[aiwp_menu location="footer"]` a uložte změny. Aktuální `examples/footer.html` i `examples/footer.css` jsou na toto menu připravené. Bez přiřazeného neprázdného menu se odkazy nevypíšou.

Odkazy na sekce jako `/#sluzby` nebo `/#postup` přidávejte v nabídce **Vlastní odkazy** ve **Vzhled → Menu**. Musí směřovat na existující sekce skutečné úvodní stránky. Pro stránku Kontakt nejprve vytvořte stránku a potom ji přidejte do menu. Odkazy začínající `/`, včetně odkazu na značce „forma.“, předpokládají web v kořeni domény. Pokud WordPress běží například na `example.cz/web/`, použijte skutečné adresy včetně této cesty.

Rychlý náhled editoru používá menu načtené při jeho otevření. Po úpravě menu v jiném okně uložte rozpracovaný kód a obnovte celou stránku editoru. Samotné **Obnovit náhled** načtené menu nezmění.

Na stránce můžete společnou hlavičku a patičku jednotlivě skrýt. Hodí se to například pro reklamní stránku s vlastním rozložením.

## 4. Nastavte SEO

U konkrétní stránky najdete SEO pole. Vyplňte srozumitelný SEO titulek, krátký meta popis a případně obrázek pro sdílení. SEO titulek se používá v názvu karty prohlížeče; není to automaticky viditelný nadpis v HTML stránky. Ten musí obsahovat vložený kód.

Adresu canonical měňte pouze tehdy, když potřebujete určit jinou hlavní adresu obsahu. Pro běžnou jedinečnou stránku ji ponechte prázdnou a použije se její vlastní adresa. Volba zákazu indexace sděluje vyhledávačům, že stránku nemají zařadit do výsledků; nechrání obsah heslem.

Pokud používáte Yoast SEO, Rank Math, All in One SEO, SEOPress nebo The SEO Framework, nastavte SEO v tomto pluginu. Web svépomocí mu při rozpoznání přenechá generování SEO značek, aby se výstupy neduplikovaly.

## 5. Ověřte skutečný výsledek

- Otevřete uložený koncept přes běžný náhled WordPressu. Rychlý náhled kontroluje samotný fragment a není náhledem celého webu.
- Zúžte okno na šířku telefonu a vyzkoušejte menu.
- Klikněte na odkazy a tlačítka. V ukázce kontaktní tlačítko otevírá e-mailovou aplikaci; formulář ani odesílání zpráv na serveru součástí nejsou.
- Projděte navigaci klávesou Tab a potvrzujte Enterem. Zkontrolujte, že je vidět aktuálně vybraný prvek.
- Po publikování otevřete stránku také jako odhlášený návštěvník. Pokud používáte cache, po úpravě ji obnovte.

## Další úpravy a návrat k předchozí verzi

Při změně předejte AI současné HTML, CSS i JS a popište konkrétní požadavek. Požádejte o úplný výsledný obsah každého změněného pole. Pro začátečníka je snazší celé pole nahradit než hledat jednotlivé řádky.

Uložené verze najdete ve standardních revizích WordPressu. Revize zahrnují také kód a SEO uložené v metadatech. Počet uchovaných verzí závisí na nastavení WordPressu a revize nemusí být dostupné, pokud je instalace vypíná. Návrat kontrolujte v náhledu; automatické ukládání neberte jako náhradu vědomého uložení změn.

## Když něco nefunguje

| Problém | Co zkontrolovat |
| --- | --- |
| Pole Web svépomocí nevidím nebo do nich nemohu psát | Aktivaci pluginu a oprávnění účtu. Je potřeba správce s možností vkládat nefiltrované HTML; v Multisite obvykle správce sítě. |
| Po vložení kódu vidím původní obsah | Zda je u stránky zapnutý režim Web svépomocí a změny jsou uložené. |
| V náhledu chybí hlavička nebo některé styly | Otevřete běžný náhled uložené stránky. Rychlý náhled zobrazuje pouze upravovaný fragment. |
| Nový styl změnil i menu | Požádejte AI, aby všechny styly omezila na unikátní třídu dané stránky a nepoužívala obecné selektory jako `header`, `button` nebo `body`. |
| Změny ve Vzhled → Menu nejsou v hlavičce | Ověřte plugin 1.1.0, přiřazení menu k umístění **Hlavní menu** a značku `[aiwp_menu location="primary"]` v uloženém HTML hlavičky. Staré ručně napsané odkazy se samy nepřepíšou. |
| Menu je prázdné | Přidejte položky do menu, přiřaďte správné umístění a klikněte na **Uložit menu**. |
| Ve veřejném webu je menu aktuální, v rychlém náhledu staré | Uložte rozpracovaný kód a obnovte celou stránku editoru. |
| Odkaz ukazuje chybu 404 | Zda cílová stránka existuje a odkaz odpovídá její skutečné adrese. |
| Kontaktní tlačítko neodesílá formulář | `mailto:` pouze otevírá e-mailovou aplikaci. Skutečný formulář potřebuje samostatné řešení pro zpracování a doručení zpráv. |
