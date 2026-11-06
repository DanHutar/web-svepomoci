# ByYourself 1.10.7

The page and shared-part editor preview now renders Pencilino markers instead of showing their source code. It displays saved Pencilino overrides and media images while using draft defaults for new fields. Preview requests require code-editing permission, document access and a nonce. The sandbox remains isolated; previews do not register or modify Pencilino fields or save page content. Failed requests show an error instead of raw markers.

## Integration introduced in 1.10.6

Optional Pencilino integration supports editable text, rich text, buttons and media images in ByYourself HTML. Pencilino remains separately installed and owns editing, permissions and storage. Existing HTML needs explicit editable markers; see docs/pencilino.md.

Fields are isolated by document ID, including shared headers and footers. Without Pencilino, original HTML defaults remain visible. AI prompts include integration instructions when Pencilino is active. Default theme navigation remains WordPress-managed. Frontend editor sessions retain core WordPress styles.

Update both Builder and Theme. Pencilino is not bundled. Local tests with supplied Pencilino 2.0.4 passed: REST saves and reload, page isolation, images, rich-text sanitation, shared header, anonymous rejection, editor/code permission separation and deactivation fallback.

## Retained improvements from 1.10.5

Missing pages now show a concise, translated 404 screen with a Back home button and the same published header and footer as the rest of the website. Shared CSS and header/footer JavaScript load correctly on missing URLs, without loading the home page's private or page-specific code. The HTML response remains HTTP 404. Layout defaults live in the main theme stylesheet and can be overridden by Shared CSS.

Comments are disabled by default across the website. The new Comments setting restores the original discussion settings when unchecked. Existing comments are retained for administration; external forms are unaffected. Features that use native WordPress comments, such as product reviews, may also be affected. Clear website/CDN caches after updating.

Admin labels and errors now escape translated output consistently in both packages. Targeted security checks cover roles, nonces, REST permissions, private assets and malicious translations. Google Fonts remain available. Built-in consent management remains retired; configure an external consent plugin separately.

Update both the plugin and theme to receive the complete 404 feature. Security review scope and results are documented in docs/bezpecnostni-testy-2026-10-10.md; this release is not a security certification and does not enable CSP or HSTS.
