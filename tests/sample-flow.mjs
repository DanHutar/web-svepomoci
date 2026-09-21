import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { bootWordPress, phpJson } from '../tools/playground.mjs';

const server = await bootWordPress(9402, false);
let browser;
try {
  const id = await phpJson(server, 'wp_set_password("aiwp-local-test", 1); echo wp_insert_post(["post_type"=>"page", "post_title"=>"Ukázka z tlačítka", "post_status"=>"publish", "post_content"=>"<p id=original-content>Původní obsah</p>"]);');
  browser = await chromium.launch({ headless: true, channel: 'chrome' });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  const page = await context.newPage();
  await page.goto(`${server.serverUrl}/wp-admin/post.php?post=${id}&action=edit`);
  await page.locator('#user_login').fill('admin');
  await page.locator('#user_pass').fill('aiwp-local-test');
  await Promise.all([page.waitForNavigation(), page.locator('#wp-submit').click()]);
  await page.waitForFunction(() => document.querySelector('.CodeMirror')?.CodeMirror);
  assert.equal(await page.locator('#aiwp-enabled').isChecked(), false);
  await page.locator('[data-aiwp-sample]').click();
  const enabledAfterSample = await page.locator('#aiwp-enabled').isChecked();
  console.log('AI mode after inserting sample:', enabledAfterSample);
  if (process.argv.includes('--expect-active')) assert.equal(enabledAfterSample, true, 'Sample insertion enables AI mode');
  await page.locator('[data-aiwp-refresh]').click();
  const preview = page.frameLocator('.aiwp-preview-frame');
  await preview.locator('.moje-stranka').waitFor();
  const previewBackground = await preview.locator('.moje-stranka').evaluate(el => getComputedStyle(el).backgroundColor);
  assert.equal(previewBackground, 'rgb(246, 248, 251)');
  await Promise.all([page.waitForNavigation(), page.locator('#publish').click()]);
  const publicPage = await context.newPage();
  await publicPage.goto(`${server.serverUrl}/?page_id=${id}`);
  if (!enabledAfterSample) {
    assert.equal(await publicPage.locator('.moje-stranka').count(), 0);
    assert.equal(await publicPage.locator('#original-content').count(), 1);
    console.log('REPRODUCED: styled preview but original frontend content when mode stays off.');
    await page.locator('#aiwp-enabled').check();
    await Promise.all([page.waitForNavigation(), page.locator('#publish').click()]);
    await publicPage.reload();
  }
  await publicPage.locator('.moje-stranka').waitFor();
  assert.equal(await publicPage.locator('.moje-stranka').evaluate(el => getComputedStyle(el).backgroundColor), previewBackground);
  assert.equal(await publicPage.locator('.moje-stranka__karty').evaluate(el => getComputedStyle(el).display), 'grid');
  assert.equal(await publicPage.locator('.moje-stranka__karty').evaluate(el => getComputedStyle(el).gridTemplateColumns.split(' ').length), 3);
  await publicPage.setViewportSize({ width: 390, height: 844 });
  assert.equal(await publicPage.locator('.moje-stranka__karty').evaluate(el => getComputedStyle(el).gridTemplateColumns.split(' ').length), 1);
  console.log('PASS: native sample save, frontend background, desktop grid and mobile layout.');
} finally {
  if (browser) await browser.close();
  await server[Symbol.asyncDispose]();
}
