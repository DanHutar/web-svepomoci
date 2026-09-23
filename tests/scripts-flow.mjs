import { bootWordPress, projectRoot } from '../tools/playground.mjs';
import { runScriptChecks } from './scripts.mjs';
const server = await bootWordPress(9408, false, projectRoot, '7.1.2');
try { await runScriptChecks(server); }
finally { await server[Symbol.asyncDispose](); }
