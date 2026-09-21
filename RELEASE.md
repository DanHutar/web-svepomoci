# Web svépomocí 1.3.0

- Výběr osmi veřejných Google Fontů ve **Web svépomocí → Vzhled webu**: Inter, Roboto, Open Sans, Montserrat, Nunito Sans, Source Sans 3, Lora a Merriweather.
- Tlačítko **Zkopírovat zadání pro společné CSS** doplní vybraný font, barvu, šířku a současné CSS. AI navrhne `clamp()` pro velikost písma i výšku řádků H1–H6, body a small podle fontu.
- Zadání u stránky, Headeru a Footeru obsahuje uložený společný vzhled, aby AI používala stejné třídy a typografii.
- Rychlý náhled používá vybraný Google Font i společné CSS ve stejných obalech jako veřejná stránka.
- Nová sada promptů: [Společný vzhled](https://github.com/DanHutar/web-svepomoci/blob/main/docs/spolecny-vzhled.md) a [stránky, Header, Footer a úpravy](https://github.com/DanHutar/web-svepomoci/blob/main/docs/zadani-pro-ai.md).

Z verze 1.2.0 aktualizujte běžným tlačítkem ve WordPressu; kontrolu vyvoláte přes **Web svépomocí → Zkontrolovat aktualizace**. Ze starších verzí nahrajte instalační ZIPy ručně a potvrďte nahrazení.

Obsah a společné CSS zůstávají zachované. Volba fontu sama nepřepisuje typografickou stupnici: tu připraví AI podle zadání a vložíte ji do Společného CSS. Vlastní font-family nebo velikosti ve starém CSS stránek mohou mít přednost. Google Font se načítá ze serverů Googlu; původní systémová písma bez stahování zůstávají dostupná.

Použijte přílohy **web-svepomoci-plugin.zip** a **web-svepomoci-sablona.zip**. **Source code (zip)** není instalační balíček WordPressu.
