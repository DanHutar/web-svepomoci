# Web svépomocí 1.5.0

- Uložené společné CSS a styly aktuální stránky, Headeru a Footeru se načítají samostatně. V HTML je odkaz místo celého kódu těchto stylů.
- Veřejná podoba CSS se šetrně zmenšuje a odstraňuje text komentářů. Původní čitelný kód zůstává v administraci, revizích a zadáních pro AI.
- Změna CSS mění otisk v adrese. Staré odkazy načítají aktuální povolený obsah; přístup ke stylům konceptů, soukromých stránek a stránek s heslem vychází z oprávnění WordPressu.
- Relativní URL obrázků zachovávají cestu původní stránky. Pořadí společných a místních stylů i náhled editoru zůstávají zachované.

Od verze 1.2.0 aktualizujte běžným tlačítkem ve WordPressu; kontrolu vyvoláte přes **Web svépomocí → Zkontrolovat aktualizace**. Ze starších verzí nahrajte instalační ZIPy ručně a potvrďte nahrazení. Po aktualizaci vymažte případnou cache webu.

Samostatné načítání CSS není šifrování ani ochrana proti kopírování. Prohlížeč stále dostává potřebné styly, které lze zobrazit v jeho nástrojích. Nic není potřeba přesouvat mezi poli administrace. Styly vložené WordPressem nebo jinými pluginy tato změna neupravuje.

Podrobnosti: [Společný vzhled a načítání CSS](https://github.com/DanHutar/web-svepomoci/blob/main/docs/spolecny-vzhled.md).

Použijte přílohy **web-svepomoci-plugin.zip** a **web-svepomoci-sablona.zip**. **Source code (zip)** není instalační balíček WordPressu.
