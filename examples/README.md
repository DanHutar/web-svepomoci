# Ukázka: Ateliér Forma

Tři části jedné jednoduché české stránky: hlavička, hlavní obsah a patička. Žádné knihovny, externí fonty, obrázkové služby ani síťové požadavky. JavaScript je potřebný pouze pro mobilní menu hlavičky. Stránka a patička mají prázdný skript kromě vysvětlujícího komentáře.

| Kam vložit | HTML | CSS | JS |
| --- | --- | --- | --- |
| Header | `header.html` | `header.css` | `header.js` |
| Úprava úvodní stránky | `page.html` | `page.css` | `page.js` |
| Footer | `footer.html` | `footer.css` | `footer.js` |

Otevřete soubor jako text, zkopírujte jeho celý obsah a vložte jej do odpovídajícího pole v administraci. U stránky zapněte použití vlastního kódu; u hlavičky a patičky jejich vlastní použití.

Před zveřejněním nahraďte fiktivní název **Ateliér Forma**, popisy služeb a všechny výskyty `ahoj@example.com`. Kontaktní tlačítka jsou skutečné odkazy `mailto:`, které otevírají e-mailovou aplikaci návštěvníka. Nejde o kontaktní formulář.

Hlavička a patička vyžadují plugin **AI Web Studio 1.1.0** a používají menu ze **Vzhled → Menu**. Vytvořte hlavní menu a přiřaďte jej k umístění **Hlavní menu**. Přidejte své stránky nebo vlastní odkazy na sekce ukázkové úvodní stránky `/#sluzby` a `/#postup`. Pro patičku přiřaďte stejné nebo jiné menu k umístění **Menu v patičce**; může obsahovat kontakt nebo odkaz `mailto:` na váš e-mail. Menu bez položek nebo bez přiřazeného umístění nic nevypíše.

`header.html` obsahuje `[aiwp_menu location="primary"]` a `footer.html` obsahuje `[aiwp_menu location="footer"]`. Tyto značky ponechte v HTML. Odkazy a jejich pořadí pak upravujete pouze ve WordPressu, bez nového kopírování kódu. CSS podporuje nativní seznam `.aiwp-menu` a viditelné podnabídky `.sub-menu`. Při přechodu ze starších příkladů aktualizujte HTML i CSS; mobilní `header.js` zůstává stejný.

Stránku s kontakty nejprve vytvořte a potom přidejte do menu. Ukázkovou stránku nastavte jako statickou úvodní stránku webu, aby fungovaly odkazy na její sekce. Pokud web není v kořeni domény, opravte počáteční `/` v odkazech na značce „forma.“ a použijte skutečné adresy při vytváření vlastních odkazů v menu.

Styly jsou omezené na obaly `.sample-site-header`, `.sample-page` a `.sample-site-footer`. Ukázka si definuje vlastní barvy a šířku; pro jednotný vzhled je při změnách upravte ve všech třech souborech CSS. Nastavení globální barvy v pluginu tyto výslovně zadané hodnoty nepřepíše.

Rychlý náhled jedné části neuvidí zbylé dvě části. Výsledné složení ověřte v běžném náhledu uložené stránky WordPressu, také na mobilní šířce a pomocí klávesnice.

Menu v rychlém náhledu odpovídá stavu při otevření editoru. Po změně menu v jiné kartě nejprve uložte rozpracovaný kód a potom obnovte celou stránku editoru. Samotné obnovení náhledu stav menu nenačítá znovu.
