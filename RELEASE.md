# Web svépomocí 1.7.0

- JavaScript uložený u stránky, Headeru a Footeru se načítá samostatnými odkazy. Ve zdroji HTML už není celý obsah polí JS.
- Způsob vkládání kódu, náhledy, revize, pořadí spouštění a oddělení skriptů zůstávají zachované. Kód není potřeba znovu ukládat.
- Načítají se pouze skripty zobrazených a zapnutých částí. Koncepty, soukromé stránky a stránky zamčené heslem svůj JavaScript anonymnímu návštěvníkovi neposkytnou ani přes staré odkazy.
- Změna kódu mění verzi odkazu. Privilegované odpovědi a stránky s heslem se neukládají do cache; veřejné odpovědi ověřují aktuálnost pomocí ETag.
- Dosavadní samostatné CSS, odstraňování sousedních duplicit CSS a omezení nepotřebných stylů WordPressu zůstávají zachované.

Od verze 1.2.0 aktualizujte běžným tlačítkem ve WordPressu; kontrolu vyvoláte přes **Web svépomocí → Zkontrolovat aktualizace**. Ze starších verzí nahrajte instalační ZIPy ručně a potvrďte nahrazení. Po aktualizaci vymažte případnou cache webu.

Úprava zkracuje zdroj HTML, není ochranou proti kopírování. JavaScript zůstává dostupný v nástrojích prohlížeče. Původní syntaxe a komentáře se zachovávají; nejde o minifikaci ani šifrování.

Podrobnosti: [JavaScript mimo zdroj HTML](https://github.com/DanHutar/web-svepomoci/blob/main/docs/javascript.md).

Použijte přílohy **web-svepomoci-plugin.zip** a **web-svepomoci-sablona.zip**. **Source code (zip)** není instalační balíček WordPressu.
