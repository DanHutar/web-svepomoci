# Zadání pro AI přímo ve WordPressu

Od verze **1.4.0** najdete přípravu zadání v nabídce **Web svépomocí → Zadání pro AI**. Vyberete, co chcete vytvořit, a vlastními slovy popíšete požadavek. WordPress doplní potřebné údaje o vašem webu a pravidla pro kód.

Žádný AI účet ani API klíč se nepropojuje. Hotové zadání vložíte do své AI a její odpověď potom ručně vložíte do WordPressu.

## Postup

1. Nejprve uložte rozpracované změny ve **Vzhled webu** a v editorech stránek, Headeru nebo Footeru. Zadání používá uložený stav, nikoli rozepsané změny v jiném okně.
2. Otevřete **Web svépomocí → Zadání pro AI** a vyberte druh zadání z tabulky níže.
3. Popište, co potřebujete. Například: „Vytvoř stránku Kontakt pro můj ateliér. Adresa je …, e-mail je …, otevírací doba je … Použij společný vzhled webu.“ U úpravy také vyhledejte a vyberte existující stránku.
4. Klikněte na **Připravit zadání**. Prohlédněte si text i pokyny, kam potom vložit výsledek.
5. Klikněte na **Zkopírovat zadání** a vložte je do své AI. Pokud prohlížeč kopírování nepovolí, označte text v poli a zkopírujte jej pomocí Ctrl+C; na Macu pomocí Cmd+C.
6. Odpověď od AI vložte do odpovídajících polí podle tabulky. Nekopírujte značky ohraničující blok kódu ani vysvětlení kolem něj.
7. Uložte změny a zkontrolujte výsledek v náhledu WordPressu i na skutečné stránce.

Změníte-li druh zadání, popis nebo vybranou stránku, vytvořte zadání znovu. Původní text už neodpovídá novému požadavku.

| Co vybrat | Co se do zadání doplní | Kam vložit výsledek AI |
| --- | --- | --- |
| **Společný vzhled a typografie** | Uložený font, barva, šířka a dosavadní společné CSS; pravidla pro `clamp()` velikostí a řádkování H1–H6, běžného textu a small | Celý blok CSS do **Web svépomocí → Vzhled webu → Společné CSS** |
| **Nová stránka** | Uložený společný vzhled a pravidla pro obsah stránky | Vytvořte stránku, zapněte vlastní kód a vložte zvlášť **HTML**, **CSS** a **JS** |
| **Header — hlavička webu** | Společný vzhled a uložený kód hlavičky, pokud už existuje; pravidla pro menu z WordPressu | Do polí **HTML**, **CSS** a **JS** v **Headeru** |
| **Footer — patička webu** | Společný vzhled a uložený kód patičky, pokud už existuje; pravidla pro menu z WordPressu | Do polí **HTML**, **CSS** a **JS** ve **Footeru** |
| **Úprava existující stránky** | Společný vzhled a uložený obsah právě vybrané stránky | Do polí **HTML**, **CSS** a **JS** této stránky; návrh SEO patří do jejích SEO polí |

Pokyny pod hotovým zadáním obsahují také odkaz na místo pro vložení výsledku. Hlavičku či patičku nezapomeňte zapnout a uložit, aby se na webu používala. Menu spravujte ve **Vzhled → Menu**; AI má v kódu ponechat příslušnou značku `[aiwp_menu location="primary"]` nebo `[aiwp_menu location="footer"]`.

V editoru najdete kód pod záložkami **Obsah (HTML)**, **Vzhled (CSS)** a **Chování (JS)**. SEO titulek a meta popis patří do samostatných SEO polí, ne do HTML. Když stránka skript nepotřebuje, JS může zůstat prázdný.

## Doporučené pořadí pro nový web

Ve **Vzhled webu** vyberte font, barvu a šířku a uložte je. Potom připravte zadání **Společný vzhled a typografie**. Do popisu napište zaměření webu, pro koho je určený a jak má působit. CSS od AI vložte do **Společného CSS** a uložte.

Teprve potom připravujte zadání pro jednotlivé stránky, Header a Footer. Budou vycházet ze stejného uloženého vzhledu. Společné CSS obsahuje styly pro celý web; CSS jednotlivé stránky má přidávat jen její odlišnosti. Více v [návodu ke společnému vzhledu](spolecny-vzhled.md).

## Úprava hotové stránky

Vyberte **Úprava existující stránky** a zvolte stránku ze seznamu. Pokud v něm není, napište její název a klikněte na **Vyhledat stránky**. Seznam zobrazuje nejvýše 20 výsledků; u většího webu zpřesněte hledání. Do popisu napište konkrétní změnu, například „Pod služby přidej sekci s těmito třemi cenami …, ostatní obsah zachovej.“ Nemusíte z jednotlivých polí ručně kopírovat uložený kód.

Pokud vyberete běžnou stránku bez zapnutého vlastního kódu, zadání obsahuje její standardní uložený obsah pro převod do polí Web svépomocí. Po vložení výsledku zkontrolujte, že AI zachovala potřebný obsah a funkce, zapněte vlastní kód a uložte stránku. Původní běžný obsah zůstává ve WordPressu; při aktivním vlastním kódu se nezobrazuje.

Zadání může obsahovat neveřejný text vybrané stránky nebo kontakt z uloženého kódu. Před vložením do externí AI si text projděte. Samotné vytvoření nebo zkopírování zadání nic neposílá AI ani nemění uloženou stránku.

Dosavadní tlačítka pro kopírování zadání ve **Vzhled webu** a editorech zůstávají dostupná. Ruční vzory najdete v [sadě zadání](zadani-pro-ai.md).
