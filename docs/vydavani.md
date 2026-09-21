# Vydávání aktualizací

Projekt se publikuje do veřejného repozitáře [DanHutar/web-svepomoci](https://github.com/DanHutar/web-svepomoci). Celý obsah této složky je kořenem repozitáře. `node_modules`, lokální testovací výsledky a sestavené ZIPy se do Gitu nepřidávají.

## Nová verze

1. Změňte kód a zvyšte stabilní verzi ve formátu `1.2.1` v hlavičce i konstantě `AIWP_VERSION` v `plugin/ai-web-studio/ai-web-studio.php`, v hlavičce `theme/ai-web/style.css` a v `package.json` / `package-lock.json`. Obě součásti vydáváme se stejným číslem.
2. Popište změny v `RELEASE.md`. Při změně požadavků upravte hlavičky `Requires at least` a `Requires PHP`; balení je automaticky vloží do manifestu.
3. Pokud upravujete `includes/github-updates.php`, udržujte totožný soubor v pluginu i šabloně. Balení odmítne rozdílné kopie.
4. Spusťte `npm ci`, `powershell -NoProfile -ExecutionPolicy Bypass -File ./tools/package.ps1`, `npm run test:updates` a podle změny také `npm test` / `npm run test:menu`. Na systémech s PowerShell 7 použijte `pwsh -File ./tools/package.ps1`.
5. Nahrajte změny do větve `main`. Workflow **Test and release** sestaví oba ZIPy, spustí test aktualizací ve WordPressu a po úspěchu vytvoří stabilní vydání s tagem `v1.2.1` a třemi přílohami: `web-svepomoci-plugin.zip`, `web-svepomoci-sablona.zip`, `updates.json`.

Workflow používá krátkodobý `GITHUB_TOKEN` poskytovaný GitHub Actions; vlastní tajné klíče se nenastavují. Publikované vydání se stejným číslem nepřepisuje. Další změny vydávejte pod vyšší verzí. Při neúspěšném nahrání může zůstat koncept vydání; zkontrolujte log Actions a koncept před novým pokusem dokončete nebo odstraňte. Neúplné, konceptové a předběžné verze WordPress nepoužije.

Po vydání otevřete na testovacím webu **Web svépomocí → Zkontrolovat aktualizace** a aktualizujte plugin i šablonu. Nastavení automatické instalace je na správci daného WordPressu.

## Kompatibilita s původní instalací

Názvy produktu a ZIPů jsou nové. Uvnitř ZIPů zůstávají složky `ai-web-studio` a `ai-web`, stejně jako hlavní soubor pluginu, identifikátory menu, databázová pole a nastavení. WordPress tak pozná aktualizaci existujících součástí. Složky ručně nepřejmenovávejte. Úpravy obsahu v administraci přežijí aktualizaci; ruční úpravy souborů pluginu či rodičovské šablony aktualizace nahrazuje.

Aktualizační mechanismus používá oficiální filtry WordPressu pro [pluginy](https://developer.wordpress.org/reference/hooks/update_plugins_hostname/) a [šablony](https://developer.wordpress.org/reference/hooks/update_themes_hostname/) a [GitHub Releases API](https://docs.github.com/en/rest/releases/releases#get-the-latest-release).
