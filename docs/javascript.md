# JavaScript mimo zdroj HTML

Od verze 1.7.0 vkládejte JavaScript stejně jako dříve do pole **Chování (JS)** stránky, Headeru nebo Footeru, bez značek `<script>`. Po uložení se na veřejném webu načte samostatným odkazem místo celého kódu vloženého do HTML. Starý obsah není potřeba převádět ani znovu ukládat.

Každá zobrazená část s neprázdným JS má vlastní skript. Pořadí zůstává Header → Footer → stránka, na konci dokumentu a před událostí `DOMContentLoaded`. Samostatné soubory zachovávají dosavadní izolaci: syntaktická chyba v jedné části nebrání načtení ostatních. Prázdné, skryté nebo vypnuté části se nenačítají. Optimalizační pluginy, které skripty odkládají nebo slučují, mohou pořadí změnit; případně vyjměte odkazy s parametrem `aiwp_script` z těchto úprav.

Adresy skriptů zachovávají kontext původní stránky a používají parametr `aiwp_script=header`, `footer` nebo `page`. Při změně kódu se změní také verze v odkazu. Starý odkaz vrací aktuální povolený obsah; po změně stránky na koncept nebo soukromou stránku její kód neposkytne. Stránka chráněná heslem zpřístupní svůj JS až po zadání hesla. Odpovědi přihlášeným uživatelům, náhledy a stránky s heslem se neukládají do cache. Nevytvářejí se veřejné kopie na disku.

Editor, náhled a revize ponechávají původní čitelný kód. Ani veřejná odpověď kód neminifikuje: zachovává řetězce, regulární výrazy, komentáře a zalomení řádků, které mohou ovlivnit chování JavaScriptu.

Ve **Zobrazit zdroj stránky** uvidíte odkaz na skript. Jeho obsah zůstává dostupný v prohlížeči — jde o přesun z HTML, nikoli utajení či šifrování. Hesla, tajné API klíče a soukromá logika do JavaScriptu prohlížeče nepatří. HTML atributy, komentáře a skripty jiných pluginů tato funkce neupravuje.

Po aktualizaci pluginu a šablony na 1.7.0 vymažte případnou cache webu. Ověřte menu, tlačítka, animace a rozbalovací prvky na počítači i telefonu.
