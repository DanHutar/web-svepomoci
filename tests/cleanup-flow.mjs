import { bootWordPress, projectRoot } from '../tools/playground.mjs';
import { runCleanupChecks } from './cleanup.mjs';

const server = await bootWordPress(9406, false, projectRoot, '7.1.2');
try {
  await runCleanupChecks(server);
} finally {
  await server[Symbol.asyncDispose]();
}
