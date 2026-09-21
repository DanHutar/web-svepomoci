import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { bootWordPress, phpJson } from '../tools/playground.mjs';

const server = await bootWordPress(9403, false);
let browser;
try {
  const result = await phpJson(server, 'wp_set_password("aiwp-local-test", 1); require "/aiwp-tests/menu-checks.php";');
  for (const message of result.passed) console.log('PASS:', message);
  const ids = result.fixtures;
  browser = await chromium.launch({ headless: true, channel: 'chrome' });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  context.setDefaultTimeout(15000);
  const frontend = await context.newPage();
  const errors = [];
  frontend.on('pageerror', error => errors.push(error.message));
  await frontend.goto(`${server.serverUrl}/?page_id=${ids.pageId}`);
  assert.equal(await frontend.locator('.aiwp-header #menu-test-header').count(), 1);
  assert.equal(await frontend.locator('.aiwp-footer #menu-test-footer').count(), 1);
  assert.equal(await frontend.locator('#menu-test-header nav a').count(), 2);
  assert.equal(await frontend.locator('#menu-test-footer nav a').textContent(), 'Footer contact');
  assert.equal(await frontend.locator('#menu-test-page a').textContent(), 'Footer contact', 'Page HTML also expands its menu marker');
  const currentLink = frontend.locator('#menu-test-header .sub-menu .current-menu-item > a');
  assert.equal(await currentLink.textContent(), 'Current test page');
  assert.equal(await currentLink.getAttribute('aria-current'), 'page');
  assert.equal(await frontend.locator('#menu-test-header .current-menu-ancestor').count(), 1);
  assert.ok(!(await frontend.locator('body').textContent()).includes('[aiwp_menu'), 'Frontend contains resolved menus');
  console.log('PASS: Actual custom header/footer/page render their assigned native menus, hierarchy and current-page state.');

  const originalHeader = await phpJson(server, `echo wp_json_encode(aiwp_get_document(${ids.headerId}));`);
  await phpJson(server, `
    $id = wp_update_nav_menu_item(${ids.menuId}, ${ids.itemId}, array(
      'menu-item-title' => 'Updated in Appearance Menus', 'menu-item-type' => 'post_type',
      'menu-item-object' => 'page', 'menu-item-object-id' => ${ids.pageId},
      'menu-item-parent-id' => ${ids.parentId}, 'menu-item-status' => 'publish', 'menu-item-position' => 2
    ));
    echo wp_json_encode(!is_wp_error($id));
  `);
  await frontend.reload();
  assert.equal(await currentLink.textContent(), 'Updated in Appearance Menus');
  assert.deepEqual(await phpJson(server, `echo wp_json_encode(aiwp_get_document(${ids.headerId}));`), originalHeader);
  console.log('PASS: Editing a native menu immediately updates the frontend without changing saved header code.');

  await phpJson(server, `set_theme_mod('nav_menu_locations', array('primary' => ${ids.menuId})); echo 'true';`);
  await frontend.reload();
  assert.equal(await frontend.locator('#menu-test-footer nav a, #menu-test-page a').count(), 0);
  assert.equal(await frontend.locator('#menu-test-header nav a').count(), 2);
  await phpJson(server, `set_theme_mod('nav_menu_locations', array('primary' => ${ids.menuId}, 'footer' => ${ids.footerMenuId})); echo 'true';`);
  console.log('PASS: Removing the footer assignment leaves it empty and does not substitute a different menu.');

  const admin = await context.newPage();
  admin.on('pageerror', error => errors.push(error.message));
  admin.on('dialog', dialog => dialog.accept());
  await admin.goto(`${server.serverUrl}/wp-admin/nav-menus.php?menu=${ids.menuId}`);
  await admin.locator('#user_login').fill('admin');
  await admin.locator('#user_pass').fill('aiwp-local-test');
  await Promise.all([admin.waitForNavigation(), admin.locator('#wp-submit').click()]);
  assert.equal(await admin.locator(`#menu-item-${ids.itemId}`).count(), 1, 'Assigned menu is available in native Appearance Menus screen');

  for (const [kind, id, location, linkName] of [
    ['header', ids.headerId, 'primary', 'Updated in Appearance Menus'],
    ['footer', ids.footerId, 'footer', 'Footer contact'],
  ]) {
    await admin.goto(`${server.serverUrl}/wp-admin/post.php?post=${id}&action=edit`);
    await admin.waitForFunction(() => document.querySelector('#aiwp-panel-html .CodeMirror')?.CodeMirror);
    await admin.locator('[data-aiwp-refresh]').click();
    const preview = admin.frameLocator('.aiwp-preview-frame');
    await preview.getByRole('link', { name: linkName, exact: true }).waitFor();
    assert.ok(!(await preview.locator('body').textContent()).includes('[aiwp_menu'));

    const before = await admin.evaluate(() => {
      const html = document.querySelector('#aiwp-panel-html .CodeMirror').CodeMirror;
      html.setValue('<div id="keep-before">Before</div>\n<div id="keep-after">After</div>');
      html.setCursor({ line: 1, ch: 0 });
      return {
        html: html.getValue(),
        css: document.querySelector('#aiwp-panel-css .CodeMirror').CodeMirror.getValue(),
        js: document.querySelector('#aiwp-panel-js .CodeMirror').CodeMirror.getValue(),
      };
    });
    await admin.locator(`[data-aiwp-insert-menu][data-aiwp-location="${location}"]`).click();
    const after = await admin.evaluate(() => Object.fromEntries(['html', 'css', 'js'].map(key => [key, document.querySelector('#aiwp-panel-' + key + ' .CodeMirror').CodeMirror.getValue()])));
    const token = `[aiwp_menu location="${location}"]`;
    assert.ok(after.html.includes(token));
    assert.equal(after.html.replace(token, '').trim(), before.html.trim(), 'Insertion preserves surrounding HTML');
    assert.equal(after.css, before.css);
    assert.equal(after.js, before.js);
    await admin.locator('[data-aiwp-refresh]').click();
    await preview.getByRole('link', { name: linkName, exact: true }).waitFor();
    assert.equal(await preview.locator('#keep-before').textContent(), 'Before');
    assert.equal(await preview.locator('#keep-after').textContent(), 'After');
    console.log(`PASS: ${kind} preview resolves menu and insert button preserves the user's HTML, CSS and JS.`);
  }

  assert.deepEqual(errors, [], 'Frontend and editor have no uncaught JavaScript errors');
  console.log('PASS: Focused menu integration in WordPress 6.8.3 / PHP 8.3 / Chrome.');
} finally {
  if (browser) await browser.close();
  await server[Symbol.asyncDispose]();
}
