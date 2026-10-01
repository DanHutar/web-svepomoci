import fs from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

// Render a real HTML/CSS preview. It stays English regardless of test/site locale.
const root = new URL('../', import.meta.url);
const read = file => fs.readFile(new URL(file, root), 'utf8');
const html = await read('tools/theme-preview.html');
const css = (await Promise.all(['theme/ai-web/style.css', 'examples/header.css', 'examples/page.css'].map(read))).join('\n');
const browser = await chromium.launch({ headless: true, channel: 'chrome' });
try {
  const page = await browser.newPage({ viewport: { width: 1200, height: 900 }, deviceScaleFactor: 1 });
  await page.setContent(html);
  await page.addStyleTag({ content: css + '\n.sample-page .sample-page__services { padding-block: 36px; }' });
  await page.evaluate(() => document.fonts.ready);
  await page.screenshot({ path: fileURLToPath(new URL('theme/ai-web/screenshot.png', root)) });
  console.log('Created the English ByYourself theme preview (1200 x 900).');
} finally { await browser.close(); }
