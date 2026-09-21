<?php
/**
 * Beginner-facing editors for pages and shared template parts.
 *
 * @package AIWebStudio
 */

defined( 'ABSPATH' ) || exit;

final class AIWP_Admin {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function meta_boxes() {
		add_meta_box( 'aiwp-studio', 'web-svepomoci-plugin · Obsah stránky', array( __CLASS__, 'editor' ), 'page', 'normal', 'high' );
		add_meta_box( 'aiwp-studio', 'web-svepomoci-plugin · Společná část webu', array( __CLASS__, 'editor' ), 'aiwp_part', 'normal', 'high' );
		add_meta_box( 'aiwp-seo', 'SEO · Vyhledávání a sdílení', array( __CLASS__, 'seo' ), 'page', 'normal', 'default' );
	}

	public static function assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! in_array( $screen->post_type, array( 'page', 'aiwp_part' ), true ) ) {
			return;
		}
		wp_enqueue_style( 'aiwp-admin', AIWP_URL . 'assets/admin.css', array(), AIWP_VERSION );
		if ( ! aiwp_can_edit_code() ) {
			return;
		}
		$editors = array();
		foreach ( array( 'html' => 'text/html', 'css' => 'text/css', 'js' => 'application/javascript' ) as $key => $mime ) {
			$editors[ $key ] = wp_enqueue_code_editor( array( 'type' => $mime, 'codemirror' => array( 'lineNumbers' => true, 'lineWrapping' => true, 'indentUnit' => 2, 'tabSize' => 2, 'lint' => false ) ) );
		}
		$dependencies = array( 'jquery' );
		if ( false !== $editors['html'] ) {
			$dependencies[] = 'code-editor';
		}
		wp_enqueue_media();
		wp_enqueue_script( 'aiwp-admin', AIWP_URL . 'assets/admin.js', $dependencies, AIWP_VERSION, true );
		wp_localize_script( 'aiwp-admin', 'aiwpAdmin', array(
			'editors'      => $editors,
			'settings'     => aiwp_get_settings(),
			'siteName'     => get_bloginfo( 'name' ),
			'siteUrl'      => home_url( '/' ),
			'menuPreviews' => array( 'primary' => aiwp_render_menu( 'primary' ), 'footer' => aiwp_render_menu( 'footer' ) ),
		) );
	}

	public static function editor( $post ) {
		if ( ! aiwp_can_edit_code() ) {
			echo '<p>Úpravy kódu jsou dostupné správci webu s oprávněním vkládat HTML a JavaScript.</p>';
			return;
		}
		$data    = aiwp_get_document( $post->ID );
		$is_page = 'page' === $post->post_type;
		$kind    = $is_page ? 'page' : ( (int) $post->ID === aiwp_get_part_id( 'footer' ) ? 'footer' : 'header' );
		$menu_location = 'footer' === $kind ? 'footer' : 'primary';
		$menu_label    = 'footer' === $kind ? 'Menu v patičce' : 'Hlavní menu';
		wp_nonce_field( 'aiwp_save', 'aiwp_nonce' );
		?>
		<div class="aiwp-studio" data-aiwp-kind="<?php echo esc_attr( $kind ); ?>">
			<div class="aiwp-intro">
				<span class="aiwp-eyebrow">OD NÁPADU K VLASTNÍMU WEBU</span>
				<h3>Váš nápad. Kód od AI. Váš web.</h3>
				<p>Zkopírujte zadání pro AI, doplňte svou představu a vložte výsledek do tří polí. Potom zkontrolujte náhled a uložte změny.</p>
				<div class="aiwp-actions">
					<button type="button" class="button button-primary" data-aiwp-copy-prompt>Zkopírovat zadání pro AI <span aria-hidden="true">↗</span></button>
					<button type="button" class="button" data-aiwp-sample>Vložit ukázkový obsah</button>
				</div>
				<p class="aiwp-status" data-aiwp-action-status role="status" aria-live="polite"></p>
				<details class="aiwp-prompt-fallback" data-aiwp-prompt-fallback hidden><summary>Zadání pro ruční zkopírování</summary><textarea readonly rows="9" aria-label="Zadání pro AI k ručnímu zkopírování"></textarea></details>
			</div>
			<div class="aiwp-mode">
				<label class="aiwp-toggle"><input type="checkbox" name="aiwp[enabled]" id="aiwp-enabled" value="1" <?php checked( ! empty( $data['enabled'] ) ); ?>><span><strong><?php echo $is_page ? 'Zobrazovat obsah z AI editoru' : 'Používat tuto vlastní část webu'; ?></strong><small><?php echo $is_page ? 'Po zapnutí se na stránce zobrazí HTML, CSS a JavaScript níže. Běžný editor WordPressu zůstává zachovaný.' : 'Zapněte až po dokončení obsahu a uložte. Změna se použije společně na celém webu.'; ?></small></span></label>
			</div>
			<div class="aiwp-workspace">
				<?php if ( ! $is_page ) : ?>
					<div class="aiwp-menu-help">
						<strong>Odkazy spravujte ve WordPressu</strong>
						<p>Ve <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" target="_blank" rel="noopener">Vzhled → Menu (nová karta)</a> vytvořte menu, přidejte stránky a přiřaďte ho do umístění <strong><?php echo esc_html( $menu_label ); ?></strong>. Změny odkazů se pak na webu projeví automaticky.</p>
						<p>Do HTML vložte <code><?php echo esc_html( '[aiwp_menu location="' . $menu_location . '"]' ); ?></code>, ideálně dovnitř značky <code>&lt;nav&gt;</code>. Nahraďte jím původní ručně psané odkazy; značka se na webu změní na seznam odkazů.</p>
						<button type="button" class="button" data-aiwp-insert-menu data-aiwp-location="<?php echo esc_attr( $menu_location ); ?>">Vložit menu z WordPressu</button>
						<p class="description">Tlačítko vloží značku na místo kurzoru nebo označeného textu v HTML. Potom klikněte na Aktualizovat. Po změně menu ve Vzhled → Menu znovu načtěte tento editor, aby se aktualizovaly odkazy v náhledu.</p>
						<?php if ( ! has_nav_menu( $menu_location ) ) : ?>
							<p class="aiwp-menu-notice">K umístění „<?php echo esc_html( $menu_label ); ?>“ zatím není přiřazené menu. Nejdříve ho vyberte a uložte ve Vzhled → Menu; do té doby se odkazy na webu nezobrazí.</p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<p class="aiwp-mode-status<?php echo empty( $data['enabled'] ) ? ' is-disabled' : ''; ?>" data-aiwp-mode-status role="status"><?php echo empty( $data['enabled'] ) ? 'Použití vlastního kódu je vypnuté. Pro zobrazení na webu ho zapněte a stránku uložte.' : 'Použití vlastního kódu je zapnuté. Změny se na web projeví po uložení nebo publikování.'; ?></p>
				<div class="aiwp-tabs" role="tablist" aria-label="Druh kódu">
					<?php foreach ( array( 'html' => array( 'HTML', 'Obsah' ), 'css' => array( 'CSS', 'Vzhled' ), 'js' => array( 'JS', 'Chování' ) ) as $key => $labels ) : ?>
						<button type="button" id="aiwp-tab-<?php echo esc_attr( $key ); ?>" class="aiwp-tab<?php echo 'html' === $key ? ' is-active' : ''; ?>" role="tab" aria-controls="aiwp-panel-<?php echo esc_attr( $key ); ?>" aria-selected="<?php echo 'html' === $key ? 'true' : 'false'; ?>" tabindex="<?php echo 'html' === $key ? '0' : '-1'; ?>" data-aiwp-tab="<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $labels[0] ); ?></strong><span><?php echo esc_html( $labels[1] ); ?></span><span class="aiwp-filled" data-aiwp-filled="<?php echo esc_attr( $key ); ?>" aria-label="Pole obsahuje kód" <?php echo empty( $data[ $key ] ) ? 'hidden' : ''; ?>></span></button>
					<?php endforeach; ?>
				</div>
				<?php
				$hints = array( 'html' => 'Vložte pouze obsah stránky, například sekce, nadpisy a odstavce. Bez značek html, head, body, style a script. PHP se nevkládá.', 'css' => 'Vložte CSS bez značek <style>. Použijte vlastní třídy, například .moje-stranka, aby se vzhled neprolínal s ostatními částmi webu.', 'js' => 'Nepovinné. Vložte JavaScript bez značek <script>. Pro běžnou stránku s texty a obrázky může toto pole zůstat prázdné.' );
				foreach ( $hints as $key => $hint ) :
					?>
					<div class="aiwp-code-panel" id="aiwp-panel-<?php echo esc_attr( $key ); ?>" role="tabpanel" aria-labelledby="aiwp-tab-<?php echo esc_attr( $key ); ?>">
						<p class="aiwp-field-hint" id="aiwp-hint-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $hint ); ?></p>
						<label class="screen-reader-text" for="aiwp-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( strtoupper( $key ) . ' kód' ); ?></label>
						<textarea id="aiwp-<?php echo esc_attr( $key ); ?>" name="aiwp[<?php echo esc_attr( $key ); ?>]" class="aiwp-code" rows="16" spellcheck="false" autocomplete="off" autocapitalize="off" aria-describedby="aiwp-hint-<?php echo esc_attr( $key ); ?>" data-aiwp-code="<?php echo esc_attr( $key ); ?>"><?php echo esc_textarea( $data[ $key ] ); ?></textarea>
					</div>
				<?php endforeach; ?>
				<div class="aiwp-editor-foot"><span>Vkládejte samotný kód bez ohraničení ```.</span><span data-aiwp-code-status role="status" aria-live="polite"></span></div>
			</div>
			<div class="aiwp-preview">
				<div class="aiwp-preview-toolbar"><div><h4>Náhled obsahu</h4><p data-aiwp-preview-status role="status" aria-live="polite">Připraveno k prvnímu náhledu.</p></div><div class="aiwp-preview-controls"><div class="aiwp-device-group" role="group" aria-label="Šířka náhledu"><button type="button" class="button is-active" data-aiwp-device="desktop" aria-pressed="true">Počítač</button><button type="button" class="button" data-aiwp-device="mobile" aria-pressed="false">Mobil</button></div><button type="button" class="button button-primary" data-aiwp-refresh>Obnovit náhled</button></div></div>
				<div class="aiwp-preview-stage" data-aiwp-preview-stage><div class="aiwp-preview-empty" data-aiwp-preview-empty><span aria-hidden="true">◇</span><strong>Tady uvidíte svůj návrh</strong><p>Po vložení kódu klikněte na „Obnovit náhled“.</p></div><iframe title="Izolovaný náhled právě vloženého obsahu" class="aiwp-preview-frame" sandbox="allow-scripts" referrerpolicy="no-referrer" hidden></iframe></div>
				<p class="aiwp-preview-note">Tento náhled neukládá změny. Zobrazuje jen právě upravovaný obsah; odkazy, formuláře a externí skripty jsou omezené. Pro náhled celého webu nejprve uložte koncept nebo aktualizujte stránku a pak použijte tlačítko Náhled ve WordPressu.</p>
			</div>
			<?php if ( $is_page ) : ?>
				<details class="aiwp-page-options"><summary>Rozložení této stránky</summary><div><label><input type="checkbox" name="aiwp[hide_header]" value="1" <?php checked( ! empty( $data['hide_header'] ) ); ?>> Skrýt společnou hlavičku</label><label><input type="checkbox" name="aiwp[hide_footer]" value="1" <?php checked( ! empty( $data['hide_footer'] ) ); ?>> Skrýt společnou patičku</label><p class="description">Vhodné například pro samostatnou prodejní stránku. Tuto volbu podporuje šablona web-svepomoci-sablona.</p></div></details>
			<?php endif; ?>
			<div class="aiwp-save-reminder"><span class="dashicons dashicons-saved" aria-hidden="true"></span><span>Hotovo? Použijte <strong>Uložit koncept</strong>, <strong>Publikovat</strong> nebo <strong>Aktualizovat</strong> ve WordPressu. Předchozí uložené verze najdete v revizích.</span></div>
			<noscript><p>Pro zvýraznění kódu, přepínání záložek a náhled zapněte JavaScript v prohlížeči. Kód lze i bez něj vložit do tří textových polí a uložit.</p></noscript>
		</div>
		<?php
	}

	public static function seo( $post ) {
		if ( ! aiwp_can_edit_code() ) {
			echo '<p>Nastavení SEO v pluginu web-svepomoci-plugin upravuje správce webu.</p>';
			return;
		}
		$data = aiwp_get_document( $post->ID );
		?>
		<div class="aiwp-seo-fields">
			<p class="aiwp-section-lead">Pomozte lidem poznat, co na stránce najdou. Prázdný SEO titulek se doplní z názvu stránky.</p>
			<div class="aiwp-field"><label for="aiwp-seo-title">SEO titulek</label><input type="text" id="aiwp-seo-title" name="aiwp[seo_title]" value="<?php echo esc_attr( $data['seo_title'] ); ?>" placeholder="<?php echo esc_attr( get_the_title( $post ) ); ?>" aria-describedby="aiwp-seo-title-hint"><p class="description" id="aiwp-seo-title-hint"><span data-aiwp-counter="aiwp-seo-title">0</span> znaků · Obvykle se hodí přibližně 50–60 znaků; nejdůležitější sdělení dejte na začátek.</p></div>
			<div class="aiwp-field"><label for="aiwp-seo-description">Meta popis</label><textarea id="aiwp-seo-description" name="aiwp[seo_description]" rows="3" aria-describedby="aiwp-seo-description-hint"><?php echo esc_textarea( $data['seo_description'] ); ?></textarea><p class="description" id="aiwp-seo-description-hint"><span data-aiwp-counter="aiwp-seo-description">0</span> znaků · Stručně popište přínos stránky. Přibližně 140–160 znaků je dobrý výchozí bod, nikoli pevný limit.</p></div>
			<div class="aiwp-field"><label for="aiwp-seo-image">Obrázek pro sdílení</label><div class="aiwp-input-action"><input type="url" id="aiwp-seo-image" name="aiwp[seo_image]" value="<?php echo esc_attr( $data['seo_image'] ); ?>" placeholder="https://…/obrazek.jpg"><button type="button" class="button" data-aiwp-media>Vybrat z médií</button></div><p class="description">Adresa obrázku pro náhled odkazu na sociálních sítích. Můžete vložit URL nebo vybrat obrázek z knihovny.</p></div>
			<details class="aiwp-seo-advanced"><summary>Pokročilé nastavení</summary><div class="aiwp-field"><label for="aiwp-seo-canonical">Kanonická URL</label><input type="url" id="aiwp-seo-canonical" name="aiwp[seo_canonical]" value="<?php echo esc_attr( $data['seo_canonical'] ); ?>" placeholder="Automaticky: adresa této stránky"><p class="description">Vyplňte pouze, pokud má vyhledávač považovat jinou adresu za hlavní verzi stejného obsahu.</p></div><label class="aiwp-checkbox-line"><input type="checkbox" name="aiwp[seo_noindex]" value="1" <?php checked( ! empty( $data['seo_noindex'] ) ); ?>> Požádat vyhledávače, aby tuto stránku neindexovaly (noindex)</label><p class="description">Stránka zůstane veřejně dostupná. Toto nastavení ji nechrání heslem.</p></details>
			<p class="aiwp-seo-note">Vyhledávače mohou zvolit jiný titulek nebo popis. Pokud používáte podporovaný SEO plugin, metadata spravujte v něm — web-svepomoci-plugin mu přenechá jejich výpis.</p>
		</div>
		<?php
	}
}
