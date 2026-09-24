import { bootWordPress, phpJson, projectRoot } from '../tools/playground.mjs';
const server = await bootWordPress(9409, false, projectRoot, '7.1.2');
try {
  console.log(await phpJson(server, 'require_once ABSPATH . "wp-admin/includes/plugin.php"; deactivate_plugins("ai-web-studio/ai-web-studio.php"); require "/aiwp-tests/webp.php";'));
} finally { await server[Symbol.asyncDispose](); }
