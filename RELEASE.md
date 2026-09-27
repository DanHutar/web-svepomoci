# Web svépomocí 1.8.1

- Oprava nefunkčního tlačítka **Vybrat z médií** v SEO panelu na WordPressu 7.1.2. Identifikátor pole SEO titulku již nekoliduje s identifikátorem nadpisu panelu, který vytváří WordPress.
- Počítadlo znaků ověřuje, že pracuje se vstupním polem. Skript má výslovnou závislost na knihovně médií a při jejím nenačtení zobrazí srozumitelnou zprávu místo tichého selhání.
- Výběr a změna obrázku, zavření dialogu bez změny, uložení a výpis obrázku v metadatech pro sdílení mají vlastní test na WordPressu 6.8.3 i 7.1.2.
- Již uložené SEO hodnoty se nepřevádějí ani nemažou. Převod WebP a další dosavadní funkce zůstávají zachované.

Od verze 1.2.0 aktualizujte běžným tlačítkem ve WordPressu; kontrolu vyvoláte přes **Web svépomocí → Zkontrolovat aktualizace**. Ze starších verzí nahrajte instalační ZIPy ručně a potvrďte nahrazení. Po aktualizaci vymažte případnou cache webu.

Po aktualizaci znovu načtěte otevřený editor stránky, případně použijte Ctrl+F5. Vybraný obrázek potvrďte tlačítkem **Použít tento obrázek** a stránku uložte.

Podrobnosti: [Ověření funkcí](https://github.com/DanHutar/web-svepomoci/blob/main/docs/overeni.md).

Použijte přílohy **web-svepomoci-plugin.zip** a **web-svepomoci-sablona.zip**. **Source code (zip)** není instalační balíček WordPressu.
