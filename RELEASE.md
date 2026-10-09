# ByYourself 1.10.4

Built-in cookie consent management is retired. Its administration screen, banner, footer control and tracking scripts are disabled, including on sites with previously enabled settings. Legacy script URLs return 410 with no-store. Saved settings remain in the database for rollback; no scripts or consent records are migrated to Complianz.

Configure an external consent plugin separately and purge website/CDN caches after updating. Tracking inserted outside the retired module is not automatically blocked. Existing Google Fonts remain available. English and Czech prompts now direct consent setup to an external plugin.

A scoped source-code security review is documented in docs/bezpecnostni-audit-2026-10-09.md. This release does not enable production CSP or HSTS.
