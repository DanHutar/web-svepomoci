# Web svépomocí 1.8.0

- Šablona převádí nově nahrané JPEG a běžné PNG na WebP přes obrázkový editor WordPressu. Doprovodný plugin k převodu není potřeba.
- Zachovává průhlednost PNG, upravuje otočení JPEG podle dostupných EXIF údajů a nepřepisuje stejnojmenné soubory.
- GIFy a animované PNG se nepřevádějí. Existující média a ostatní formáty zůstávají beze změn.
- Při nepodporovaném serveru nebo chybě převodu se ponechá originál. Původní JPEG/PNG se odstraní až po úspěšném uložení a kontrole výsledného WebP; neúplný výstup se při chybě uklidí.
- WordPress u nového média používá WebP adresu a MIME typ a vytváří obvyklé náhledy. Dosavadní funkce editoru, samostatného CSS a JavaScriptu zůstávají zachované.

Od verze 1.2.0 aktualizujte běžným tlačítkem ve WordPressu; kontrolu vyvoláte přes **Web svépomocí → Zkontrolovat aktualizace**. Ze starších verzí nahrajte instalační ZIPy ručně a potvrďte nahrazení. Po aktualizaci vymažte případnou cache webu.

Originály pro archivaci uchovávejte také mimo web. Převod může odstranit EXIF/IPTC informace a není zaručeno, že každý výsledný soubor bude menší. Předchozí kopii konverzní funkce odstraňte z vlastních úryvků kódu, aby se nespouštěly dvě konverze.

Podrobnosti: [Automatický převod na WebP](https://github.com/DanHutar/web-svepomoci/blob/main/docs/obrazky-webp.md).

Použijte přílohy **web-svepomoci-plugin.zip** a **web-svepomoci-sablona.zip**. **Source code (zip)** není instalační balíček WordPressu.
