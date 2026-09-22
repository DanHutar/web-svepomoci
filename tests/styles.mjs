import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { phpJson } from '../tools/playground.mjs';

export function stylesUrl(html) {
  const link = html.match(/<link\b[^>]*\bid=['"]aiwp-design-css['"][^>]*>/)?.[0];
  assert.ok(link, 'Frontend links an external design stylesheet');
  return link.match(/\bhref=['"]([^'"]+)['"]/)?.[1].replace(/&#0?38;|&amp;/g, '&');
}

export async function runStylesChecks(server) {
  const fixture = await phpJson(server, `
    $settings = get_option('aiwp_settings', array());
    $page = wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'External CSS fixture'));
    update_post_meta($page, '_aiwp_enabled', 1);
    update_post_meta($page, '_aiwp_html', '<section class="css-fixture"><h1>CSS fixture</h1><p>Text</p></section>');
    $css = '/* private author note */ .css-fixture { --gap: 12px; padding: calc(var(--gap) + 8px); color: rgb(12, 34, 56); } .css-fixture/**/p { font-size: 19px; } .css-fixture::after { content: "a  b /* literal */"; }';
    update_post_meta($page, '_aiwp_css', wp_slash($css));
    $shared = array_merge(aiwp_get_settings(), array('css'=>'.css-fixture { border-top: 3px solid rgb(10, 20, 30); }'));
    update_option('aiwp_settings', $shared);
    foreach (array(
      'a/**/.b { color: red; }' => 'a/**/.b { color: red; }',
      'a { --empty: ; width: calc(100% - 2rem); }' => 'a { --empty: ; width: calc(100% - 2rem); }',
      'a { background: url("data:image/svg+xml,(a)/*literal*/"); }' => 'a { background: url("data:image/svg+xml,(a)/*literal*/"); }',
      'a { background: url(data:image/png;base64,ab/*literal*/cd); }' => 'a { background: url(data:image/png;base64,ab/*literal*/cd); }'
    ) as $source => $expected) { if (aiwp_compact_css($source) !== $expected) { throw new Exception('CSS semantics changed: ' . $source); } }
    $_SERVER['REQUEST_URI'] = '/nested/page/?page_id=' . $page . '&preview=true';
    $url = aiwp_styles_url();
    if (strpos($url, '/nested/page/?') === false || strpos($url, 'preview=true') === false) { throw new Exception('Stylesheet changed the document base URL'); }
    echo wp_json_encode(array('id'=>$page,'settings'=>$settings));
  `);
  let browser;
  try {
    const html = await (await fetch(`${server.serverUrl}/?page_id=${fixture.id}`)).text();
    assert.ok(!html.includes('private author note') && !html.includes('border-top: 3px'), 'Saved CSS is absent from page source');
    assert.ok(!html.includes('aiwp-runtime-inline-css'));
    const url = stylesUrl(html);
    const response = await fetch(url);
    assert.equal(response.status, 200);
    assert.match(response.headers.get('content-type'), /^text\/css/);
    assert.equal(response.headers.get('x-content-type-options'), 'nosniff');
    const css = await response.text();
    assert.ok(css.includes('border-top: 3px') && css.includes('rgb(12, 34, 56)'));
    assert.ok(!css.includes('private author note') && css.includes('a  b /* literal */'));
    const cached = await fetch(url, { headers: { 'If-None-Match': response.headers.get('etag') } });
    assert.equal(cached.status, 304);
    browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto(`${server.serverUrl}/?page_id=${fixture.id}`);
    const initial = await page.locator('.css-fixture').evaluate(el => ({ padding: getComputedStyle(el).paddingTop, content: getComputedStyle(el, '::after').content }));
    assert.deepEqual(initial, { padding: '20px', content: '"a  b /* literal */"' }, 'Compaction preserves calc arithmetic and literal strings');
    await phpJson(server, `update_post_meta(${fixture.id}, '_aiwp_css', '.css-fixture { color: rgb(90, 80, 70); }'); echo 'true';`);
    const changed = stylesUrl(await (await fetch(`${server.serverUrl}/?page_id=${fixture.id}`)).text());
    assert.notEqual(changed, url, 'Saving CSS changes the stylesheet URL');
    const old = await fetch(url, { headers: { 'If-None-Match': response.headers.get('etag') } });
    assert.equal(old.status, 200, 'An old link revalidates against current saved CSS');
    assert.ok((await old.text()).includes('rgb(90, 80, 70)'));
    await page.goto(`${server.serverUrl}/?page_id=${fixture.id}`);
    const computed = await page.locator('.css-fixture').evaluate(el => ({ color: getComputedStyle(el).color, border: getComputedStyle(el).borderTopWidth }));
    assert.deepEqual(computed, { color: 'rgb(90, 80, 70)', border: '3px' });
    await phpJson(server, `update_post_meta(${fixture.id}, '_aiwp_enabled', false); echo 'true';`);
    assert.ok(!(await (await fetch(changed)).text()).includes('rgb(90, 80, 70)'), 'Disabling AI content also removes its CSS from old URLs');
    await phpJson(server, `update_post_meta(${fixture.id}, '_aiwp_enabled', true); echo 'true';`);
    await phpJson(server, `wp_update_post(array('ID'=>${fixture.id}, 'post_status'=>'draft')); echo 'true';`);
    const draft = await fetch(changed);
    assert.equal(draft.status, 404);
    assert.ok(!(await draft.text()).includes('rgb(90, 80, 70)'), 'Formerly public CSS cannot reveal a draft');
    await phpJson(server, `wp_update_post(array('ID'=>${fixture.id}, 'post_status'=>'private')); echo 'true';`);
    assert.equal((await fetch(changed)).status, 404, 'Private page CSS is not public');
    await phpJson(server, `wp_update_post(array('ID'=>${fixture.id}, 'post_status'=>'publish','post_password'=>'styles-test')); echo 'true';`);
    const locked = await fetch(changed);
    assert.ok(!(await locked.text()).includes('rgb(90, 80, 70)'), 'Locked page CSS is excluded even with a previously valid URL');
    assert.match(locked.headers.get('cache-control'), /no-store/);
    await page.goto(`${server.serverUrl}/?page_id=${fixture.id}`);
    await page.locator('input[name="post_password"]').fill('styles-test');
    await Promise.all([page.waitForNavigation(), page.locator('input[name="Submit"]').click()]);
    assert.equal(await page.locator('.css-fixture').evaluate(el => getComputedStyle(el).color), 'rgb(90, 80, 70)', 'Correct password unlocks the page stylesheet');
    console.log('PASS: External CSS, compaction, browser styling, version URLs, ETag and draft/private/password protection.');
  } finally {
    if (browser) await browser.close();
    const settings = Buffer.from(JSON.stringify(fixture.settings)).toString('base64');
    await phpJson(server, `update_option('aiwp_settings',json_decode(base64_decode('${settings}'),true)); wp_delete_post(${fixture.id},true); echo 'true';`);
  }
}
