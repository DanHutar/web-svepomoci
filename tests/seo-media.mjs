import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { phpJson } from '../tools/playground.mjs';

export async function runSeoMediaChecks(server) {
  const fixture = await phpJson(server, `
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_set_password('aiwp-local-test',1);
    $page = wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'SEO media test'));
    $images = array();
    foreach (array(1,2) as $number) {
      $image = imagecreatetruecolor(40,20);
      imagefill($image,0,0,imagecolorallocate($image,50 * $number,100,150));
      $tmp = wp_tempnam(); imagepng($image,$tmp); imagedestroy($image);
      $id = media_handle_sideload(array('name'=>'seo-media-'.$number.'.png','tmp_name'=>$tmp),0);
      if (is_wp_error($id)) { throw new Exception($id->get_error_message()); }
      $images[] = array('id'=>$id,'url'=>wp_get_attachment_url($id));
    }
    echo wp_json_encode(array('page'=>$page,'images'=>$images));
  `);
  let browser;
  try {
    browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const page = await browser.newPage();
    // WASM serves dynamic CSS/JS serially; allow navigation to finish, keep assertions unchanged.
    page.setDefaultNavigationTimeout(90000);
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(`${server.serverUrl}/wp-login.php`, { waitUntil: 'domcontentloaded' });
    await page.locator('#user_login').fill('admin');
    await page.locator('#user_pass').fill('aiwp-local-test');
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#wp-submit').click()]);
    await page.goto(`${server.serverUrl}/wp-admin/post.php?post=${fixture.page}&action=edit`, { waitUntil: 'domcontentloaded' });
    await page.locator('[data-aiwp-media]').waitFor();
    assert.equal(await page.locator('#aiwp-seo-title-input').count(), 1);
    const title = page.getByLabel('SEO titulek', { exact: true });
    await title.fill('Žluťoučký 🌻');
    assert.equal(await page.locator('[data-aiwp-counter="aiwp-seo-title-input"]').textContent(), '11');
    const input = page.locator('#aiwp-seo-image');
    const button = page.locator('[data-aiwp-media]');
    const modal = page.locator('.media-modal');
    const choose = async image => {
      await button.click();
      await modal.waitFor();
      const browse = modal.locator('#menu-item-browse');
      if (await browse.isVisible()) await browse.click();
      await modal.locator(`.attachment[data-id="${image.id}"]`).click();
      await modal.locator('.media-button-select').click();
      await modal.waitFor({ state: 'hidden' });
      assert.equal(await input.inputValue(), image.url);
    };
    await choose(fixture.images[0]);
    await choose(fixture.images[1]);
    await button.click();
    await modal.waitFor();
    await modal.locator('.media-modal-close').click();
    assert.equal(await input.inputValue(), fixture.images[1].url, 'Cancel preserves the previous image');
    await page.evaluate(() => { window.aiwpTestMedia = window.wp.media; window.wp.media = undefined; });
    await button.click();
    assert.match(await page.locator('[data-aiwp-media-status]').innerText(), /Knihovna médií se nenačetla/);
    await page.evaluate(() => { window.wp.media = window.aiwpTestMedia; delete window.aiwpTestMedia; });
    await button.click();
    await modal.waitFor();
    assert.ok(await page.locator('[data-aiwp-media-status]').isHidden());
    await modal.locator('.media-modal-close').click();
    await Promise.all([page.waitForNavigation({ waitUntil: 'domcontentloaded' }), page.locator('#publish').click()]);
    assert.equal(await input.inputValue(), fixture.images[1].url, 'Selected image survives saving and reload');
    assert.equal(await title.inputValue(), 'Žluťoučký 🌻');
    await page.goto(`${server.serverUrl}/?page_id=${fixture.page}`, { waitUntil: 'domcontentloaded' });
    assert.equal(await page.locator('meta[property="og:image"]').getAttribute('content'), fixture.images[1].url);
    assert.equal(await page.locator('meta[name="twitter:image"]').getAttribute('content'), fixture.images[1].url);
    assert.deepEqual(errors, [], 'SEO initialization and media selection have no JavaScript errors');
    console.log('PASS: SEO title counter, media modal, image selection/replacement/cancel, missing-library feedback, save and sharing metadata.');
  } finally {
    if (browser) await browser.close();
    await phpJson(server, `wp_delete_post(${fixture.page},true); foreach(array(${fixture.images.map(i => i.id).join(',')}) as $id) { wp_delete_attachment($id,true); } echo 'true';`);
  }
}
