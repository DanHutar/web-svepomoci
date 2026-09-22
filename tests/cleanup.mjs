import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { phpJson } from '../tools/playground.mjs';

const coreStyles = /id=['"](?:global-styles|wp-block-library|classic-theme-styles)(?:-inline)?-css['"]/;
const emoji = /wp-emoji-settings|wp-emoji-styles|wpemojiSettingsSupports|_wpemojiSettings|wp-emoji-release/;

export async function runCleanupChecks(server) {
  const data = await phpJson(server, `
    $settings = get_option('aiwp_settings', array());
    update_option('aiwp_settings', array('font'=>'system','css'=>''));
    $page = wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'Clean AI page'));
    update_post_meta($page, '_aiwp_enabled', true);
    update_post_meta($page, '_aiwp_html', '<section class="cleanup-fixture"><h1>Čistá stránka 🌻</h1><p>Vlastní obsah</p></section>');
    update_post_meta($page, '_aiwp_css', '.cleanup-fixture { color: rgb(12, 34, 56); }');
    $normal = wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'Native blocks','post_content'=>'<!-- wp:paragraph {"textColor":"vivid-red"} --><p class="has-vivid-red-color has-text-color">Nativní obsah</p><!-- /wp:paragraph -->'));
    foreach (array('class="wp-block-button"', 'color:var(--wp--preset--color--black)', 'class="wp&#45;block-image"', 'class="has-large-font-size"', 'is-layout-flex') as $text) {
      if (!aiwp_needs_core_styles($text)) { throw new Exception('Missed WP style dependency'); }
    }
    echo wp_json_encode(array('page'=>$page,'normal'=>$normal,'settings'=>$settings));
  `);
  const get = id => fetch(`${server.serverUrl}/?page_id=${id}`).then(r => r.text());
  let browser;
  try {
    const clean = await get(data.page);
    assert.ok(!coreStyles.test(clean), 'Independent AI page omits core block and preset CSS');
    assert.ok(!emoji.test(clean), 'Frontend omits WordPress emoji fallback code');
    assert.match(clean, /🌻/);
    assert.match(clean, /rel=['"]canonical['"]/);
    assert.match(clean, /property="og:title"/);
    const normal = await get(data.normal);
    assert.ok(coreStyles.test(normal), 'Ordinary page retains native block styles');
    assert.ok(!emoji.test(normal));
    await phpJson(server, `update_option('aiwp_settings',array('css'=>'.wp-block-button__link { color: red; }')); echo 'true';`);
    assert.ok(!coreStyles.test(await get(data.page)), 'Unused block selector alone does not require core CSS');
    await phpJson(server, `update_post_meta(${data.page}, '_aiwp_html', '<p class="has-vivid-red-color">Copied block class</p>'); echo 'true';`);
    assert.ok(coreStyles.test(await get(data.page)), 'AI HTML using WP classes retains core styles');
    await phpJson(server, `update_post_meta(${data.page}, '_aiwp_html', '<section class="cleanup-fixture"><h1>Čistá stránka 🌻</h1></section>'); update_option('aiwp_settings',array('css'=>'.cleanup-fixture{color:var(--wp--preset--color--vivid-red)}')); echo 'true';`);
    assert.ok(coreStyles.test(await get(data.page)), 'Shared CSS using WP variables retains core styles');
    await phpJson(server, `update_option('aiwp_settings',array('css'=>'')); update_post_meta(${data.page}, '_aiwp_enabled',false); echo 'true';`);
    assert.ok(coreStyles.test(await get(data.page)), 'Disabling AI mode restores native core styles');
    await phpJson(server, `update_post_meta(${data.page}, '_aiwp_enabled',true); wp_update_post(array('ID'=>${data.page},'post_password'=>'test')); echo 'true';`);
    assert.ok(coreStyles.test(await get(data.page)), 'Password form keeps core styles');
    await phpJson(server, `wp_update_post(array('ID'=>${data.page},'post_password'=>'')); echo 'true';`);
    browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const page = await browser.newPage();
    await page.goto(`${server.serverUrl}/?page_id=${data.page}`);
    assert.equal(await page.locator('.cleanup-fixture').evaluate(el => getComputedStyle(el).color), 'rgb(12, 34, 56)');
    await page.goto(`${server.serverUrl}/?page_id=${data.normal}`);
    assert.equal(await page.locator('.has-vivid-red-color').evaluate(el => getComputedStyle(el).color), 'rgb(207, 46, 46)', 'Native block preset still renders correctly');
    console.log('PASS: Lean AI page source, native emoji, dependency detection, ordinary blocks and SEO preservation.');
  } finally {
    if (browser) await browser.close();
    const encoded = Buffer.from(JSON.stringify(data.settings)).toString('base64');
    await phpJson(server, `update_option('aiwp_settings',json_decode(base64_decode('${encoded}'),true)); wp_delete_post(${data.page},true); wp_delete_post(${data.normal},true); echo 'true';`);
  }
}
