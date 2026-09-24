# Automatický převod obrázků na WebP

Od verze 1.8.0 převádí aktivní šablona nově nahrané JPEG a běžné PNG na WebP. Funguje v knihovně médií i při importu přes standardní nahrávací funkce WordPressu a nepotřebuje doprovodný plugin. V administraci se nic nezapíná. Existující média ani jejich odkazy se hromadně nepřepisují.

Šablona používá obrázkový editor WordPressu a jeho podporu WebP. Pokud server převod nepodporuje, obrázek je poškozený nebo zápis selže, nahrávání ponechá původní soubor. Kontroluje se také skutečný typ obrázku. JPEG se před převodem otočí podle EXIF, pokud tuto informaci editor dokáže přečíst. Průhlednost PNG se zachovává. WordPress následně vytvoří své běžné náhledové velikosti z WebP.

GIFy se nepřevádějí, aby se neztratila animace; totéž platí pro animované PNG (APNG). WebP, SVG, AVIF, PDF a další formáty tato funkce nemění. Nejde o převod obrázků vložených pouhým externím odkazem ani o automatické nahrazování adres v ručně napsaném HTML.

Po úspěšném uložení a kontrole WebP se původní JPEG/PNG odstraní, stejně jako v původním dodaném kódu. Originály pro archivaci proto uchovávejte také mimo web. Při chybě se odstraní pouze rozpracovaný nový výstup a původní soubor zůstane. Existující stejnojmenné WebP se nepřepisuje: použije se volný název. Pokud server nepovolí smazání originálu, může vedle WebP zůstat i původní soubor.

Výsledná velikost není zaručeně menší u každého obrázku. Kvalitu komprese určuje obrázkový editor WordPressu a jeho standardní filtry. Při úspěšném převodu se mohou odstranit původní EXIF/IPTC informace; nejde o archivní kopii fotografie.

Po aktualizaci vyzkoušejte nahrát jednu fotografii a jedno průhledné PNG. V detailu média má výsledná adresa končit `.webp`. Předchozí kopii stejné funkce odstraňte z vlastních úryvků kódu, aby se při nahrávání nespouštěly dvě různé konverze.

Technické podklady: [podpora formátů v editoru WordPressu](https://developer.wordpress.org/reference/functions/wp_image_editor_supports/) a [otočení podle EXIF](https://developer.wordpress.org/reference/classes/wp_image_editor/maybe_exif_rotate/).
