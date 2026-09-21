import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { phpJson } from '../tools/playground.mjs';

export async function runDesignChecks(server) {
  const original = await phpJson(server, 'echo wp_json_encode(get_option("aiwp_settings", array()));');
  const checks = await phpJson(server, `
    require_once ABSPATH . 'wp-admin/includes/template.php';
    $old = aiwp_get_settings();
    foreach (array_keys(aiwp_font_catalog()) as $key) {
      $saved = aiwp_sanitize_settings(array_merge($old, array('font' => $key)));
      if ($saved['font'] !== $key) { throw new Exception('Font rejected: ' . $key); }
    }
    if (aiwp_sanitize_settings(array_merge($old, array('font' => 'https://evil.invalid/font.css'))) !== $old) { throw new Exception('Unknown font accepted'); }
    if (aiwp_font_details('system')['url'] !== '' || aiwp_font_details('serif')['url'] !== '') { throw new Exception('Legacy font makes external request'); }
    echo 'true';
  `);
  assert.equal(checks, true);
  const browser = await chromium.launch({ headless: true, channel: 'chrome' });
  try {
    const context = await browser.newContext();
    await context.route('https://fonts.googleapis.com/**', route => route.fulfill({ contentType: 'text/css', body: '/* deterministic font request fixture */' }));
    await context.addInitScript(() => {
      Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: async text => { window.copiedPrompt = text; } } });
    });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(server.serverUrl + '/wp-admin/admin.php?page=aiwp-settings');
    if (await page.locator('#user_login').count()) {
      await page.locator('#user_login').fill('admin');
      await page.locator('#user_pass').fill('aiwp-local-test');
      await Promise.all([page.waitForNavigation(), page.locator('#wp-submit').click()]);
    }
    assert.equal(await page.locator('#aiwp-font optgroup[label="Veřejné Google Fonts"] option').count(), 8);
    await page.locator('#aiwp-font').selectOption('lora');
    let css = ':root{';
    for (const level of ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'body', 'small']) {
      css += `--aiwp-size-${level}:clamp(1rem,calc(1rem + 1vw),2rem);--aiwp-leading-${level}:clamp(1.3em,calc(1.3em + 0.1vw),1.6em);`;
    }
    css += '}';
    const scope = ':where(.aiwp-content,.aiwp-header,.aiwp-footer)';
    for (const level of ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'body', 'small']) {
      css += `${scope} ${level === 'body' ? 'p' : level}{font-size:var(--aiwp-size-${level});line-height:var(--aiwp-leading-${level});}`;
    }
    await page.locator('#aiwp-global-css').fill(css);
    await page.locator('[data-aiwp-design-copy]').click();
    await page.waitForFunction(() => !!window.copiedPrompt);
    const sharedPrompt = await page.evaluate(() => window.copiedPrompt);
    assert.ok(sharedPrompt.includes('Zvolený font: Lora') && sharedPrompt.includes(css));
    assert.ok(sharedPrompt.includes('H1, H2, H3, H4, H5, H6, body') && sharedPrompt.includes('small'));
    assert.ok(sharedPrompt.includes('font-size: clamp') && sharedPrompt.includes('line-height: clamp'));
    await page.evaluate(() => { navigator.clipboard.writeText = async () => { throw new Error('Clipboard denied for test'); }; });
    await page.locator('[data-aiwp-design-copy]').click();
    await page.locator('[data-aiwp-design-fallback] textarea').waitFor({ state: 'visible' });
    assert.equal(await page.locator('[data-aiwp-design-fallback] textarea').inputValue(), sharedPrompt);
    await Promise.all([page.waitForNavigation(), page.locator('#submit').click()]);
    assert.equal(await page.locator('#aiwp-font').inputValue(), 'lora');
    assert.equal(await page.locator('#aiwp-global-css').inputValue(), css);
    const id = await phpJson(server, `$id=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'Font test')); update_post_meta($id,'_aiwp_enabled',true); update_post_meta($id,'_aiwp_html','<section class="font-test"><h1>Český nadpis</h1><p>Běžný text</p><small>Drobný text</small></section>'); echo wp_json_encode($id);`);
    await page.goto(server.serverUrl + '/?page_id=' + id);
    assert.equal(await page.locator('link#aiwp-google-font-css').count(), 1);
    assert.match(await page.locator('link#aiwp-google-font-css').getAttribute('href'), /family=Lora.*display=swap/);
    assert.match(await page.locator('.font-test p').evaluate(el => getComputedStyle(el).fontFamily), /Lora/);
    const dimensions = await page.locator('.font-test h1').evaluate(el => ({ size: parseFloat(getComputedStyle(el).fontSize), leading: parseFloat(getComputedStyle(el).lineHeight) }));
    assert.ok(dimensions.leading > dimensions.size && dimensions.size >= 16);
    await page.goto(server.serverUrl + '/wp-admin/post.php?post=' + id + '&action=edit');
    await page.locator('[data-aiwp-copy-prompt]').click();
    await page.waitForFunction(() => !!window.copiedPrompt);
    const pagePrompt = await page.evaluate(() => window.copiedPrompt);
    assert.ok(pagePrompt.includes('Zvolený font: Lora') && pagePrompt.includes(css));
    await page.locator('[data-aiwp-refresh]').click();
    const frame = page.frameLocator('.aiwp-preview-frame');
    await frame.locator('.aiwp-content .font-test p').waitFor();
    assert.match(await frame.locator('.font-test p').evaluate(el => getComputedStyle(el).fontFamily), /Lora/);
    assert.equal(await frame.locator('link[href*="fonts.googleapis.com"]').count(), 1);
    assert.ok(await frame.locator('.font-test h1').evaluate(el => parseFloat(getComputedStyle(el).lineHeight) > parseFloat(getComputedStyle(el).fontSize)));
    assert.deepEqual(errors, []);
    console.log('PASS: Google Font validation, native settings save, live prompt context, clipboard fallback and shared clamp typography in frontend and sandbox preview.');
  } finally {
    await browser.close();
    const encoded = Buffer.from(JSON.stringify(original)).toString('base64');
    await phpJson(server, `update_option('aiwp_settings',json_decode(base64_decode('${encoded}'),true)); echo 'true';`);
  }
}
