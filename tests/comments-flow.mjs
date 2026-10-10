import assert from 'node:assert/strict';
import { randomUUID } from 'node:crypto';
import { chromium } from 'playwright';
import { bootWordPress, phpJson, projectRoot } from '../tools/playground.mjs';

const password = randomUUID();
const server = await bootWordPress(9414, false, projectRoot, '7.1.2', 'en');
let browser;
try {
  const fixture = await phpJson(server, `
    foreach (array('comments.php','comments-empty.php') as $file) { token_get_all(file_get_contents(AIWP_DIR.'includes/'.$file), TOKEN_PARSE); }
    $post = wp_insert_post(array('post_type'=>'post','post_status'=>'publish','post_title'=>'Discussion test','comment_status'=>'open','ping_status'=>'open'));
    $closed = wp_insert_post(array('post_type'=>'post','post_status'=>'publish','post_title'=>'Closed test','comment_status'=>'closed','ping_status'=>'closed'));
    $comment = wp_insert_comment(array('comment_post_ID'=>$post,'comment_content'=>'PRESERVED_COMMENT_SENTINEL','comment_author'=>'Original author','comment_approved'=>1));
    update_option('default_comment_status','closed');
    wp_set_password('${password}',1);
    echo wp_json_encode(array('post'=>$post,'closed'=>$closed,'comment'=>$comment,'disabled'=>aiwp_comments_disabled()));
  `);
  assert.equal(fixture.disabled,true,'New and existing sites default to disabled');
  browser = await chromium.launch({headless:true,channel:'chrome'});
  const publicContext = await browser.newContext();
  const page = await publicContext.newPage();
  const url=server.serverUrl+'/?p='+fixture.post;
  await page.goto(url);
  assert.equal(await page.locator('#commentform,#comments').count(),0);
  assert.ok(!(await page.content()).includes('PRESERVED_COMMENT_SENTINEL'));
  const blocked=await publicContext.request.post(server.serverUrl+'/wp-comments-post.php',{form:{comment_post_ID:String(fixture.post),author:'Test author',email:'test@example.org',comment:'BLOCKED_COMMENT_SENTINEL'}});
  assert.equal(blocked.status(),403);
  assert.equal((await publicContext.request.get(server.serverUrl+'/?rest_route=/wp/v2/comments')).status(),403);
  assert.equal((await publicContext.request.get(server.serverUrl+'/?rest_route=/wp/v2/comments/'+fixture.comment)).status(),403);
  assert.equal((await publicContext.request.get(server.serverUrl+'/?feed=comments-rss2')).status(),403);
  const checks=await phpJson(server, `
    $_SERVER['REMOTE_ADDR']='127.0.0.1';
    $results=array();
    foreach (array('comment','pingback','trackback') as $type) {
      $r=wp_new_comment(array('comment_post_ID'=>${fixture.post},'comment_type'=>$type,'comment_content'=>'Blocked '.$type,'comment_author'=>'Audit','comment_author_email'=>'audit@example.org','user_id'=>1),true);
      $results[$type]=is_wp_error($r) && 'aiwp_comments_disabled'===$r->get_error_code();
    }
    $request=new WP_REST_Request('POST','/wp/v2/comments');
    $request->set_param('post',${fixture.post}); $request->set_param('content','Blocked administrator REST comment');
    $results['admin_rest']=rest_do_request($request)->get_status()===403;
    $results['open']=comments_open(${fixture.post}); $results['pings']=pings_open(${fixture.post});
    $results['preserved']=get_comment(${fixture.comment})->comment_content==='PRESERVED_COMMENT_SENTINEL';
    $results['blocks']=render_block(array('blockName'=>'core/latest-comments','attrs'=>array(),'innerBlocks'=>array(),'innerHTML'=>'','innerContent'=>array()))==='';
    $methods=apply_filters('xmlrpc_methods',array('pingback.ping'=>'callback','wp.getPosts'=>'callback'));
    $results['xmlrpc']=!isset($methods['pingback.ping']) && isset($methods['wp.getPosts']);
    $uid=wp_insert_user(array('user_login'=>'discussion-editor','user_pass'=>wp_generate_password(),'role'=>'editor'));
    wp_set_current_user($uid); $results['unauthorized']=aiwp_sanitize_disable_comments('0')==='1';
    wp_set_current_user(1); $results['invalid']=aiwp_sanitize_disable_comments(array('0'))==='1';
    echo wp_json_encode($results);
  `);
  assert.deepEqual(checks,{comment:true,pingback:true,trackback:true,admin_rest:true,open:false,pings:false,preserved:true,blocks:true,xmlrpc:true,unauthorized:true,invalid:true});
  const admin=await browser.newContext(); const editor=await admin.newPage();
  await editor.goto(server.serverUrl+'/wp-login.php');
  await editor.locator('#user_login').fill('admin'); await editor.locator('#user_pass').fill(password);
  await Promise.all([editor.waitForNavigation(),editor.locator('#wp-submit').click()]);
  await editor.goto(server.serverUrl+'/wp-admin/admin.php?page=aiwp-comments');
  assert.equal(await editor.locator('#aiwp-disable-comments').isChecked(),true);
  assert.match(await editor.locator('.wrap').innerText(),/Disable comments across the website/);
  // Settings API rejects forged saves; the real form then proves unchecked values persist.
  const forged=await admin.request.post(server.serverUrl+'/wp-admin/options.php',{form:{option_page:'aiwp_discussion',action:'update',_wpnonce:'invalid',aiwp_disable_comments:'0'}});
  assert.equal(forged.status(),403);
  assert.equal(await phpJson(server,'echo wp_json_encode(aiwp_comments_disabled());'),true);
  await editor.locator('#aiwp-disable-comments').uncheck();
  await Promise.all([editor.waitForNavigation(),editor.locator('#submit').click()]);
  assert.equal(await editor.locator('#aiwp-disable-comments').isChecked(),false);
  await page.goto(url);
  assert.equal(await page.locator('#commentform').count(),1);
  assert.ok((await page.content()).includes('PRESERVED_COMMENT_SENTINEL'));
  assert.deepEqual(await phpJson(server,`echo wp_json_encode(array(comments_open(${fixture.post}),comments_open(${fixture.closed}),pings_open(${fixture.post}),get_option('default_comment_status')));`),[true,false,true,'closed']);
  const accepted=await publicContext.request.post(server.serverUrl+'/wp-comments-post.php',{form:{comment_post_ID:String(fixture.post),author:'New author',email:'new@example.org',comment:'ALLOWED_COMMENT_SENTINEL'},maxRedirects:0});
  assert.equal(accepted.status(),302);
  await phpJson(server,`update_option('wsp_languages',array('ui'=>'cs','public'=>'cs','content'=>'cs')); echo 'true';`);
  await editor.goto(server.serverUrl+'/wp-admin/admin.php?page=aiwp-comments');
  assert.match(await editor.locator('.wrap').innerText(),/Zakázat komentáře na celém webu/);
  await editor.locator('#aiwp-disable-comments').check();
  await Promise.all([editor.waitForNavigation(),editor.locator('#submit').click()]);
  await page.goto(url);
  assert.equal(await page.locator('#commentform,#comments').count(),0);
  const preserved=await phpJson(server,`global $wpdb; echo wp_json_encode(array('count'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID=%d",${fixture.post})),'status'=>get_post(${fixture.post})->comment_status));`);
  assert.deepEqual(preserved,{count:2,status:'open'});
  await phpJson(server,`require_once ABSPATH.'wp-admin/includes/plugin.php'; deactivate_plugins('ai-web-studio/ai-web-studio.php'); echo 'true';`);
  await page.goto(url); assert.equal(await page.locator('#commentform').count(),1);
  console.log('PASS: comments default off, frontend/REST/feed hidden, direct and privileged creation blocked, pingbacks/trackbacks blocked, native EN/CS settings with CSRF/permissions, reversible toggle and deactivation preserve data.');
} finally { if(browser) await browser.close(); await server[Symbol.asyncDispose](); }
