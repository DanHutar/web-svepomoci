import { randomUUID } from 'node:crypto';
import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { bootWordPress, phpJson, projectRoot } from '../tools/playground.mjs';
const testPassword = randomUUID();
const server = await bootWordPress(9412, false, projectRoot, '7.1.2');
let browser;
try {
  const saved = await phpJson(server, `
    $s = aiwp_consent_settings(); $s['external'] = false; $s['policy'] = home_url('/privacy/');
    foreach (array('analytics','marketing') as $key) {
      $s[$key] = array('enabled'=>true, 'description'=>'Existing service', 'js'=>'window.retiredTracking=true;', 'cookies'=>'_test', 'storage'=>'_test');
    }
    update_option('aiwp_consent_settings', $s);
    $id = wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'Legacy cookies'));
    update_post_meta($id, '_aiwp_enabled', true);
    update_post_meta($id, '_aiwp_html', '<section>Legacy marker: [aiwp_cookie_settings]</section>');
    wp_set_password('${testPassword}', 1);
    echo wp_json_encode(array('id'=>$id,'settings'=>$s,'cookie'=>aiwp_consent_cookie_name(),'version'=>aiwp_consent_version($s)));
  `);
  browser = await chromium.launch({ headless: true, channel: 'chrome' });
  const context = await browser.newContext();
  await context.addCookies([{name:saved.cookie, value:encodeURIComponent(JSON.stringify({v:saved.version,t:Math.floor(Date.now()/1000),analytics:true,marketing:true})),url:server.serverUrl}]);
  const page = await context.newPage();
  for (const language of ['en','cs']) {
    await phpJson(server, `update_option('wsp_languages',array('ui'=>'${language}','public'=>'${language}','content'=>'${language}')); echo 'true';`);
    await page.goto(server.serverUrl+'/?page_id='+saved.id);
    assert.equal(await page.locator('#aiwp-consent-panel, [data-aiwp-consent-open], #aiwp-consent-js, #aiwp-consent-css').count(),0);
    assert.ok(!(await page.content()).includes('[aiwp_cookie_settings]'));
    assert.equal(await page.evaluate(()=>!!window.retiredTracking),false);
  }
  for(const key of ['analytics','marketing','invalid','%5B%5D=analytics']) {
    const response=await context.request.get(server.serverUrl+'/?aiwp_consent_script='+key);
    assert.equal(response.status(),410);
    assert.equal(await response.text(),'');
    assert.match(response.headers()['cache-control'],/no-store/);
  }
  const arrayResponse=await context.request.get(server.serverUrl+'/?aiwp_consent_script%5B%5D=analytics');
  assert.equal(arrayResponse.status(),410);
  const head=await context.request.head(server.serverUrl+'/?aiwp_consent_script=analytics');
  assert.equal(head.status(),410);
  await page.goto(server.serverUrl+'/wp-login.php');
  await page.locator('#user_login').fill('admin');
  await page.locator('#user_pass').fill(testPassword);
  await Promise.all([page.waitForNavigation(),page.locator('#wp-submit').click()]);
  await page.goto(server.serverUrl+'/wp-admin/admin.php?page=aiwp');
  assert.equal(await page.locator('#adminmenu a[href*="page=aiwp-consent"]').count(),0);
  const removed=await context.request.get(server.serverUrl+'/wp-admin/admin.php?page=aiwp-consent');
  assert.equal(removed.status(),403);
  const footer=await phpJson(server, `echo wp_json_encode(aiwp_get_part_id('footer'));`);
  await page.goto(server.serverUrl+'/wp-admin/post.php?post='+footer+'&action=edit');
  assert.ok(!(await page.locator('body').innerText()).includes('[aiwp_cookie_settings]'));
  const state=await phpJson(server, `
    do_action('admin_init');
    echo wp_json_encode(array('stored'=>get_option('aiwp_consent_settings'),'external'=>aiwp_consent_settings()['external'],'registered'=>isset(get_registered_settings()['aiwp_consent_settings'])));
  `);
  assert.deepEqual(state.stored,saved.settings);
  assert.equal(state.external,true);
  assert.equal(state.registered,false);
  console.log('PASS: retired consent stays off with legacy settings/cookies, in both languages; endpoints closed, admin removed, stored settings preserved.');
} finally { if(browser) await browser.close(); await server[Symbol.asyncDispose](); }
