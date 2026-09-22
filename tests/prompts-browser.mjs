import assert from 'node:assert/strict';
import path from 'node:path';
import { chromium } from 'playwright';
import { phpJson, projectRoot } from '../tools/playground.mjs';

export async function runPromptChecks(server) {
  const checked = await phpJson(server, 'require "/aiwp-tests/prompt-checks.php";');
  assert.ok(checked.passed.length > 40);
  console.log(`PASS: ${checked.passed.length} prompt builder validation, saved context and permission checks.`);

  const original = await phpJson(server, 'echo wp_json_encode(get_option("aiwp_settings", array()));');
  const fixture = await phpJson(server, `
    update_option('aiwp_settings', array('font'=>'lora', 'accent'=>'#346789', 'width'=>1190, 'css'=>'.prompt-browser-shared { color: #346789; }'));
    $selected = wp_insert_post(array('post_type'=>'page','post_status'=>'draft','post_title'=>'Prompt browser selected'));
    $other = wp_insert_post(array('post_type'=>'page','post_status'=>'private','post_title'=>'Prompt browser unrelated','post_content'=>'PROMPT_BROWSER_UNSELECTED_SECRET'));
    $trash = wp_insert_post(array('post_type'=>'page','post_status'=>'trash','post_title'=>'Prompt browser trash'));
    update_post_meta($selected, '_aiwp_enabled', true);
    update_post_meta($selected, '_aiwp_html', '<section class="prompt-browser-selected"><h1>Český nadpis</h1></section>');
    update_post_meta($selected, '_aiwp_css', '.prompt-browser-selected { color: #123456; }');
    update_post_meta($selected, '_aiwp_js', 'const promptBrowserSelected = true;');
    update_post_meta($selected, '_aiwp_seo_description', 'Prompt browser saved SEO');
    echo wp_json_encode(array('selected'=>$selected,'other'=>$other,'trash'=>$trash,'header'=>aiwp_get_part_id('header'),'footer'=>aiwp_get_part_id('footer')));
  `);
  let browser;
  try {
    browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    await context.addInitScript(() => {
      Object.defineProperty(navigator, 'clipboard', {
        configurable: true,
        value: { writeText: async text => { window.copiedPrompt = text; } },
      });
    });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(server.serverUrl + '/wp-admin/admin.php?page=aiwp-prompts');
    if (await page.locator('#user_login').count()) {
      await page.locator('#user_login').fill('admin');
      await page.locator('#user_pass').fill('aiwp-local-test');
      await Promise.all([page.waitForNavigation(), page.locator('#wp-submit').click()]);
    }
    await page.locator('#aiwp-prompts-form').waitFor();
    assert.equal(await page.locator('#adminmenu a[href="admin.php?page=aiwp-prompts"]').count(), 1);
    const kind = page.locator('#aiwp-prompt-kind');
    const instructions = page.locator('#aiwp-prompt-instructions');
    const generate = page.locator('#aiwp-prompt-generate');
    const output = page.locator('#aiwp-prompt-output');
    const result = page.locator('#aiwp-prompt-result');
    const copy = page.locator('#aiwp-prompt-copy');
    assert.equal(await kind.locator('option').count(), 5);
    assert.equal(await generate.isDisabled(), true, 'An empty request cannot be generated');
    assert.equal(await result.isVisible(), false);
    assert.equal(await page.evaluate(() => window.copiedPrompt), undefined, 'Nothing is copied automatically');
    assert.ok(!await page.content().then(html => html.includes('PROMPT_BROWSER_UNSELECTED_SECRET')), 'Hub HTML does not preload all page contents');

    const config = await page.evaluate(() => window.aiwpPrompts);
    const post = fields => context.request.post(config.ajaxUrl, { form: { action: 'aiwp_build_prompt', nonce: config.nonce, kind: 'page', instructions: 'Valid request', ...fields } });
    for (const fields of [{ nonce: 'invalid' }, { kind: 'unknown' }, { instructions: '   ' }, { instructions: 'x'.repeat(10001) }, { 'instructions[]': 'invalid' }, { kind: 'edit', page_id: fixture.selected + 'bad' }, { kind: 'edit', page_id: fixture.trash }]) {
      const response = await post(fields);
      assert.ok(response.status() >= 400, `Invalid AJAX request rejected: ${Object.keys(fields).join(', ')}`);
      const payload = await response.json();
      assert.equal(payload.success, false);
      assert.ok(!JSON.stringify(payload).includes('prompt-browser-selected'));
    }
    const getResponse = await context.request.get(config.ajaxUrl, { params: { action: 'aiwp_build_prompt', nonce: config.nonce, kind: 'page', instructions: 'Valid request' } });
    assert.equal(getResponse.status(), 405, 'Prompt reads require POST with nonce');
    const searchResponse = await context.request.post(config.ajaxUrl, { form: { action: 'aiwp_prompt_pages', nonce: config.nonce, search: 'Prompt browser' } });
    const searchPayload = await searchResponse.json();
    assert.equal(searchPayload.success, true);
    assert.deepEqual(searchPayload.data.pages.map(item => item.id).sort((a, b) => a - b), [fixture.selected, fixture.other].sort((a, b) => a - b));
    assert.ok(searchPayload.data.pages.every(item => Object.keys(item).sort().join(',') === 'id,title'), 'Page search returns only titles and IDs');
    assert.ok(!JSON.stringify(searchPayload).includes('PROMPT_BROWSER_UNSELECTED_SECRET'));
    const contentSearch = await context.request.post(config.ajaxUrl, { form: { action: 'aiwp_prompt_pages', nonce: config.nonce, search: 'PROMPT_BROWSER_UNSELECTED_SECRET' } });
    assert.deepEqual((await contentSearch.json()).data.pages, [], 'Page picker searches titles rather than private page content');
    const invalidSearch = await context.request.post(config.ajaxUrl, { form: { action: 'aiwp_prompt_pages', nonce: config.nonce, 'search[]': 'invalid' } });
    assert.equal(invalidSearch.status(), 400);
    const anonymous = await browser.newContext();
    const anonymousResponse = await anonymous.request.post(config.ajaxUrl, { form: { action: 'aiwp_build_prompt', nonce: config.nonce, kind: 'edit', instructions: 'Read selected content', page_id: String(fixture.selected) } });
    assert.ok(anonymousResponse.status() >= 400 && !(await anonymousResponse.text()).includes('prompt-browser-selected'), 'Anonymous request cannot read selected code');
    await anonymous.close();

    const request = 'Navrhni čitelný vzhled. </textarea><script>window.promptInjected = true;</script>';
    const build = async () => {
      await generate.click();
      await result.waitFor({ state: 'visible' });
      assert.ok(await output.inputValue());
      assert.equal(await output.evaluate(element => element.scrollTop), 0, 'A fresh prompt is shown from its beginning');
      return output.inputValue();
    };
    await instructions.fill(request);
    const stylePrompt = await build();
    assert.ok(stylePrompt.includes('Lora') && stylePrompt.includes('.prompt-browser-shared'));
    assert.ok(stylePrompt.includes('font-size: clamp') && stylePrompt.includes('line-height: clamp'));
    assert.ok(stylePrompt.includes(request), 'The full request is shown as literal text');
    assert.equal(await output.getAttribute('readonly'), '');
    assert.equal(await page.evaluate(() => window.promptInjected), undefined, 'Markup in prompt text does not execute');
    assert.ok((await page.locator('#aiwp-prompt-destination-link').getAttribute('href')).includes('page=aiwp-settings'));
    await copy.click();
    await page.waitForFunction(() => typeof window.copiedPrompt === 'string');
    assert.equal(await page.evaluate(() => window.copiedPrompt), stylePrompt);
    await page.evaluate(() => { navigator.clipboard.writeText = async () => { throw new Error('Clipboard denied for test'); }; });
    await copy.click();
    await page.waitForFunction(() => document.getElementById('aiwp-prompt-copy-status').textContent.includes('Ctrl+C'));
    const selection = await output.evaluate(element => ({ start: element.selectionStart, end: element.selectionEnd, length: element.value.length }));
    assert.deepEqual(selection, { start: 0, end: stylePrompt.length, length: stylePrompt.length }, 'Denied clipboard selects the complete prompt for manual copy');

    await instructions.fill('Vytvoř přehlednou stránku pro malou firmu.');
    assert.equal(await result.isVisible(), false, 'Changing the request immediately removes the old result');
    assert.equal(await output.inputValue(), '');
    assert.equal(await copy.isDisabled(), true);
    for (const value of ['page', 'header', 'footer']) {
      await kind.selectOption(value);
      assert.equal(await result.isVisible(), false, 'Changing prompt kind invalidates the previous result');
      const prompt = await build();
      const destination = await page.locator('#aiwp-prompt-destination-link').getAttribute('href');
      assert.ok(prompt.includes('Lora') && prompt.includes('.prompt-browser-shared'));
      assert.ok(!prompt.includes('PROMPT_BROWSER_UNSELECTED_SECRET'));
      if (value === 'page') {
        assert.ok(destination.includes('post-new.php') && destination.includes('post_type=page'));
        assert.ok(!prompt.includes('prompt-browser-selected'));
      } else {
        assert.ok(destination.includes('post=' + fixture[value]));
        assert.ok(prompt.includes(`[aiwp_menu location="${value === 'header' ? 'primary' : 'footer'}"]`));
      }
    }

    await kind.selectOption('edit');
    assert.equal(await generate.isDisabled(), true, 'Editing requires an explicitly selected page');
    await page.locator('#aiwp-prompt-page-search').fill('Prompt browser selected');
    await page.locator('#aiwp-prompt-page-search-button').click();
    const selectedOption = page.locator(`#aiwp-prompt-page-id option[value="${fixture.selected}"]`);
    await selectedOption.waitFor({ state: 'attached' });
    assert.equal(await page.locator('#aiwp-prompt-page-id option').count(), 2);
    await page.locator('#aiwp-prompt-page-id').selectOption(String(fixture.selected));
    const editPrompt = await build();
    assert.ok(editPrompt.includes('prompt-browser-selected') && editPrompt.includes('promptBrowserSelected') && editPrompt.includes('Prompt browser saved SEO'));
    assert.ok(!editPrompt.includes('PROMPT_BROWSER_UNSELECTED_SECRET'));
    assert.ok((await page.locator('#aiwp-prompt-destination-link').getAttribute('href')).includes('post=' + fixture.selected));
    await page.locator('#aiwp-prompt-page-id').selectOption('');
    assert.equal(await result.isVisible(), false, 'Changing selected page invalidates saved code output');
    assert.equal(await generate.isDisabled(), true);

    // Simulate a transport which still resolves an old response after AbortController.
    // This proves the form checks its revision as well as requesting cancellation.
    await kind.selectOption('page');
    await page.evaluate(() => {
      const originalFetch = window.fetch.bind(window);
      window.fetch = (url, options) => {
        if (String(options?.body).includes('action=aiwp_build_prompt')) {
          return new Promise(resolve => { window.releaseStalePrompt = () => resolve(new Response(JSON.stringify({
            success: true,
            data: { prompt: 'STALE_PROMPT_RESPONSE', destination: { label: 'Stale', url: location.origin + '/wp-admin/post-new.php?post_type=page', help: 'Stale destination' }, notices: [] },
          }), { headers: { 'Content-Type': 'application/json' } })); });
        }
        return originalFetch(url, options);
      };
      window.restorePromptFetch = () => { window.fetch = originalFetch; };
    });
    await generate.click();
    await page.waitForFunction(() => typeof window.releaseStalePrompt === 'function');
    await instructions.fill('Toto je novější zadání, stará odpověď už neplatí.');
    await page.evaluate(async () => {
      window.releaseStalePrompt();
      await new Promise(resolve => setTimeout(resolve, 0));
      window.restorePromptFetch();
    });
    assert.equal(await output.inputValue(), '', 'An old response cannot restore a prompt after editing the request');
    assert.equal(await result.isVisible(), false);
    const currentPrompt = await build();
    assert.ok(currentPrompt.includes('Toto je novější zadání') && !currentPrompt.includes('STALE_PROMPT_RESPONSE'));
    await page.screenshot({ path: path.join(projectRoot, 'test-results/prompt-hub-desktop.png'), fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'Prompt form fits a narrow admin viewport');
    await page.screenshot({ path: path.join(projectRoot, 'test-results/prompt-hub-mobile.png'), fullPage: true });
    assert.deepEqual(errors, [], 'Prompt UI has no uncaught JavaScript errors');
    console.log('PASS: Native prompt AJAX validation, private page search, five destinations, literal text preview, copy fallback and stale response protection.');
  } finally {
    if (browser) await browser.close();
    const encoded = Buffer.from(JSON.stringify(original)).toString('base64');
    await phpJson(server, `update_option('aiwp_settings',json_decode(base64_decode('${encoded}'),true)); foreach (array(${fixture.selected},${fixture.other},${fixture.trash}) as $id) { wp_delete_post($id,true); } echo 'true';`);
  }
}
