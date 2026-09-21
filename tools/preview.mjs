import { bootWordPress, phpJson } from './playground.mjs';

const server = await bootWordPress();
await phpJson(server, 'require "/aiwp-tests/seed.php"; echo json_encode(aiwp_test_seed());');
console.log(`\nUkázkový web: ${server.serverUrl}\nAdministrace: ${server.serverUrl}/wp-admin/\nPouze dočasná lokální ukázka. Ukončení: Ctrl+C. Data ukázky se po ukončení nezachovají.`);
async function stop() { await server[Symbol.asyncDispose](); process.exit(0); }
process.on('SIGINT', stop);
process.on('SIGTERM', stop);
