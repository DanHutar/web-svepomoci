import { bootWordPress, projectRoot } from '../tools/playground.mjs';
import { runSeoMediaChecks } from './seo-media.mjs';
const server = await bootWordPress(9411, false, projectRoot, '7.1.2');
try { await runSeoMediaChecks(server); }
finally { await server[Symbol.asyncDispose](); }
