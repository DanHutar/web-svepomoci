import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// Tools stay outside the distribution. Install official PHP and Plugin Check first.
const root=fileURLToPath(new URL('../',import.meta.url));
const php=process.env.AIWP_PHP_PATH;
const pcp=process.env.AIWP_PCP_PATH;
if (!php || !pcp) throw new Error('Set AIWP_PHP_PATH to PHP and AIWP_PCP_PATH to the extracted official Plugin Check directory.');
const out=path.join(root,'test-results','native-phpcs-security.json');
fs.mkdirSync(path.dirname(out),{recursive:true});
// Remove only this generated report so a failed invocation cannot reuse stale results.
fs.rmSync(out,{force:true});
const args=process.platform==='win32' ? ['-d','extension_dir='+path.join(path.dirname(php),'ext'),'-d','extension=mbstring'] : [];
args.push(path.join(pcp,'vendor','bin','phpcs'),'--standard=WordPress',
  '--sniffs=WordPress.Security.EscapeOutput,WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput,WordPress.Security.SafeRedirect,WordPress.Security.PluginMenuSlug,WordPress.DB.PreparedSQL,WordPress.DB.PreparedSQLPlaceholders',
  '--extensions=php','--report=json','--report-file='+out,'plugin/ai-web-studio','theme/ai-web');
const result=spawnSync(php,args,{cwd:root,encoding:'utf8',timeout:180000,windowsHide:true});
if (result.error || !fs.existsSync(out)) throw new Error(result.error?.message || result.stderr || result.stdout || 'PHPCS did not create a report');
const report=JSON.parse(fs.readFileSync(out,'utf8'));
if (!report.totals || !Object.keys(report.files||{}).length) throw new Error('Incomplete PHPCS report');
console.log(JSON.stringify({files:Object.keys(report.files).length,...report.totals,report:out},null,2));
// Findings are intentionally not treated as a clean pass: review them individually.
process.exitCode=result.status===null ? 2 : result.status;
