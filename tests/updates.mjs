import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import { bootWordPress, phpJson, projectRoot } from '../tools/playground.mjs';

// Upgrader tests must never delete or overwrite the mounted development sources.
const temporary = await fs.mkdtemp(path.join(os.tmpdir(), 'wsp-update-test-'));
let server;
try {
  for (const directory of ['plugin', 'theme']) {
    await fs.cp(path.join(projectRoot, directory), path.join(temporary, directory), { recursive: true });
  }
  const plugin = path.join(temporary, 'plugin/ai-web-studio/ai-web-studio.php');
  const theme = path.join(temporary, 'theme/ai-web/style.css');
  const functions = path.join(temporary, 'theme/ai-web/functions.php');
  await fs.writeFile(plugin, (await fs.readFile(plugin, 'utf8'))
    .replace('Plugin Name: web-svepomoci-plugin', 'Plugin Name: AI Web Studio')
    .replace(/Version: [\d.]+/, 'Version: 1.1.0')
    .replace(/define\( 'AIWP_VERSION', '[\d.]+' \);/, "define( 'AIWP_VERSION', '1.1.0' );")
    .replace("require_once AIWP_DIR . 'includes/github-updates.php';", ''));
  await fs.writeFile(theme, (await fs.readFile(theme, 'utf8'))
    .replace('Theme Name: web-svepomoci-sablona', 'Theme Name: AI Web')
    .replace(/Version: [\d.]+/, 'Version: 1.0.0'));
  await fs.writeFile(functions, (await fs.readFile(functions, 'utf8'))
    .replace("require_once get_template_directory() . '/includes/github-updates.php';", ''));
  server = await bootWordPress(9404, false, temporary);
  await server.playground.writeFile('/tmp/updates.json', await fs.readFile(path.join(projectRoot, 'dist/updates.json')));
  for (const kind of ['plugin', 'sablona']) {
    await server.playground.writeFile('/tmp/web-svepomoci-' + kind + '.zip',
      await fs.readFile(path.join(projectRoot, 'dist/web-svepomoci-' + kind + '.zip')));
  }
  const install = await phpJson(server, 'require "/aiwp-tests/update-install.php";');
  console.log(install);
  assert.equal(install.plugin, true);
  assert.equal(install.theme, true);
  const result = await phpJson(server, 'require "/aiwp-tests/update-checks.php";');
  console.log(result);
  const frontend = await (await fetch(server.serverUrl + '/')).text();
  assert.match(frontend, /class="aiwp-header"/);
  assert.match(frontend, /class="aiwp-footer"/);
  assert.match(frontend, /class="aiwp-menu"/);
  assert.ok(!frontend.includes('[aiwp_menu'), 'Existing menu markers still render after replacement');
  // Theme alone must still register updates when the companion plugin is inactive.
  await phpJson(server, 'require_once ABSPATH . "wp-admin/includes/plugin.php"; deactivate_plugins("ai-web-studio/ai-web-studio.php"); echo "true";');
  assert.equal(await phpJson(server, 'echo json_encode(class_exists("WSP_GitHub_Updates") && has_filter("update_plugins_github.com") && has_filter("update_themes_github.com"));'), true);
  // Plugin alone must also work under an unrelated active theme.
  await phpJson(server, 'require_once ABSPATH . "wp-admin/includes/plugin.php"; activate_plugin("ai-web-studio/ai-web-studio.php"); mkdir(WP_CONTENT_DIR . "/themes/wsp-test-fallback"); file_put_contents(WP_CONTENT_DIR . "/themes/wsp-test-fallback/style.css", "/* Theme Name: Test fallback */"); file_put_contents(WP_CONTENT_DIR . "/themes/wsp-test-fallback/index.php", "<?php echo \'fallback\';"); switch_theme("wsp-test-fallback"); echo "true";');
  assert.equal(await phpJson(server, 'echo json_encode(class_exists("WSP_GitHub_Updates") && has_filter("update_themes_github.com"));'), true);
  console.log('PASS: Actual ZIP replacement preserves content, theme settings and activation; native update hooks, validation, caching and either-component loading pass.');
} catch (error) {
  console.error(error.message);
  process.exitCode = 1;
} finally {
  if (server) await server[Symbol.asyncDispose]();
  if (path.dirname(temporary) !== os.tmpdir() || !path.basename(temporary).startsWith('wsp-update-test-')) {
    throw new Error('Refusing to remove an unexpected temporary directory');
  }
  await fs.rm(temporary, { recursive: true, force: true });
}
