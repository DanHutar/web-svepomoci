import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { phpJson } from '../tools/playground.mjs';

function scriptUrl(html, kind) {
  const tag = html.match(new RegExp(`<script\\b[^>]*\\bid=['"]aiwp-${kind}-js['"][^>]*>`))?.[0];
  assert.ok(tag, `External ${kind} script exists`);
  return tag.match(/\bsrc=['"]([^'"]+)['"]/)?.[1].replace(/&#0?38;|&amp;/g, '&');
}

export async function runScriptChecks(server) {
  const scripts = {
    header: 'window.aiwpOrder = ["header"];',
    footer: 'window.aiwpOrder.push("footer");',
    page: 'window.aiwpOrder.push("page"); document.querySelector("#script-button").addEventListener("click", function(){this.textContent="Clicked";}); document.addEventListener("DOMContentLoaded",function(){document.body.dataset.scriptReady="yes";}); // final comment',
  };
  const encoded = Buffer.from(JSON.stringify(scripts)).toString('base64');
  const fixture = await phpJson(server, `
    $old = get_option('aiwp_part_ids', array());
    $scripts = json_decode(base64_decode('${encoded}'),true);
    $ids = array();
    foreach ($scripts as $kind => $js) {
      $id = wp_insert_post(array('post_type'=>$kind === 'page' ? 'page' : 'aiwp_part','post_status'=>'publish','post_title'=>'External script fixture'));
      update_post_meta($id,'_aiwp_enabled',true);
      update_post_meta($id,'_aiwp_html',$kind === 'page' ? '<button id="script-button">Test</button>' : '<div>Part</div>');
      update_post_meta($id,'_aiwp_js',wp_slash($js));
      $ids[$kind] = $id;
    }
    update_option('aiwp_part_ids',array('header'=>$ids['header'],'footer'=>$ids['footer']));
    $_SERVER['REQUEST_URI'] = '/nested/path/?preview=true&page_id=' . $ids['page'];
    $url = aiwp_script_url('page',$scripts['page']);
    if (strpos($url,'/nested/path/?') === false || strpos($url,'preview=true') === false) { throw new Exception('Script URL lost page context'); }
    echo wp_json_encode(array('ids'=>$ids,'old'=>$old));
  `);
  const pageId = fixture.ids.page;
  const pageUrl = `${server.serverUrl}/?page_id=${pageId}`;
  const html = () => fetch(pageUrl).then(r => r.text());
  const setJs = async (kind, js) => {
    const value = Buffer.from(js).toString('base64');
    await phpJson(server, `update_post_meta(${fixture.ids[kind]},'_aiwp_js',wp_slash(base64_decode('${value}'))); echo 'true';`);
  };
  let browser;
  try {
    const source = await html();
    assert.ok(!source.includes('window.aiwpOrder') && !source.includes('final comment'), 'Saved JavaScript is absent from HTML');
    const urls = Object.fromEntries(Object.keys(scripts).map(kind => [kind, scriptUrl(source, kind)]));
    for (const kind of Object.keys(scripts)) {
      const response = await fetch(urls[kind]);
      assert.equal(response.status, 200);
      assert.match(response.headers.get('content-type'), /^application\/javascript/);
      assert.equal(response.headers.get('x-content-type-options'), 'nosniff');
      assert.equal(await response.text(), `(function(){\n${scripts[kind]}\n})();`, 'Original syntax and wrapper remain intact');
    }
    const response = await fetch(urls.page);
    const etag = response.headers.get('etag');
    assert.ok(etag);
    assert.equal((await fetch(urls.page, { headers: { 'If-None-Match': etag } })).status, 304);
    assert.equal(await (await fetch(urls.page, { method: 'HEAD' })).text(), '');
    for (const query of ['aiwp_script=unknown', 'aiwp_script[]=page']) {
      assert.equal((await fetch(`${pageUrl}&${query}`)).status, 404);
    }
    browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto(pageUrl);
    assert.deepEqual(await page.evaluate(() => window.aiwpOrder), ['header', 'footer', 'page']);
    assert.equal(await page.locator('body').getAttribute('data-script-ready'), 'yes');
    await page.locator('#script-button').click();
    assert.equal(await page.locator('#script-button').textContent(), 'Clicked');
    await setJs('header', 'this is invalid javascript !!!');
    await setJs('footer', 'window.aiwpOrder = ["footer"];');
    await page.goto(pageUrl);
    assert.deepEqual(await page.evaluate(() => window.aiwpOrder), ['footer', 'page'], 'Syntax error in one script does not stop the others');
    await setJs('header', scripts.header);
    await setJs('footer', scripts.footer);
    await setJs('page', scripts.page + '\n// updated script');
    assert.notEqual(scriptUrl(await html(), 'page'), urls.page);
    const updated = await fetch(urls.page, { headers: { 'If-None-Match': etag } });
    assert.equal(updated.status, 200);
    assert.ok((await updated.text()).includes('updated script'));
    await phpJson(server, `update_post_meta(${pageId},'_aiwp_hide_header',true); echo 'true';`);
    assert.equal((await fetch(urls.header)).status, 404, 'Hidden part script is unavailable even through an old link');
    await phpJson(server, `update_post_meta(${pageId},'_aiwp_hide_header',false); update_post_meta(${pageId},'_aiwp_enabled',false); echo 'true';`);
    assert.equal((await fetch(urls.page)).status, 404, 'Disabled page script is unavailable');
    await phpJson(server, `update_post_meta(${pageId},'_aiwp_enabled',true); echo 'true';`);
    for (const status of ['draft', 'private']) {
      await phpJson(server, `wp_update_post(array('ID'=>${pageId},'post_status'=>'${status}')); echo 'true';`);
      const result = await fetch(urls.page);
      assert.equal(result.status, 404);
      assert.ok(!(await result.text()).includes('aiwpOrder'));
    }
    await phpJson(server, `wp_update_post(array('ID'=>${pageId},'post_status'=>'publish','post_password'=>'script-test')); echo 'true';`);
    const locked = await fetch(urls.page);
    assert.equal(locked.status, 404);
    assert.match(locked.headers.get('cache-control'), /no-store/);
    await page.goto(pageUrl);
    await page.locator('input[name="post_password"]').fill('script-test');
    await Promise.all([page.waitForNavigation(), page.locator('input[name="Submit"]').click()]);
    assert.deepEqual(await page.evaluate(() => window.aiwpOrder), ['header', 'footer', 'page']);
    const unlocked = await context.request.get(scriptUrl(await page.content(), 'page'));
    assert.equal(unlocked.status(), 200);
    assert.match(unlocked.headers()['cache-control'], /no-store/);
    assert.equal(unlocked.headers().etag, undefined);
    console.log('PASS: External JS, execution order, DOM ready, clicks, error isolation, cache invalidation and draft/private/password/hidden protection.');
  } finally {
    if (browser) await browser.close();
    const old = Buffer.from(JSON.stringify(fixture.old)).toString('base64');
    await phpJson(server, `update_option('aiwp_part_ids',json_decode(base64_decode('${old}'),true)); foreach(array(${Object.values(fixture.ids).join(',')}) as $id) { wp_delete_post($id,true); } echo 'true';`);
  }
}
