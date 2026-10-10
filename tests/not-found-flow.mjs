import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { bootWordPress, phpJson } from '../tools/playground.mjs';
import { stylesUrl } from './styles.mjs';

const server = await bootWordPress(9416, false);
let browser;
try {
  await server.playground.mkdir('/wordpress/wp-content/mu-plugins');
  await server.playground.writeFile('/wordpress/wp-content/mu-plugins/aiwp-test-offline.php', `<?php
    if (!defined('DISABLE_WP_CRON')) define('DISABLE_WP_CRON',true);
    add_filter('pre_http_request', function() { return new WP_Error('test_offline','External HTTP disabled in regression'); }, PHP_INT_MAX);
  `);
  await phpJson(server, `
    $parts = array();
    foreach (array('header','footer') as $kind) {
      $id = wp_insert_post(array('post_type'=>'aiwp_part','post_status'=>'publish','post_title'=>$kind));
      update_post_meta($id, '_aiwp_enabled', true);
      update_post_meta($id, '_aiwp_html', '<div class="test-'.$kind.'">Shared '.$kind.'</div>');
      update_post_meta($id, '_aiwp_css', '.test-'.$kind.'{color:rgb(11,22,33)}');
      update_post_meta($id, '_aiwp_js', 'document.documentElement.dataset.'.$kind.'="ready";');
      $parts[$kind] = $id;
    }
    update_option('aiwp_part_ids', $parts);
    $settings = aiwp_get_settings();
    $settings['css'] = '.ai-web-not-found h1{color:rgb(12,34,56)}';
    update_option('aiwp_settings', $settings);
    $home = wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'Home'));
    update_post_meta($home, '_aiwp_enabled', true);
    update_post_meta($home, '_aiwp_html', '<p>Home only</p>');
    update_post_meta($home, '_aiwp_css', '.HOME_ONLY_SENTINEL{color:red}');
    update_post_meta($home, '_aiwp_js', 'window.HOME_ONLY_SENTINEL=true;');
    update_post_meta($home, '_aiwp_hide_header', true);
    update_post_meta($home, '_aiwp_hide_footer', true);
    update_option('show_on_front','page'); update_option('page_on_front',$home);
    echo 'true';
  `);
  console.log('404 fixtures ready.');
  const url = server.serverUrl + '/?page_id=999999';
  const response = await fetch(url);
  assert.equal(response.status, 404, 'HTML retains real 404 status');
  const html = await response.text();
  assert.match(html, /Zpět domů/);
  assert.match(html, /Shared header/);
  assert.match(html, /Shared footer/);
  const cssResponse = await fetch(stylesUrl(html));
  assert.equal(cssResponse.status, 200);
  const css = await cssResponse.text();
  assert.match(css, /test-header/); assert.match(css, /test-footer/);
  assert.match(css, /12,34,56/); assert.ok(!css.includes('HOME_ONLY_SENTINEL'));
  assert.equal((await fetch(server.serverUrl + '/?aiwp_error_assets=404&aiwp_script=page')).status, 404);
  console.log('PASS: 404 HTTP status, translations, shared CSS and exclusion of home code.');
  browser = await chromium.launch({headless:true, channel:'chrome'});
  const page = await browser.newPage({viewport:{width:390,height:844}});
  page.setDefaultNavigationTimeout(90000);
  await page.goto(url, {waitUntil:'networkidle'});
  assert.equal(await page.locator('h1').evaluate(el=>getComputedStyle(el).color), 'rgb(12, 34, 56)');
  assert.equal(await page.evaluate(()=>document.documentElement.dataset.header), 'ready');
  assert.equal(await page.evaluate(()=>document.documentElement.dataset.footer), 'ready');
  assert.equal(await page.evaluate(()=>window.HOME_ONLY_SENTINEL), undefined);
  assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
  await page.locator('.ai-web-not-found__button').click();
  assert.equal(new URL(page.url()).pathname, '/');
  assert.equal(new URL(page.url()).search, '');
  await phpJson(server, `update_option('wsp_languages',array('ui'=>'en','public'=>'en','content'=>'en')); echo 'true';`);
  assert.match(await (await fetch(url)).text(), /Back home/);
  await phpJson(server, `require_once ABSPATH.'wp-admin/includes/plugin.php'; deactivate_plugins('ai-web-studio/ai-web-studio.php'); echo 'true';`);
  const fallback = await fetch(url);
  assert.equal(fallback.status,404);
  const fallbackHtml = await fallback.text();
  assert.match(fallbackHtml,/ai-web-header/); assert.match(fallbackHtml,/ai-web-footer/);
  assert.match(fallbackHtml,/Back home/);
  console.log('PASS: 404 status, shared CSS and part JS, isolated home assets, mobile layout, home link, Czech/English and theme fallback.');
} finally {
  if (browser) await browser.close();
  await server[Symbol.asyncDispose]();
}
