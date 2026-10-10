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
		add_meta_box( 'aiwp-studio', __( 'ByYourself Builder · Page content', 'ai-web-studio' ), array( __CLASS__, 'editor' ), 'page', 'normal', 'high' );
		add_meta_box( 'aiwp-studio', __( 'ByYourself Builder · Shared website part', 'ai-web-studio' ), array( __CLASS__, 'editor' ), 'aiwp_part', 'normal', 'high' );
		add_meta_box( 'aiwp-seo', __( 'SEO · Search and sharing', 'ai-web-studio' ), array( __CLASS__, 'seo' ), 'page', 'normal', 'default' );
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
		$dependencies = array( 'jquery', 'media-views' );
		if ( false !== $editors['html'] ) {
			$dependencies[] = 'code-editor';
		}
		wp_enqueue_media();
		wp_enqueue_script( 'aiwp-admin', AIWP_URL . 'assets/admin.js', $dependencies, AIWP_VERSION, true );
		wp_localize_script( 'aiwp-admin', 'aiwpAdmin', array(
			'editors'      => $editors,
			'settings'     => aiwp_get_settings(),
			'font'         => aiwp_font_details( aiwp_get_settings()['font'] ),
			'globalCss'    => aiwp_global_css(),
			'designContext' => aiwp_shared_design_context(),
			'siteName'     => get_bloginfo( 'name' ),
			'siteUrl'      => home_url( '/' ),
			'menuPreviews' => array( 'primary' => aiwp_render_menu( 'primary' ), 'footer' => aiwp_render_menu( 'footer' ) ),
			'contentLanguageInstruction' => aiwp_content_language_instruction(),
			'contentLanguage' => WSP_Languages::language( 'content' ),
		) );
	}

	public static function editor( $post ) {
		if ( ! aiwp_can_edit_code() ) {
			echo wp_kses_post( __( '<p>Code editing is available to administrators with permission to insert HTML and JavaScript.</p>', 'ai-web-studio' ) );
			return;
		}
		$data    = aiwp_get_document( $post->ID );
		$is_page = 'page' === $post->post_type;
		$kind    = $is_page ? 'page' : ( (int) $post->ID === aiwp_get_part_id( 'footer' ) ? 'footer' : 'header' );
		$menu_location = 'footer' === $kind ? 'footer' : 'primary';
		$menu_label    = 'footer' === $kind ? __( 'Footer menu', 'ai-web-studio' ) : __( 'Primary menu', 'ai-web-studio' );
		wp_nonce_field( 'aiwp_save', 'aiwp_nonce' );
		?>
		<div class="aiwp-studio" data-aiwp-kind="<?php echo esc_attr( $kind ); ?>">
			<div class="aiwp-intro">
				<span class="aiwp-eyebrow"><?php echo esc_html__( 'FROM AN IDEA TO YOUR OWN WEBSITE', 'ai-web-studio' ); ?></span>
				<h3><?php echo esc_html__( 'Your idea. AI code. Your website.', 'ai-web-studio' ); ?></h3>
				<p><?php echo esc_html__( 'Copy the AI prompt, add your requirements and paste the result into the three fields. Then check the preview and save your changes.', 'ai-web-studio' ); ?></p>
				<div class="aiwp-actions">
					<button type="button" class="button button-primary" data-aiwp-copy-prompt><?php echo esc_html__( 'Copy AI prompt', 'ai-web-studio' ); ?> <span aria-hidden="true">↗</span></button>
					<button type="button" class="button" data-aiwp-sample><?php echo esc_html__( 'Insert sample content', 'ai-web-studio' ); ?></button>
				</div>
				<p class="aiwp-status" data-aiwp-action-status role="status" aria-live="polite"></p>
				<details class="aiwp-prompt-fallback" data-aiwp-prompt-fallback hidden><summary><?php echo esc_html__( 'Prompt for manual copying', 'ai-web-studio' ); ?></summary><textarea readonly rows="9" aria-label="<?php echo esc_attr__( 'AI prompt for manual copying', 'ai-web-studio' ); ?>"></textarea></details>
			</div>
			<div class="aiwp-mode">
				<label class="aiwp-toggle"><input type="checkbox" name="aiwp[enabled]" id="aiwp-enabled" value="1" <?php checked( ! empty( $data['enabled'] ) ); ?>><span><strong><?php echo $is_page ? esc_html__( 'Display content from the AI editor', 'ai-web-studio' ) : esc_html__( 'Use this custom website part', 'ai-web-studio' ); ?></strong><small><?php echo $is_page ? esc_html__( 'When enabled, the page displays the HTML, CSS and JavaScript below. The standard WordPress content is preserved.', 'ai-web-studio' ) : esc_html__( 'Enable once the content is ready, then save. The change applies across the website.', 'ai-web-studio' ); ?></small></span></label>
			</div>
			<div class="aiwp-workspace">
				<?php if ( ! $is_page ) : ?>
					<div class="aiwp-menu-help">
						<strong><?php esc_html_e( 'Manage links in WordPress', 'ai-web-studio' ); ?></strong>
						<p><?php esc_html_e( 'In', 'ai-web-studio' ); ?> <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Appearance → Menus (new tab)', 'ai-web-studio' ); ?></a> <?php echo esc_html__( 'create a menu, add pages and assign it to the location', 'ai-web-studio' ); ?> <strong><?php echo esc_html( $menu_label ); ?></strong><?php echo esc_html__( '. Changes to links will then appear on the website automatically.', 'ai-web-studio' ); ?></p>
						<p><?php echo esc_html__( 'Insert into HTML', 'ai-web-studio' ); ?> <code><?php echo esc_html( '[aiwp_menu location="' . $menu_location . '"]' ); ?></code><?php echo esc_html__( ', ideally inside a', 'ai-web-studio' ); ?> <code>&lt;nav&gt;</code><?php echo esc_html__( ' element. Replace the manually written links with this marker; it will become a list of links on the website.', 'ai-web-studio' ); ?></p>
						<button type="button" class="button" data-aiwp-insert-menu data-aiwp-location="<?php echo esc_attr( $menu_location ); ?>"><?php echo esc_html__( 'Insert WordPress menu', 'ai-web-studio' ); ?></button>
						<p class="description"><?php echo esc_html__( 'This button inserts the marker at the cursor or replaces the selected HTML. Then click Update. After changing a menu in Appearance → Menus, reload this editor to refresh the preview links.', 'ai-web-studio' ); ?></p>
						<?php if ( ! has_nav_menu( $menu_location ) ) : ?>
							<p class="aiwp-menu-notice"><?php echo esc_html__( 'No menu is assigned to “', 'ai-web-studio' ); ?><?php echo esc_html( $menu_label ); ?><?php echo esc_html__( '” yet. Select and save a menu in Appearance → Menus first; links will not appear until then.', 'ai-web-studio' ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<p class="aiwp-mode-status<?php echo empty( $data['enabled'] ) ? ' is-disabled' : ''; ?>" data-aiwp-mode-status role="status"><?php echo empty( $data['enabled'] ) ? esc_html__( 'Custom code is disabled. Enable it and save the page to show it on the website.', 'ai-web-studio' ) : esc_html__( 'Custom code is enabled. Save or publish to apply your changes to the website.', 'ai-web-studio' ); ?></p>
				<div class="aiwp-tabs" role="tablist" aria-label="<?php echo esc_attr__( 'Code type', 'ai-web-studio' ); ?>">
					<?php foreach ( array( 'html' => array( 'HTML', __( 'Content', 'ai-web-studio' ) ), 'css' => array( 'CSS', __( 'Design', 'ai-web-studio' ) ), 'js' => array( 'JS', __( 'Behaviour', 'ai-web-studio' ) ) ) as $key => $labels ) : ?>
						<button type="button" id="aiwp-tab-<?php echo esc_attr( $key ); ?>" class="aiwp-tab<?php echo 'html' === $key ? ' is-active' : ''; ?>" role="tab" aria-controls="aiwp-panel-<?php echo esc_attr( $key ); ?>" aria-selected="<?php echo 'html' === $key ? 'true' : 'false'; ?>" tabindex="<?php echo 'html' === $key ? '0' : '-1'; ?>" data-aiwp-tab="<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $labels[0] ); ?></strong><span><?php echo esc_html( $labels[1] ); ?></span><span class="aiwp-filled" data-aiwp-filled="<?php echo esc_attr( $key ); ?>" aria-label="<?php echo esc_attr__( 'This field contains code', 'ai-web-studio' ); ?>" <?php echo empty( $data[ $key ] ) ? 'hidden' : ''; ?>></span></button>
					<?php endforeach; ?>
				</div>
				<?php
				$hints = array( 'html' => __( 'Insert only page content, such as sections, headings and paragraphs. No html, head, body, style or script tags. PHP is not supported.', 'ai-web-studio' ), 'css' => __( 'Insert CSS without <style> tags. Use your own classes, such as .sample-page, to avoid affecting other website elements.', 'ai-web-studio' ), 'js' => __( 'Optional. Insert JavaScript without <script> tags. For a simple page with text and images, this field can stay empty.', 'ai-web-studio' ) );
				foreach ( $hints as $key => $hint ) :
					?>
					<div class="aiwp-code-panel" id="aiwp-panel-<?php echo esc_attr( $key ); ?>" role="tabpanel" aria-labelledby="aiwp-tab-<?php echo esc_attr( $key ); ?>">
						<p class="aiwp-field-hint" id="aiwp-hint-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $hint ); ?></p>
						<label class="screen-reader-text" for="aiwp-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( strtoupper( $key ) . __( ' code', 'ai-web-studio' ) ); ?></label>
						<textarea id="aiwp-<?php echo esc_attr( $key ); ?>" name="aiwp[<?php echo esc_attr( $key ); ?>]" class="aiwp-code" rows="16" spellcheck="false" autocomplete="off" autocapitalize="off" aria-describedby="aiwp-hint-<?php echo esc_attr( $key ); ?>" data-aiwp-code="<?php echo esc_attr( $key ); ?>"><?php echo esc_textarea( $data[ $key ] ); ?></textarea>
					</div>
				<?php endforeach; ?>
				<div class="aiwp-editor-foot"><span><?php echo esc_html__( 'Paste the code itself without ``` fences.', 'ai-web-studio' ); ?></span><span data-aiwp-code-status role="status" aria-live="polite"></span></div>
			</div>
			<div class="aiwp-preview">
				<div class="aiwp-preview-toolbar"><div><h4><?php echo esc_html__( 'Content preview', 'ai-web-studio' ); ?></h4><p data-aiwp-preview-status role="status" aria-live="polite"><?php echo esc_html__( 'Ready for the first preview.', 'ai-web-studio' ); ?></p></div><div class="aiwp-preview-controls"><div class="aiwp-device-group" role="group" aria-label="<?php echo esc_attr__( 'Preview width', 'ai-web-studio' ); ?>"><button type="button" class="button is-active" data-aiwp-device="desktop" aria-pressed="true"><?php echo esc_html__( 'Desktop', 'ai-web-studio' ); ?></button><button type="button" class="button" data-aiwp-device="mobile" aria-pressed="false"><?php echo esc_html__( 'Mobile', 'ai-web-studio' ); ?></button></div><button type="button" class="button button-primary" data-aiwp-refresh><?php echo esc_html__( 'Refresh preview', 'ai-web-studio' ); ?></button></div></div>
				<div class="aiwp-preview-stage" data-aiwp-preview-stage><div class="aiwp-preview-empty" data-aiwp-preview-empty><span aria-hidden="true">◇</span><strong><?php echo esc_html__( 'Your design will appear here', 'ai-web-studio' ); ?></strong><p><?php echo esc_html__( 'After pasting the code, click “Refresh preview”.', 'ai-web-studio' ); ?></p></div><iframe title="<?php echo esc_attr__( 'Isolated preview of the entered content', 'ai-web-studio' ); ?>" class="aiwp-preview-frame" sandbox="allow-scripts" referrerpolicy="no-referrer" hidden></iframe></div>
				<p class="aiwp-preview-note"><?php echo esc_html__( 'This preview does not save changes. It only displays the content currently being edited; links, forms and external scripts are restricted. To preview the whole website, save a draft or update the page, then use WordPress Preview.', 'ai-web-studio' ); ?></p>
			</div>
			<?php if ( $is_page ) : ?>
				<details class="aiwp-page-options"><summary><?php echo esc_html__( 'Page layout', 'ai-web-studio' ); ?></summary><div><label><input type="checkbox" name="aiwp[hide_header]" value="1" <?php checked( ! empty( $data['hide_header'] ) ); ?>> <?php echo esc_html__( 'Hide shared header', 'ai-web-studio' ); ?></label><label><input type="checkbox" name="aiwp[hide_footer]" value="1" <?php checked( ! empty( $data['hide_footer'] ) ); ?>> <?php echo esc_html__( 'Hide shared footer', 'ai-web-studio' ); ?></label><p class="description"><?php echo esc_html__( 'Useful for a standalone landing page, for example. This option is supported by the ByYourself Theme theme.', 'ai-web-studio' ); ?></p></div></details>
			<?php endif; ?>
			<div class="aiwp-save-reminder"><span class="dashicons dashicons-saved" aria-hidden="true"></span><span><?php echo esc_html__( 'Finished? Use', 'ai-web-studio' ); ?> <strong><?php echo esc_html__( 'Save draft', 'ai-web-studio' ); ?></strong>, <strong><?php echo esc_html__( 'Publish', 'ai-web-studio' ); ?></strong> <?php esc_html_e( 'or', 'ai-web-studio' ); ?> <strong><?php echo esc_html__( 'Update', 'ai-web-studio' ); ?></strong> <?php echo esc_html__( 'in WordPress. Previously saved versions are available in revisions.', 'ai-web-studio' ); ?></span></div>
			<noscript><p><?php echo esc_html__( 'Enable JavaScript in your browser for syntax highlighting, tabs and previews. Without it, you can still paste code into the three text fields and save.', 'ai-web-studio' ); ?></p></noscript>
		</div>
		<?php
	}

	public static function seo( $post ) {
		if ( ! aiwp_can_edit_code() ) {
			echo wp_kses_post( __( '<p>SEO settings in ByYourself Builder are managed by the website administrator.</p>', 'ai-web-studio' ) );
			return;
		}
		$data = aiwp_get_document( $post->ID );
		?>
		<div class="aiwp-seo-fields">
			<p class="description" data-aiwp-media-status role="status" aria-live="polite" hidden></p>
			<p class="aiwp-section-lead"><?php echo esc_html__( 'Help people understand what the page contains. An empty SEO title uses the page title.', 'ai-web-studio' ); ?></p>
			<div class="aiwp-field"><label for="aiwp-seo-title-input"><?php echo esc_html__( 'SEO title', 'ai-web-studio' ); ?></label><input type="text" id="aiwp-seo-title-input" name="aiwp[seo_title]" value="<?php echo esc_attr( $data['seo_title'] ); ?>" placeholder="<?php echo esc_attr( get_the_title( $post ) ); ?>" aria-describedby="aiwp-seo-title-input-hint"><p class="description" id="aiwp-seo-title-input-hint"><span data-aiwp-counter="aiwp-seo-title-input">0</span> <?php echo esc_html__( 'characters · Around 50–60 characters is often useful; put the main message first.', 'ai-web-studio' ); ?></p></div>
			<div class="aiwp-field"><label for="aiwp-seo-description"><?php echo esc_html__( 'Meta description', 'ai-web-studio' ); ?></label><textarea id="aiwp-seo-description" name="aiwp[seo_description]" rows="3" aria-describedby="aiwp-seo-description-hint"><?php echo esc_textarea( $data['seo_description'] ); ?></textarea><p class="description" id="aiwp-seo-description-hint"><span data-aiwp-counter="aiwp-seo-description">0</span> <?php echo esc_html__( 'characters · Briefly describe the page\'s value. Around 140–160 characters is a useful starting point, not a fixed limit.', 'ai-web-studio' ); ?></p></div>
			<div class="aiwp-field"><label for="aiwp-seo-image"><?php echo esc_html__( 'Sharing image', 'ai-web-studio' ); ?></label><div class="aiwp-input-action"><input type="url" id="aiwp-seo-image" name="aiwp[seo_image]" value="<?php echo esc_attr( $data['seo_image'] ); ?>" placeholder="https://…/obrazek.jpg"><button type="button" class="button" data-aiwp-media><?php echo esc_html__( 'Choose from media', 'ai-web-studio' ); ?></button></div><p class="description"><?php echo esc_html__( 'Image URL for social link previews. Paste a URL or choose an image from the library.', 'ai-web-studio' ); ?></p></div>
			<details class="aiwp-seo-advanced"><summary><?php echo esc_html__( 'Advanced settings', 'ai-web-studio' ); ?></summary><div class="aiwp-field"><label for="aiwp-seo-canonical"><?php echo esc_html__( 'Canonical URL', 'ai-web-studio' ); ?></label><input type="url" id="aiwp-seo-canonical" name="aiwp[seo_canonical]" value="<?php echo esc_attr( $data['seo_canonical'] ); ?>" placeholder="<?php echo esc_attr__( 'Automatic: this page\'s URL', 'ai-web-studio' ); ?>"><p class="description"><?php echo esc_html__( 'Fill this in only if search engines should treat another URL as the primary version of the same content.', 'ai-web-studio' ); ?></p></div><label class="aiwp-checkbox-line"><input type="checkbox" name="aiwp[seo_noindex]" value="1" <?php checked( ! empty( $data['seo_noindex'] ) ); ?>> <?php echo esc_html__( 'Ask search engines not to index this page (noindex)', 'ai-web-studio' ); ?></label><p class="description"><?php echo esc_html__( 'The page remains publicly accessible. This setting does not password-protect it.', 'ai-web-studio' ); ?></p></details>
			<p class="aiwp-seo-note"><?php echo esc_html__( 'Search engines may choose a different title or description. If you use a supported SEO plugin, manage metadata there — ByYourself Builder will let it output the metadata.', 'ai-web-studio' ); ?></p>
		</div>
		<?php
	}
}
