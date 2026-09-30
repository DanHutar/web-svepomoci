import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const root = fileURLToPath(new URL('../', import.meta.url));
const read = file => fs.readFileSync(path.join(root,file),'utf8');
const catalog = JSON.parse(read('translations/catalog.json'));
for (const [domain, values] of Object.entries(JSON.parse(read('translations/additions.json')))) Object.assign(catalog[domain] ||= {},values);
function header(language) { return 'Content-Type: text/plain; charset=UTF-8\nLanguage: '+language+'\nPlural-Forms: '+(language==='cs_CZ'?'nplurals=3; plural=(n == 1) ? 0 : (n >= 2 && n <= 4) ? 1 : 2;':'nplurals=2; plural=(n != 1);')+'\n'; }
function mo(messages) {
 const entries=Object.entries(messages).sort((a,b)=>Buffer.compare(Buffer.from(a[0]),Buffer.from(b[0])));
 const tableSize=entries.length*8, dataStart=28+tableSize*2;
 const sources=entries.map(e=>Buffer.from(e[0]+'\0')), targets=entries.map(e=>Buffer.from(e[1]+'\0'));
 const data=Buffer.alloc(dataStart+sources.concat(targets).reduce((n,b)=>n+b.length,0));
 [0x950412de,0,entries.length,28,28+tableSize,0,0].forEach((n,i)=>data.writeUInt32LE(n,i*4));
 let offset=dataStart;
 for(const [group,buffers] of [sources,targets].entries())buffers.forEach((buffer,i)=>{
  data.writeUInt32LE(buffer.length-1,28+group*tableSize+i*8); data.writeUInt32LE(offset,32+group*tableSize+i*8); buffer.copy(data,offset);offset+=buffer.length;
 });
 return data;
}
const directories={ 'ai-web-studio':['plugin/ai-web-studio/languages'], 'ai-web':['theme/ai-web/languages'], 'web-svepomoci':['plugin/ai-web-studio/languages','theme/ai-web/languages'] };
for(const [domain,dirs] of Object.entries(directories))for(const dir of dirs) {
 fs.mkdirSync(path.join(root,dir),{recursive:true});
 for(const locale of ['en_US','cs_CZ']) {
  const messages={'':header(locale),...(locale==='cs_CZ'?catalog[domain]:{})};
  fs.writeFileSync(path.join(root,dir,domain+'-'+locale+'.mo'),mo(messages));
 }
 const po=Object.entries({'':header('cs_CZ'),...catalog[domain]}).map(([source,target])=>'msgid '+JSON.stringify(source)+'\nmsgstr '+JSON.stringify(target)+'\n').join('\n');
 fs.writeFileSync(path.join(root,dir,domain+'-cs_CZ.po'),po);
}
const scripts={'aiwp-admin':'admin.js','aiwp-prompts':'prompts.js','aiwp-settings-prompt':'settings.js','aiwp-consent':'consent.js'};
for(const [handle,file] of Object.entries(scripts)) {
 const messages=new Set();
 for(const match of read('plugin/ai-web-studio/assets/'+file).matchAll(/wp\.i18n\.__\(("(?:\\.|[^"\\])*"),\s*"ai-web-studio"\)/g))messages.add(JSON.parse(match[1]));
 for(const language of ['en','cs']) {
  const entries={'':{domain:'ai-web-studio',lang:language==='cs'?'cs_CZ':'en_US','plural-forms':language==='cs'?'nplurals=3; plural=(n==1)?0:(n>=2&&n<=4)?1:2;':'nplurals=2; plural=(n != 1);'}};
  for(const source of messages) {if(!catalog['ai-web-studio'][source])throw new Error('Missing Czech translation: '+source);entries[source]=[language==='cs'?catalog['ai-web-studio'][source]:source];}
  const jed={domain:'ai-web-studio',locale_data:{'ai-web-studio':entries}};
  fs.writeFileSync(path.join(root,'plugin/ai-web-studio/languages/'+handle+'-'+language+'.json'),JSON.stringify(jed)+'\n');
 }
}
fs.copyFileSync(path.join(root,'plugin/ai-web-studio/includes/languages.php'),path.join(root,'theme/ai-web/includes/languages.php'));
console.log('Built gettext MO/PO and JavaScript catalogs for English and Czech.');
