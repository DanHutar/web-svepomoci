# ByYourself 1.10.5

Missing pages now show a concise, translated 404 screen with a Back home button and the same published header and footer as the rest of the website. Shared CSS and header/footer JavaScript load correctly on missing URLs, without loading the home page's private or page-specific code. The HTML response remains HTTP 404. Layout defaults live in the main theme stylesheet and can be overridden by Shared CSS.

Comments are disabled by default across the website. The new Comments setting restores the original discussion settings when unchecked. Existing comments are retained for administration; external forms are unaffected. Features that use native WordPress comments, such as product reviews, may also be affected. Clear website/CDN caches after updating.

Admin labels and errors now escape translated output consistently in both packages. Targeted security checks cover roles, nonces, REST permissions, private assets and malicious translations. Google Fonts remain available. Built-in consent management remains retired; configure an external consent plugin separately.

Update both the plugin and theme to receive the complete 404 feature. Security review scope and results are documented in docs/bezpecnostni-testy-2026-10-10.md; this release is not a security certification and does not enable CSP or HSTS.
