import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import { bootWordPress, phpJson, projectRoot } from '../tools/playground.mjs';

const server = await bootWordPress(9415, false, projectRoot, '7.1.2', 'en');
const out = path.join(projectRoot, 'test-results');
await fs.mkdir(out, {recursive:true});
try {
  // Avoid loopback cron and update-service delays in the isolated security suite.
  await server.playground.mkdir('/wordpress/wp-content/mu-plugins');
  await server.playground.writeFile('/wordpress/wp-content/mu-plugins/aiwp-test-offline.php', `<?php
    if (!defined('DISABLE_WP_CRON')) define('DISABLE_WP_CRON',true);
    add_filter('pre_http_request', function() { return new WP_Error('test_offline','External HTTP disabled in regression'); }, PHP_INT_MAX);
  `);
  const result = await phpJson(server, 'require "/aiwp-tests/security-checks.php";');
  await fs.writeFile(path.join(out,'security-checks.json'),JSON.stringify(result,null,2));
  console.log(`PASS: ${result.passed.length} role, nonce, REST, input and SEO security checks.`);
  for (const action of ['aiwp_build_prompt','aiwp_prompt_pages']) {
    const response = await fetch(server.serverUrl+'/wp-admin/admin-ajax.php',{method:'POST',body:new URLSearchParams({action,nonce:'invalid'})});
    assert.ok(response.status>=400,`Anonymous ${action} rejected`);
  }
  console.log('PASS: anonymous HTTP requests cannot access prompt endpoints.');
  for (const [state,id] of Object.entries(result.private_fixtures)) {
    for (const suffix of ['', '&aiwp_styles=1', '&aiwp_script=page']) {
      const response=await fetch(server.serverUrl+'/?page_id='+id+suffix,{signal:AbortSignal.timeout(30000)});
      assert.ok(!(await response.text()).includes('PRIVATE_SECURITY_SENTINEL'),state+suffix+': no private code');
      if (state!=='password' || suffix==='&aiwp_script=page') assert.equal(response.status,404);
      if (suffix) assert.equal(response.headers.get('x-content-type-options'),'nosniff');
    }
  }
  console.log('PASS: 9 HTTP checks exclude draft, private and password-protected HTML/CSS/JS.');
} finally { await server[Symbol.asyncDispose](); }
