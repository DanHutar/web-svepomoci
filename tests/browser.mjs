import assert from 'node:assert/strict';
import path from 'node:path';
import { chromium } from 'playwright';

export async function runBrowserChecks(serverUrl, fixtures, outputDir) {
  const browser = await chromium.launch({ headless: true, channel: 'chrome' });
  try {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(serverUrl + '/wp-admin/post.php?post=' + fixtures.pageId + '&action=edit');
    if (await page.locator('#user_login').count()) {
      await page.locator('#user_login').fill('admin');
      await page.locator('#user_pass').fill('aiwp-local-test');
      await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#wp-submit').click()]).catch(async error => {
        console.error('Login diagnostic:', page.url(), await page.locator('body').innerText());
        throw error;
      });
    }
    await page.locator('.aiwp-studio').waitFor();
    await page.waitForFunction(() => document.querySelector('.CodeMirror')?.CodeMirror);
    assert.equal(await page.locator('[name="aiwp[enabled]"]').isChecked(), true);
    assert.equal(await page.locator('#postdivrich').isVisible(), false, 'Ordinary editor is tucked away in AI mode');
    await page.locator('#aiwp-enabled').uncheck();
    assert.equal(await page.locator('#postdivrich').isVisible(), true);
    await page.locator('#aiwp-enabled').check();
    assert.ok(await page.locator('#adminmenu').getByText('Header', { exact: true }).count());
    assert.ok(await page.locator('#adminmenu').getByText('Footer', { exact: true }).count());
    await page.locator('[data-aiwp-tab="css"]').click();
    assert.equal(await page.locator('#aiwp-panel-css').isVisible(), true);
    assert.equal(await page.locator('#aiwp-panel-html').isVisible(), false);
    await page.locator('[data-aiwp-tab="html"]').click();
    await page.locator('[data-aiwp-refresh]').click();
    const preview = page.locator('.aiwp-preview-frame');
    assert.equal(await preview.getAttribute('sandbox'), 'allow-scripts');
    await page.frameLocator('.aiwp-preview-frame').locator('.sample-page').waitFor();
    await page.locator('[data-aiwp-device="mobile"]').click();
    assert.ok((await preview.boundingBox()).width <= 400);
    await page.locator('[data-aiwp-device="desktop"]').click();
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({ path: path.join(outputDir, 'admin-editor.png'), fullPage: true });
    // Save through the actual classic WordPress form, with CodeMirror enabled.
    await page.evaluate(() => {
      const cm = key => document.querySelector('#aiwp-panel-' + key + ' .CodeMirror').CodeMirror;
      cm('html').setValue(cm('html').getValue() + '\n<span id="browser-roundtrip" hidden>Žluťoučký</span>');
      cm('css').setValue(cm('css').getValue() + '\n#browser-roundtrip { --saved-check: 1; }');
      cm('js').setValue(cm('js').getValue() + '\ndocument.documentElement.dataset.aiwpTest = "ok";');
    });
    await page.locator('#aiwp-seo-title').fill('Ateliér – ověřená stránka');
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#publish').click()]);
    await page.locator('.aiwp-studio').waitFor();
    assert.ok((await page.locator('#aiwp-html').inputValue()).includes('browser-roundtrip'));
    assert.equal(await page.locator('#aiwp-seo-title').inputValue(), 'Ateliér – ověřená stránka');
    await context.clearCookies();
    await page.goto(serverUrl + '/?page_id=' + fixtures.pageId);
    await page.waitForFunction(() => document.documentElement.dataset.aiwpTest === 'ok');
    assert.equal(await page.title(), 'Ateliér – ověřená stránka');
    assert.equal(await page.locator('#browser-roundtrip').textContent(), 'Žluťoučký');
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
    await page.screenshot({ path: path.join(outputDir, 'website-desktop.png'), fullPage: true });
    await page.setViewportSize({ width: 1200, height: 900 });
    await page.screenshot({ path: path.resolve(outputDir, '../theme/ai-web/screenshot.png') });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.screenshot({ path: path.join(outputDir, 'website-mobile.png'), fullPage: true });
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'No horizontal mobile overflow');
    assert.deepEqual(errors, [], 'No uncaught JavaScript errors');
    console.log('Browser: tabs, sandbox preview, mobile, native save, frontend JS, SEO and screenshots passed.');
  } finally { await browser.close(); }
}
