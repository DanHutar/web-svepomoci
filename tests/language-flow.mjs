import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { bootWordPress, phpJson, projectRoot } from '../tools/playground.mjs';
const server = await bootWordPress(9413, false, projectRoot, '7.1.2', null);
let browser, page;
const errors=[];
async function checkSample(page, language) {
  await page.waitForFunction(() => document.querySelector('.CodeMirror')?.CodeMirror);
  await page.locator('[data-aiwp-sample]').click();
  const code = await page.evaluate(() => Object.fromEntries(['html','css'].map(key => [key, document.querySelector('#aiwp-panel-'+key+' .CodeMirror').CodeMirror.getValue()])));
  assert.match(code.html, /class="sample-page__content"/);
  assert.doesNotMatch(code.html + code.css, /moje-|__obsah|__karty|__tlacitko|#vice/);
  assert.match(code.html, /href="#sample-more"/);
  assert.match(code.html, /id="sample-more"/);
  assert.match(await page.locator('[data-aiwp-action-status]').innerText(), language === 'en' ? /Edit the text and links/ : /Upravte texty a odkazy/);
  const heading = language === 'en' ? 'Great ideas start with a first page.' : 'Velké nápady začínají první stránkou.';
  await page.locator('[data-aiwp-refresh]').click();
  const preview = page.frameLocator('.aiwp-preview-frame');
  assert.equal(await preview.locator('.sample-page h1').innerText(), heading);
  assert.equal(await preview.locator('.sample-page').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(246, 248, 251)');
  await page.locator('#title').fill('Sample '+language);
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#publish').click()]);
  const id = await page.locator('#post_ID').inputValue();
  const publicPage = await page.context().newPage();
  try {
    await publicPage.goto(server.serverUrl+'/?page_id='+id);
    assert.equal(await publicPage.locator('.sample-page h1').innerText(), heading);
    assert.equal(await publicPage.locator('.sample-page').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(246, 248, 251)');
    assert.equal(await publicPage.locator('.sample-page__cards').evaluate(el => getComputedStyle(el).gridTemplateColumns.split(' ').length), 3);
    await publicPage.setViewportSize({width:390,height:844});
    assert.equal(await publicPage.locator('.sample-page__cards').evaluate(el => getComputedStyle(el).gridTemplateColumns.split(' ').length), 1);
  } finally { await publicPage.close(); }
  await page.bringToFront();
}
async function checkPartSamples(page, language) {
  const ids = await phpJson(server, `echo wp_json_encode(array('header'=>aiwp_get_part_id('header'),'footer'=>aiwp_get_part_id('footer')));`);
  for (const [kind, id] of Object.entries(ids)) {
    await page.goto(server.serverUrl+'/wp-admin/post.php?post='+id+'&action=edit');
    await page.waitForFunction(() => document.querySelector('.CodeMirror')?.CodeMirror);
    await page.locator('[data-aiwp-sample]').click();
    const html = await page.evaluate(() => document.querySelector('#aiwp-panel-html .CodeMirror').CodeMirror.getValue());
    assert.match(html, /class="sample-part"/);
    assert.doesNotMatch(html, /moje-/);
    assert.ok(html.includes('[aiwp_menu location="'+(kind === 'footer' ? 'footer' : 'primary')+'"]'));
    await page.locator('[data-aiwp-refresh]').click();
    const preview = page.frameLocator('.aiwp-preview-frame');
    assert.equal(await preview.locator('.sample-part__brand').innerText(), language === 'en' ? 'Your website name' : 'Název vašeho webu');
    assert.equal(await preview.locator('.sample-part').evaluate(el => getComputedStyle(el).display), 'flex');
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#publish').click()]);
  }
}
try {
  const initial = await phpJson(server, `wp_set_password('aiwp-local-test',1); echo wp_json_encode(WSP_Languages::settings());`);
  assert.deepEqual(initial, { ui:'en', public:'en', content:'en' });
  browser = await chromium.launch({ headless:true, channel:'chrome' });
  const context = await browser.newContext();
  // Test translated copy feedback without depending on headless browser clipboard permission UI.
  await context.addInitScript(() => {
    Object.defineProperty(navigator, 'clipboard', { configurable: true, value: {
      writeText: async text => { window.copiedPrompt = text; }
    } });
  });
  page = await context.newPage();
  page.on('dialog', dialog => dialog.accept());
  page.setDefaultTimeout(20000);
  page.setDefaultNavigationTimeout(60000);
  page.on('pageerror',e=>errors.push(e.message));
  await page.goto(server.serverUrl+'/wp-login.php');
  await page.locator('#user_login').fill('admin'); await page.locator('#user_pass').fill('aiwp-local-test');
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#wp-submit').click()]);
  await page.goto(server.serverUrl+'/wp-admin/admin.php?page=aiwp');
  assert.equal(await page.locator('.aiwp-dashboard h1').innerText(), 'Your website starts with an idea.');
  assert.equal(await page.locator('#toplevel_page_aiwp .wp-menu-name').innerText(), 'ByYourself');
  await page.goto(server.serverUrl+'/wp-admin/post-new.php?post_type=page');
  assert.equal(await page.locator('[data-aiwp-sample]').innerText(),'Insert sample content');
  await page.locator('[data-aiwp-copy-prompt]').click();
  await page.waitForFunction(()=>document.querySelector('[data-aiwp-action-status]').textContent.length > 0);
  assert.match(await page.locator('[data-aiwp-action-status]').innerText(), /Prompt|Copy/);
  await checkSample(page, 'en');
  await checkPartSamples(page, 'en');
  console.log('English page/header/footer samples: classes, preview, native save and mobile layout passed.');
  await page.goto(server.serverUrl+'/wp-admin/admin.php?page=wsp-languages');
  await page.locator('#wsp-language-ui').selectOption('cs');
  await page.locator('#wsp-language-public').selectOption('cs');
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }),page.locator('#submit').click()]);
  assert.equal(await page.locator('.wrap h1').last().innerText(),'Jazyk');
  await page.goto(server.serverUrl+'/wp-admin/admin.php?page=aiwp');
  assert.equal(await page.locator('.aiwp-dashboard h1').innerText(),'Váš web začíná nápadem.');
  assert.equal(await page.locator('#toplevel_page_aiwp .wp-menu-name').innerText(), 'ByYourself');
  await page.goto(server.serverUrl+'/wp-admin/post-new.php?post_type=page');
  assert.equal(await page.locator('[data-aiwp-sample]').innerText(),'Vložit ukázkový obsah');
  await page.locator('[data-aiwp-copy-prompt]').click();
  await page.waitForFunction(()=>document.querySelector('[data-aiwp-action-status]').textContent.length > 0);
  assert.match(await page.locator('[data-aiwp-action-status]').innerText(), /Zadání|zadání/);
  await checkSample(page, 'cs');
  await checkPartSamples(page, 'cs');
  console.log('Czech page/header/footer samples: English classes, translated text and responsive layout passed.');
  await page.goto(server.serverUrl+'/wp-admin/admin.php?page=aiwp-prompts');
  await page.locator('#aiwp-prompt-instructions').fill('Create an accessible contact page.');
  await page.locator('#aiwp-prompt-kind').selectOption('page');
  await page.locator('#aiwp-prompt-generate').click();
  await page.locator('#aiwp-prompt-result').waitFor();
  assert.match(await page.locator('#aiwp-prompt-output').inputValue(), /Požadovaný jazyk obsahu: angličtina/);
  const fixture = await phpJson(server, `
    $id = wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'Keep my title','post_content'=>'Keep my original content'));
    update_post_meta($id, '_aiwp_html', '<section><h1>Do not translate me</h1></section>');
    $s=aiwp_consent_settings(); $s['external']=false; $s['policy']=home_url('/privacy');
    $s['analytics']=array('enabled'=>true,'description'=>'Keep this service description','js'=>'window.languageTestTracking=true;','cookies'=>'','storage'=>'');
    update_option('aiwp_consent_settings',$s); echo wp_json_encode($id);
  `);
  await page.goto(server.serverUrl+'/?page_id='+fixture);
  assert.equal(await page.locator('[data-aiwp-consent-action="accept"]').innerText(),'Přijmout vše');
  await page.goto(server.serverUrl+'/wp-admin/admin.php?page=wsp-languages');
  await page.locator('#wsp-language-public').selectOption('en');
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }),page.locator('#submit').click()]);
  assert.equal(await page.locator('.wrap h1').last().innerText(),'Jazyk', 'Public language does not change the interface');
  await page.goto(server.serverUrl+'/?page_id='+fixture);
  assert.equal(await page.locator('[data-aiwp-consent-action="accept"]').innerText(),'Accept all');
  assert.equal(await page.locator('.aiwp-consent-description').innerText(),'Keep this service description');
  assert.ok((await page.content()).includes('Keep my original content'));
  await page.goto(server.serverUrl+'/?p=999999');
  assert.equal(await page.locator('main h1').innerText(),'Page not found');
  assert.equal(await phpJson(server, `echo wp_json_encode(get_post_meta(${fixture},'_aiwp_html',true));`), '<section><h1>Do not translate me</h1></section>');
  const protectedSettings = await phpJson(server, `
    require_once ABSPATH . 'wp-admin/includes/template.php';
    wp_set_current_user(1);
    $old=WSP_Languages::settings(); $bad=WSP_Languages::sanitize(array('ui'=>'../../evil','public'=>'en','content'=>'en'));
    wp_set_current_user(0); $denied=WSP_Languages::sanitize(array('ui'=>'en','public'=>'en','content'=>'en'));
    echo wp_json_encode($bad===$old && $denied===$old);
  `);
  assert.equal(protectedSettings,true);
  await phpJson(server, `delete_option('wsp_languages'); echo 'true';`);
  await page.goto(server.serverUrl+'/wp-admin/admin.php?page=wsp-languages');
  assert.equal(await page.locator('#wsp-language-ui').inputValue(),'cs', 'Upgrade preserves Czech on configured sites');
  assert.equal(await phpJson(server, `echo wp_json_encode(get_option('WPLANG',''));`), '', 'WordPress language is unchanged');
  await phpJson(server, `require_once ABSPATH.'wp-admin/includes/plugin.php'; deactivate_plugins('ai-web-studio/ai-web-studio.php'); echo 'true';`);
  await page.goto(server.serverUrl+'/wp-admin/themes.php?page=wsp-languages');
  assert.equal(await page.locator('#wsp-language-ui').inputValue(),'cs', 'Theme supports languages without the plugin');
  await page.locator('#wsp-language-public').selectOption('en');
  await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }),page.locator('#submit').click()]);
  await page.goto(server.serverUrl+'/?p=999999');
  assert.equal(await page.locator('main h1').innerText(),'Page not found');
  assert.deepEqual(errors,[]);
  console.log('Languages: defaults, UI/JS/prompts, independent public labels, preserved content, permissions, migration and standalone theme passed.');
} catch (error) {
  console.error('Language test diagnostic:', errors, page && await page.evaluate(() => ({
    url: location.href, state: document.readyState, codeEditor: !!document.querySelector('.CodeMirror'),
    copied: window.copiedPrompt, status: document.querySelector('[data-aiwp-action-status]')?.textContent,
    mode: document.querySelector('[data-aiwp-mode-status]')?.textContent
  })).catch(() => null));
  throw error;
} finally { if(browser)await browser.close(); await server[Symbol.asyncDispose](); }
