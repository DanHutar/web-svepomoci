import { chromium } from 'playwright';
import fs from 'node:fs/promises';

const target = 'https://web-svepomoci.webmaster.danielhutar.cz/?page_id=8';
const browser = await chromium.launch({ headless: true, channel: 'chrome' });
try {
  const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
  const failures = [];
  page.on('requestfailed', request => failures.push({ url: request.url(), error: request.failure()?.errorText }));
  page.on('response', response => { if (response.status() >= 400) failures.push({ url: response.url(), status: response.status() }); });
  await page.goto(target, { waitUntil: 'networkidle', timeout: 45000 });
  const desktop = await page.evaluate(() => {
    const sample = document.querySelector('.moje-stranka');
    const cards = document.querySelector('.moje-stranka__karty');
    const heading = document.querySelector('.moje-stranka h1');
    return {
      url: location.href, title: document.title,
      sampleVisible: Boolean(sample),
      background: sample && getComputedStyle(sample).backgroundColor,
      headingSize: heading && getComputedStyle(heading).fontSize,
      grid: cards && getComputedStyle(cards).gridTemplateColumns,
      overflow: document.documentElement.scrollWidth > innerWidth,
      styles: Array.from(document.styleSheets, s => ({ href: s.href, disabled: s.disabled })),
    };
  });
  await page.screenshot({ path: 'test-results/live-desktop.png', fullPage: true });
  await page.setViewportSize({ width: 390, height: 844 });
  const mobile = await page.evaluate(() => ({ grid: getComputedStyle(document.querySelector('.moje-stranka__karty')).gridTemplateColumns, overflow: document.documentElement.scrollWidth > innerWidth }));
  await page.screenshot({ path: 'test-results/live-mobile.png', fullPage: true });
  await page.goto(new URL('/', target).href, { waitUntil: 'networkidle', timeout: 45000 });
  const homepage = await page.evaluate(() => ({ url: location.href, title: document.title, hasSample: Boolean(document.querySelector('.moje-stranka')), text: document.querySelector('main')?.innerText.slice(0, 700) }));
  const result = { desktop, mobile, homepage, failures };
  console.log(JSON.stringify(result, null, 2));
  await fs.writeFile('test-results/live-inspection.json', JSON.stringify(result, null, 2));
} finally { await browser.close(); }
