# Web svépomocí pro WordPress

Jednoduchá šablona a doprovodný plugin pro sestavení webu z HTML, CSS a JavaScriptu, které vám připraví AI. Kód kopírujete do oddělených polí v administraci WordPressu. Žádný účet AI ani klíč API se s webem nepropojuje.

- **Šablona `web-svepomoci-sablona`** zobrazuje stránky, společnou hlavičku a patičku.
- **Plugin `web-svepomoci-plugin`** přidává editory kódu, nastavení vzhledu, SEO, náhled a ukládání do revizí WordPressu.
- **Složka `examples`** obsahuje hotovou ukázku stránky, hlavičky a patičky.

Potřebujete WordPress **6.4 nebo novější**, PHP **7.4 nebo novější** a účet s oprávněními `manage_options` i `unfiltered_html`. V běžné samostatné instalaci je to správce; v síti Multisite zpravidla správce celé sítě. Jiné role mohou dál pracovat s běžným obsahem podle svých oprávnění, ale nemohou vkládat spustitelný kód do těchto editorů.

## Instalace

1. Použijte připravené balíčky `dist/web-svepomoci-sablona.zip` a `dist/web-svepomoci-plugin.zip`. Pokud jste upravili zdrojové soubory, vytvořte je znovu ve složce `ai-wordpress` příkazem `powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\package.ps1`. Výjimka ze zásad spouštění platí jen pro tento proces; nemění trvalé nastavení Windows.
2. Ve WordPressu otevřete **Vzhled → Šablony → Instalovat šablonu → Nahrát šablonu**. Nahrajte `web-svepomoci-sablona.zip` a aktivujte šablonu.
3. V nabídce **Pluginy → Instalace pluginů → Nahrát plugin** nahrajte `web-svepomoci-plugin.zip` a plugin aktivujte.
4. Otevřete **Web svépomocí** a projděte nastavení. Potom vytvořte první stránku podle [návodu](docs/prvni-web.md).

Zobrazené názvy jsou nové, ale kvůli zachování aktivace a přiřazení menu se technické složky nemění. ZIP soubory mají správnou instalační strukturu: kořenovou složku `ai-web`, respektive `ai-web-studio`. Znovuspuštění balení nahradí předchozí ZIP soubory. Zdrojové složky zůstávají zachované.

Při ruční instalaci zkopírujte `theme/ai-web` do `wp-content/themes/` a `plugin/ai-web-studio` do `wp-content/plugins/`.

## Aktualizace existujícího testovacího webu na 1.2.0

1. Ve své testovací administraci otevřete **Pluginy → Přidat nový → Nahrát plugin** (podle překladu může jít o **Instalace pluginů**).
2. Vyberte místní soubor `dist/web-svepomoci-plugin.zip`, nerozbalujte jej a klikněte na **Nainstalovat**.
3. WordPress rozpozná již nainstalovaný plugin. Zvolte **Nahradit stávající nahraným** (*Replace current with uploaded*).
4. V přehledu pluginů ověřte, že **web-svepomoci-plugin** zůstává aktivní a uvádí verzi **1.2.0**.

Nahrazení aktualizuje soubory pluginu; uložené stránky, HTML, CSS, JS, SEO a revize zůstávají v databázi. Plugin předem nemažte. Stejně nahrajte `dist/web-svepomoci-sablona.zip` přes **Vzhled → Šablony → Instalovat šablonu → Nahrát šablonu** a potvrďte nahrazení. Obě součásti pak mají verzi **1.2.0** a nové názvy. Nový ZIP se na hosting sám neodešle; nahrajte jej uvedeným postupem.

Aktualizace sama nepřepisuje již uložené HTML hlavičky a patičky. Pro propojení starší hlavičky s menu vložte značku popsanou níže a uložte ji. Změny odkazů pak provádějte ve **Vzhled → Menu**.

## Aktualizace z GitHubu

Repozitář: [DanHutar/web-svepomoci](https://github.com/DanHutar/web-svepomoci). Instalační ZIPy najdete mezi přílohami [nejnovějšího vydání](https://github.com/DanHutar/web-svepomoci/releases/latest). Nepoužívejte **Source code (zip)** jako instalační balíček.

Verze 1.2.0 přidává kontrolu nových stabilních vydání. Po jejím prvním ručním nahrání se další verze nabízejí v běžných **Aktualizacích WordPressu**, u pluginu a u šablony. V nabídce **Web svépomocí → Zkontrolovat aktualizace** můžete vynutit nové načtení. Automatické instalace bez kliknutí se samy nezapínají.

Kontrola používá veřejné GitHub API a manifest vydání, nepotřebuje token. Odesílá běžný HTTP požadavek, nikoli obsah vašich stránek nebo přihlašovací údaje. Výsledek se ukládá na hodinu, neúspěch na pět minut. Když GitHub neodpovídá, web dál funguje; aktualizace se nabídne po úspěšné kontrole. Musí být aktivní alespoň náš plugin nebo naše šablona. Stejná čísla verzí obou balíčků zjednodušují vydávání.

Postup pro další vývoj a vydání je v [docs/vydavani.md](docs/vydavani.md).

## Jak se web skládá

| Místo | Co se vkládá |
| --- | --- |
| Úprava stránky | Zapnutí režimu Web svépomocí a oddělené HTML, CSS a JS pro jednu stránku; SEO; volitelné skrytí hlavičky či patičky |
| **Header** v levém menu | Společná hlavička webu včetně svého CSS a JS |
| **Footer** v levém menu | Společná patička webu včetně svého CSS a JS |
| **Vzhled → Menu** | Odkazy pro umístění **Hlavní menu** a **Menu v patičce** |
| **Web svépomocí** | Návod a společná nastavení vzhledu |

V režimu Web svépomocí se místo standardního obsahu dané stránky zobrazí její HTML. Při vypnutí režimu se opět používá běžný obsah WordPressu. Hlavička a patička mají vlastní zapnutí; jejich uložené změny se při aktivním zapnutí projeví na celém webu.

Začněte [návodem pro první web](docs/prvni-web.md). Do AI můžete zkopírovat [připravené zadání](docs/zadani-pro-ai.md). Příklady a seznam souborů najdete v [examples/README.md](examples/README.md).

## Menu z WordPressu ve vlastním HTML

Ve **Vzhled → Menu** vytvořte menu, přidejte stránky a přiřaďte jej k umístění **Hlavní menu**. Do HTML hlavičky uvnitř svého `<nav>` vložte místo ručně napsaných odkazů:

```html
[aiwp_menu location="primary"]
```

Pro patičku přiřaďte menu k umístění **Menu v patičce** a do jejího `<nav>` vložte `[aiwp_menu location="footer"]`. Stejné menu můžete přiřadit k oběma umístěním, nebo použít dvě různá. Hlavičku a patičku uložte a zapněte jejich použití.

Plugin vloží nativní seznam WordPressu `<ul class="aiwp-menu">` s položkami `<li>` a odkazy. Podnabídky používají třídu `.sub-menu`; vzorové CSS v `examples/header.css` a `examples/footer.css` počítá s oběma seznamy. Další změny názvů, pořadí i odkazů stačí uložit ve **Vzhled → Menu**, HTML už nekopírujete. Bez přiřazeného neprázdného menu se odkazy nevypíšou.

## Co tato verze umí a kde má hranice

HTML se vkládá jako část stránky, bez značek `html`, `head`, `body`, `style`, `script` nebo `meta`. CSS a JavaScript mají vlastní pole. PHP se z administrace nespouští. Od verze 1.1.0 se v AI HTML zpracovávají pouze vlastní značky `[aiwp_menu location="primary"]` a `[aiwp_menu location="footer"]`; libovolné WordPress shortcody ani shortcody jiných pluginů se nespouštějí. Další dynamické funkce je potřeba přidat samostatně.

Rychlý náhled běží v odděleném prostředí pro právě upravovaný fragment. Celou stránku v aktivní šabloně kontrolujte přes uložený koncept a standardní náhled WordPressu. Náhled fragmentu nereprodukuje všechny společné styly ani chování výsledného webu.

Menu v rychlém náhledu používá stav načtený při otevření editoru. Pokud menu změníte v jiném okně, nejprve uložte rozpracovaný kód a potom obnovte celou stránku editoru. Tlačítko pro obnovení samotného náhledu nový stav menu nenačítá.

Revize WordPressu zahrnují uložená pole HTML, CSS, JS a SEO; platí nastavení a počet revizí dané instalace. Pokud jsou revize vypnuté, tato historie se nevytváří. Revize nenahrazují zálohu celého webu.

Kód ukládejte tlačítkem **Uložit koncept / Aktualizovat**. Automatické ukládání běžného editoru tato vlastní pole nezahrnuje. Při opuštění neuložených úprav vás prohlížeč upozorní. Společné nastavení vzhledu nemá vlastní revize. Stránku, která obsahovala AI kód, mohou i po jeho vymazání upravovat pouze oprávnění správci; tím se chrání také její starší revize.

Vlastní SEO výstup ustupuje rozpoznaným pluginům Yoast SEO, Rank Math, All in One SEO, SEOPress a The SEO Framework. Pokud některý používáte, upravujte SEO v něm. U jiných SEO doplňků je potřeba ověřit, zda nevypisují stejné značky.

Kód ve vložených polích běží na vašem webu. Pro první pokusy používejte testovací instalaci a kód ze zdroje, kterému důvěřujete. AI nemá dostávat přihlašovací údaje ani API klíče, které by následně vložila do veřejného HTML či JavaScriptu.

## Lokální ukázka a testování

Pro používání na hostingu nepotřebujete Node.js. Vývojář může s Node.js 22+ spustit `npm ci` a pak `npm run preview`. Otevře se dočasný WordPress na adrese uvedené v terminálu, s aktivní šablonou, pluginem a vloženou ukázkou. Administrace je na `/wp-admin/`. Server se ukončí pomocí Ctrl+C; data této ukázky se po ukončení nezachovají. Při prvním spuštění se stáhne WordPress do cache Playground v uživatelské složce.

`npm test` ověřuje PHP, ukládání a oprávnění ve WordPressu a ovládání editoru přes místní Google Chrome. Výsledky a snímky ukládá do `test-results/`. Testovací závislosti ani data se nepřidávají do instalačních ZIPů. Přesnou ověřenou konfiguraci uvádí [záznam ověření](docs/overeni.md).
