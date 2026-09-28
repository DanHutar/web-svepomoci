# Web svépomocí 1.9.2

- **Souhlas spravuje externí plugin** je nyní výchozí stav, pokud volba ještě není uložená. Naše lišta, ovládání i skripty jsou vypnuté. Již uložená volba se nemění; pro vlastní správu zaškrtnutí zrušte a uložte.
- Tlačítko Nastavení cookies umístíte přímo do HTML patičky značkou **[aiwp_cookie_settings]**. Výsledné tlačítko má třídu `aiwp-cookie-settings`; další JavaScript není potřeba.
- Pokud se tlačítko vykreslí v patičce, samostatný blok pod ní se nepřidá. Výchozí patička šablony má tlačítko již uvnitř. U vlastní patičky bez značky zůstává záložní ovládání na konci stránky.
- Podpora více ovládání, návratu fokusu a ukázky v náhledu editoru. Prompty a příklad patičky používají novou značku.

Od verze 1.2.0 aktualizujte běžným tlačítkem ve WordPressu; kontrolu vyvoláte přes **Web svépomocí → Zkontrolovat aktualizace**. Ze starších verzí nahrajte instalační ZIPy ručně a potvrďte nahrazení. Po aktualizaci vymažte případnou cache webu.

V externím režimu značka nic nevypíše. Ovládání změny souhlasu musí poskytovat externí plugin, například Complianz; přepínač jej nenastavuje. [Návod a omezení](https://github.com/DanHutar/web-svepomoci/blob/main/docs/soukromi-cookies.md). [Textové prompty](https://github.com/DanHutar/web-svepomoci/tree/main/prompty).

Podrobnosti: [Ověření funkcí](https://github.com/DanHutar/web-svepomoci/blob/main/docs/overeni.md).

Použijte přílohy **web-svepomoci-plugin.zip** a **web-svepomoci-sablona.zip**. **Source code (zip)** není instalační balíček WordPressu.
