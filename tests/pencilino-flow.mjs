import {runCLI} from '@wp-playground/cli';
import {chromium} from 'playwright';
import assert from 'node:assert/strict';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const root=fileURLToPath(new URL('../',import.meta.url));
const server=await runCLI({command:'server',port:9438,login:true,php:'8.3',wp:'6.8.3',workers:1,
 'mount-before-install':[
  {hostPath:path.join(root,'plugin/ai-web-studio'),vfsPath:'/wordpress/wp-content/plugins/ai-web-studio'},
  {hostPath:path.join(root,'theme/ai-web'),vfsPath:'/wordpress/wp-content/themes/ai-web'},
  {hostPath:path.join(root,'../pencilino-main'),vfsPath:'/wordpress/wp-content/plugins/pencilino'}
 ],blueprint:{steps:[{step:'activatePlugin',pluginPath:'ai-web-studio/ai-web-studio.php'},
 {step:'activatePlugin',pluginPath:'pencilino/pencilino-by-rino.php'},
 {step:'activateTheme',themeFolderName:'ai-web'}]}});
const php=async code=>{const r=await server.playground.run({code:'<?php require "/wordpress/wp-load.php"; wp_set_current_user(1); '+code});assert.ok(!r.errors,r.errors);return JSON.parse(r.text);};
let browser;
try {
 const ids=await php(`
 $html='<h1>[aiwp_text id="title"]Original[/aiwp_text]</h1>[aiwp_richtext id="intro"]<p>Hello</p>[/aiwp_richtext][aiwp_button id="cta" url="/contact"]Contact[/aiwp_button]';
 $a=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'A'));
 $b=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'B'));
 foreach(array($a,$b) as $id){update_post_meta($id,'_aiwp_enabled',1);update_post_meta($id,'_aiwp_html',$html);}
 aiwp_ensure_parts();$h=aiwp_get_part_id('header');
 wp_update_post(array('ID'=>$h,'post_status'=>'publish'));update_post_meta($h,'_aiwp_enabled',1);update_post_meta($h,'_aiwp_html','<header>[aiwp_text id="title"]Shared[/aiwp_text]</header>');
 echo json_encode(array($a,$b,$h));`);
 browser=await chromium.launch({channel:'chrome',headless:true});const page=await browser.newPage();
 await page.goto(server.serverUrl+'/');
 // The CLI auto-login is served at /wp-admin/.
 await page.goto(server.serverUrl+'/wp-admin/');
 await page.goto(server.serverUrl+'/?page_id='+ids[0]);
 assert.equal(await page.locator('[data-pencil-field="byyourself-'+ids[0]+'.title"]').count(),1);
 assert.ok(await page.locator('[data-pencil-toolbar]').count());
 const saved=await page.evaluate(async id=>{
  const r=await fetch(pencilEditor.restUrl+id,{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':pencilEditor.nonce},body:JSON.stringify({value:'Edited safely'})});return r.status;
 },'byyourself-'+ids[0]+'.title');assert.equal(saved,200);
 await page.reload();assert.equal(await page.locator('.aiwp-content h1').innerText(),'Edited safely');
 const beforePreview=await php(`echo json_encode(array(get_option('pencil_field_registry'),get_option('pencil_values')));`);
 await page.goto(server.serverUrl+'/wp-admin/post.php?post='+ids[0]+'&action=edit');
 await page.locator('[data-aiwp-refresh]').click();
 await page.waitForFunction(()=>{const f=document.querySelector('.aiwp-preview-frame');return f && f.srcdoc.includes('Edited safely');});
 assert.equal(await page.locator('.aiwp-preview-frame').getAttribute('sandbox'),'allow-scripts');
 assert.equal(await page.frameLocator('.aiwp-preview-frame').locator('h1').innerText(),'Edited safely');
 const denied=await page.evaluate(async id=>{
  const r=await fetch(aiwpAdmin.previewUrl,{method:'POST',body:new URLSearchParams({action:'aiwp_preview_html',nonce:'invalid',post_id:id,html:'test'})});return r.status;
 },String(ids[0]));assert.equal(denied,403);
 assert.deepEqual(await php(`echo json_encode(array(get_option('pencil_field_registry'),get_option('pencil_values')));`),beforePreview);
 await page.goto(server.serverUrl+'/?page_id='+ids[1]);assert.equal(await page.locator('.aiwp-content h1').innerText(),'Original');
 assert.equal(await page.locator('[data-pencil-field="byyourself-'+ids[2]+'.title"]').count(),1);
 const security=await php(`
 wp_set_current_user(0);
 $req=new WP_REST_Request('POST','/pencil/v1/fields/byyourself-${ids[0]}.title');$req->set_param('value','Intruder');
 $status=rest_do_request($req)->get_status();
 $visitor=aiwp_render_document_html('[aiwp_text id="title"]Original[/aiwp_text]',${ids[0]});
 wp_set_current_user(1);
 add_shortcode('dangerous_test',function(){return 'EXECUTED';});
 $blocked=aiwp_render_document_html('[dangerous_test]',${ids[0]});
 $button=aiwp_render_document_html('[aiwp_button id="bad" url="javascript:alert(1)"]Test[/aiwp_button]',${ids[0]});
 $escaped=aiwp_render_document_html('[[aiwp_text id="escape"]Literal[/aiwp_text]]',${ids[0]});
 echo json_encode(array($status,$visitor,$blocked,$button,$escaped,aiwp_comments_disabled(),aiwp_can_edit_code()));`);
 assert.ok([401,403].includes(security[0]));assert.equal(security[1],'Edited safely');
 assert.equal(security[2],'[dangerous_test]');assert.ok(!security[3].includes('javascript:'));assert.ok(security[4].startsWith('[aiwp_text'));
 assert.ok(security[5]);
 const extra=await php(`
 $upload=wp_upload_bits('pencil-test.png',null,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
 $image=wp_insert_attachment(array('post_mime_type'=>'image/png','post_title'=>'Test','post_status'=>'inherit'),$upload['file']);
 $markup=aiwp_render_document_html('[aiwp_image id="photo" attachment="'.$image.'" /]',${ids[0]});
 $rich=Pencil_Fields::save('byyourself-${ids[0]}.intro','<p><strong>Edited</strong><script>alert(1)</script></p>');
 $richHtml=aiwp_render_document_html('[aiwp_richtext id="intro"]<p>Hello</p>[/aiwp_richtext]',${ids[0]});
 $editor=wp_insert_user(array('user_login'=>'bridge_editor','user_pass'=>wp_generate_password(),'role'=>'editor'));
 wp_set_current_user($editor);
 $permissions=array(Pencil_Plugin::can_edit(),aiwp_can_edit_code());
 echo json_encode(array($markup,$richHtml,$permissions));`);
 assert.match(extra[0],/data-pencil-type="image"/);assert.match(extra[0],/<img/);
 assert.match(extra[1],/<strong>Edited<\/strong>/);assert.ok(!extra[1].includes('<script'));
 assert.deepEqual(extra[2],[true,false]);
 // Deactivation occurs in a separate request: no dependency on Pencilino remains.
 await php(`require_once ABSPATH.'wp-admin/includes/plugin.php';deactivate_plugins('pencilino/pencilino-by-rino.php');echo 'true';`);
 await page.reload();assert.equal(await page.locator('.aiwp-content h1').innerText(),'Original');
 assert.equal(await page.locator('[data-pencil-field]').count(),0);
 console.log('PASS: coactivation, fields and images, richtext sanitation, REST save/reload, document isolation, shared header, anonymous rejection, editor/code permissions, escaping, comments, deactivation fallback.');
} finally {await browser?.close();await server[Symbol.asyncDispose]();}
