# Web svépomocí 1.6.0

- Samostatné AI stránky v naší šabloně vynechávají nepotřebné základní styly bloků, barevné palety a globální styly WordPressu.
- Pokud kód stránky, zobrazené hlavičky či patičky, společné CSS nebo třídy menu odkazují na styly WordPressu, jejich načítání zůstává zachované.
- Běžné stránky s bloky, stránky s vypnutým AI editorem, formuláře pro zadání hesla, vlastní šablony stránek a jiné šablony webu zachovávají standardní načítání stylů.
- Na veřejném webu s naší šablonou se nenačítá doplňkový skript a CSS WordPressu pro emoji. Běžné znaky emoji se zobrazují podle podpory zařízení.

Od verze 1.2.0 aktualizujte běžným tlačítkem ve WordPressu; kontrolu vyvoláte přes **Web svépomocí → Zkontrolovat aktualizace**. Ze starších verzí nahrajte instalační ZIPy ručně a potvrďte nahrazení. Po aktualizaci vymažte případnou cache webu.

Úprava zkracuje zdroj HTML, není ochranou proti kopírování. Obsah a jeho potřebné styly zůstávají dostupné prohlížeči. SEO metadata, API, RSS a ostatní funkce WordPressu se nevypínají. Nastavení ani uložený kód není potřeba převádět.

Podrobnosti: [Společný vzhled a načítání CSS](https://github.com/DanHutar/web-svepomoci/blob/main/docs/spolecny-vzhled.md).

Použijte přílohy **web-svepomoci-plugin.zip** a **web-svepomoci-sablona.zip**. **Source code (zip)** není instalační balíček WordPressu.
