import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
const dictionaryFile = fileURLToPath(new URL('../../inc/translations.php', import.meta.url));
const dictionary = JSON.parse(execFileSync('php', ['-r', '$copy = require $argv[1]; echo json_encode($copy);', dictionaryFile], { encoding: 'utf8' }));

const origin = process.env.TESTSTORE_ORIGIN || 'http://127.0.0.1:18441';
assert.ok(['localhost', '127.0.0.1'].includes(new URL(origin).hostname), 'These stateful tests are restricted to a local fixture');
const workspace = process.env.MARKETING_WORKSPACE || process.cwd();
const out = path.join(workspace, '.context/teststore-localization');
const fixture = JSON.parse(fs.readFileSync('/tmp/idguard-teststore-localization/build/fixture.json', 'utf8'));
const wine = fixture.products.find((product) => product.restricted);
const juice = fixture.products.find((product) => !product.restricted);
const cases = [
  ['da', 'da-DK', 'Kurv'], ['en', 'en-GB', 'Cart'], ['sv', 'sv-SE', 'Varukorg'],
  ['no', 'nb-NO', 'Handlekurv'], ['nl', 'nl-NL', 'Winkelwagen'], ['fi', 'fi', 'Ostoskori'],
];
const checks = [], failures = [];
const run = async (name, work) => {
  try { await work(); checks.push(name); console.log('PASS', name); }
  catch (error) { failures.push({ name, error: error.stack }); console.log('FAIL', name, error.message); }
};
const browser = await chromium.launch();
const makeContext = async (viewport = { width: 1000, height: 900 }) => {
  const context = await browser.newContext({ viewport, locale: 'da-DK', reducedMotion: 'reduce' });
  await context.route('**/*', (route) => ['127.0.0.1', 'localhost', 'fonts.googleapis.com', 'fonts.gstatic.com'].includes(new URL(route.request().url()).hostname) ? route.continue() : route.abort());
  await context.addInitScript(() => { window.teststoreAlerts = []; window.alert = (...args) => window.teststoreAlerts.push(args); });
  return context;
};
const noOverflow = async (page, name) => assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${name}: document overflows`);
const localizedTitle = async (page, first, last) => {
  const rawTitle = await page.title();
  const normalize = (value) => value.replace(/\s[-–—]\s/g, ' – ');
  const title = normalize(rawTitle); first = normalize(first); last = normalize(last);
  assert.ok(title.startsWith(first + ' '), `Title has wrong language/prefix: ${title}`);
  assert.ok(title.endsWith(last), `Title has wrong language/suffix: ${title}`);
  assert.equal(await page.locator('meta[property="og:title"]').getAttribute('content'), rawTitle);
};


try {
  for (const [language, htmlLang, cartTitle] of cases) {
    const context = await makeContext();
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await run(`${language}: native home, products, links and cache isolation`, async () => {
      const response = await page.goto(`${origin}/?lang=${language}`);
      await page.evaluate(() => document.fonts.ready);
      assert.equal(response.status(), 200);
      assert.equal(await page.locator('html').getAttribute('lang'), htmlLang);
      assert.equal(await page.locator('.products li.product').count(), 6);
      const urls = await page.locator('.products li.product a.woocommerce-LoopProduct-link').evaluateAll((links) => links.map((link) => link.href));
      assert.equal(urls.length, 6);
      assert.ok(urls.every((url) => new URL(url).searchParams.get('lang') === language));
      assert.ok(response.headers()['cache-control'].includes('no-store'));
      assert.ok(response.headers()['vary'].toLowerCase().includes('cookie'));
      const cookie = (await context.cookies()).find((cookie) => cookie.name === 'idguard_teststore_language');
      assert.equal(cookie.value, language); assert.equal(cookie.httpOnly, true); assert.equal(cookie.sameSite, 'Lax');
      await noOverflow(page, language);
      await localizedTitle(page, '18+ ' + dictionary[language].shop_name, dictionary[language].site_tagline);
      const productUrl = new URL(wine.url); productUrl.searchParams.set('lang', language);
      await page.goto(productUrl.href);
      await localizedTitle(page, dictionary[language].product_wine_name, '18+ ' + dictionary[language].shop_name);
    });
    await run(`${language}: popup, block cart and Store API keep the language`, async () => {
      await page.goto(`${origin}/?lang=${language}&add-to-cart=${wine.id}`);
      await page.goto(`${origin}/cart/`);
      assert.equal(await page.locator('html').getAttribute('lang'), htmlLang);
      assert.equal(await page.locator('h1').innerText(), cartTitle);
      await localizedTitle(page, dictionary[language].page_cart, '18+ ' + dictionary[language].shop_name);
      await page.locator('#idGuardModal').waitFor({ state: 'visible' });
      const config = await page.evaluate(() => ({ title: idguardData.customization.popupTitle, confirm: idguardData.customization.confirmButton, cancel: idguardData.customization.cancelButton, requiredAge: idguardData.requiredAge, cart: idguardData.cartUrl, checkout: idguardData.checkoutUrl }));
      assert.equal(await page.locator('#idGuardModal h2').innerText(), config.title);
      assert.equal(await page.locator('#verifyButton .verify-text').innerText(), config.confirm);
      assert.equal(await page.locator('#cancelButton').innerText(), config.cancel);
      assert.equal(String(config.requiredAge), '18');
      assert.equal(new URL(config.cart).searchParams.get('lang'), language);
      assert.equal(new URL(config.checkout).searchParams.get('lang'), language);
      assert.ok((await page.locator('#idGuardModal p').innerText()).includes('MitID'));
      const cart = await context.request.get(`${origin}/wp-json/wc/store/v1/cart`);
      assert.equal(cart.status(), 200);
      assert.ok(cart.headers()['cache-control'].includes('no-store'));
      assert.ok(cart.headers()['vary'].toLowerCase().includes('cookie'));
      const data = await cart.json();
      assert.equal(data.items.length, 1); assert.equal(data.items[0].quantity, 1);
      assert.equal(data.totals.currency_code, 'DKK');
      if (language !== 'da') assert.notEqual(data.items[0].name, wine.name);
      await page.screenshot({ path: path.join(out, `${language}-cart-popup.png`) });
      await noOverflow(page, `${language} cart`);
    });
    await run(`${language}: cancel and direct checkout never clear age requirements`, async () => {
      await Promise.all([page.waitForURL((url) => url.pathname === '/'), page.locator('#cancelButton').click()]);
      await page.waitForLoadState('load');
      assert.equal(new URL(page.url()).pathname, '/');
      assert.equal(await page.locator('html').getAttribute('lang'), htmlLang);
      await page.goto(`${origin}/checkout/`);
      await localizedTitle(page, dictionary[language].page_checkout, '18+ ' + dictionary[language].shop_name);
      await page.locator('#idGuardModal').waitFor({ state: 'visible' });
      assert.equal(await page.locator('html').getAttribute('lang'), htmlLang);
      assert.equal(await page.evaluate(() => String(idguardData.requiredAge)), '18');
      assert.equal(await page.evaluate(() => sessionStorage.getItem('verification_success')), null);
      await noOverflow(page, `${language} checkout`);
    });
    await run(`${language}: known legacy alerts translate and unknown alerts pass through`, async () => {
      await page.evaluate(() => {
        window.alert('Du skal verificere din alder for at fortsætte til kassen.');
        window.alert('Unknown third-party diagnostic', 42);
      });
      const result = await page.evaluate(() => ({ calls: window.teststoreAlerts, expected: idguardShopPresentation.alerts['Du skal verificere din alder for at fortsætte til kassen.'] }));
      assert.equal(result.calls.at(-2)[0], result.expected);
      assert.deepEqual(result.calls.at(-1), ['Unknown third-party diagnostic', 42]);
      assert.equal(await page.evaluate(() => sessionStorage.getItem('verification_success')), null);
    });
    await run(`${language}: verification still targets Danish MitID, without contacting it`, async () => {
      await page.goto(`${origin}/cart/`);
      await page.locator('#idGuardModal').waitFor({ state: 'visible' });
      await context.route('https://authentication.idguard.dk/**', (route) => route.fulfill({ status: 200, contentType: 'text/plain', body: 'External authentication deliberately blocked by local test.' }));
      const [request] = await Promise.all([page.waitForRequest((request) => new URL(request.url()).hostname === 'authentication.idguard.dk'), page.locator('#verifyButton').click()]);
      const url = new URL(request.url());
      assert.equal(url.pathname, '/oauth2/authorize');
      assert.equal(url.searchParams.get('login_hint'), 'country:DK');
      assert.equal(url.searchParams.get('scope'), 'openid is_over_18');
      assert.equal(url.searchParams.get('redirect_uri'), 'https://app.idguard.dk/callback-wp.php');
    });
    await run(`${language}: no script exceptions`, async () => assert.deepEqual(errors, []));
    await context.close();
    const unrestricted = await makeContext();
    const unrestrictedPage = await unrestricted.newPage();
    await run(`${language}: production nested empty-cart headings are translated`, async () => {
      await unrestrictedPage.goto(`${origin}/cart/?lang=${language}`);
      assert.equal(await unrestrictedPage.locator('h1').innerText(), cartTitle);
      assert.equal(await unrestrictedPage.locator('.wc-block-cart__empty-cart__title').innerText(), dictionary[language].cart_empty);
      const headings = await unrestrictedPage.locator('main h2').allTextContents();
      assert.ok(headings.includes(dictionary[language].cart_new_products));
      if (language !== 'en') assert.ok(!headings.some((text) => text.includes('Your cart is currently empty!') || text === 'New in store'));
    });
    await run(`${language}: unrestricted product needs no age popup`, async () => {
      await unrestrictedPage.goto(`${origin}/?lang=${language}&add-to-cart=${juice.id}`);
      await unrestrictedPage.goto(`${origin}/cart/`);
      assert.equal(await unrestrictedPage.locator('html').getAttribute('lang'), htmlLang);
      assert.ok(!(await unrestrictedPage.evaluate(() => idguardData.requiredAge)));
      assert.equal(await unrestrictedPage.locator('#idGuardModal').count(), 0);
      const cart = await unrestricted.request.get(`${origin}/wp-json/wc/store/v1/cart`);
      assert.equal((await cart.json()).items[0].id, juice.id);
    });
    await run(`${language}: native block checkout translates the existing COD display text`, async () => {
      await unrestrictedPage.goto(`${origin}/checkout/`);
      assert.equal(await unrestrictedPage.locator('html').getAttribute('lang'), htmlLang);
      assert.equal(await unrestrictedPage.locator('#idGuardModal').count(), 0);
      await unrestrictedPage.waitForFunction((title) => document.querySelector('.wp-block-woocommerce-checkout-payment-block')?.innerText.includes(title), dictionary[language].payment_cod_title);
      const payment = await unrestrictedPage.locator('.wp-block-woocommerce-checkout-payment-block').innerText();
      assert.ok(payment.includes(dictionary[language].payment_cod_title));
      assert.ok(payment.includes(dictionary[language].payment_cod_description));
      if (language !== 'da') assert.ok(!payment.includes('Efterkrav') && !payment.includes('Betal med kontanter ved levering.'));
      await noOverflow(unrestrictedPage, `${language} unrestricted checkout`);
    });
    await unrestricted.close();
    for (const width of [320, 390]) await run(`${language}: ${width}px native mobile layout and menu`, async () => {
      const mobile = await makeContext({ width, height: 844 });
      try {
        const p = await mobile.newPage(); await p.goto(`${origin}/?lang=${language}`); await p.evaluate(() => document.fonts.ready);
        await noOverflow(p, `${language} ${width}`);
        assert.ok(await p.locator('.shop-language summary').isVisible());
        await p.locator('.shop-language summary').click();
        assert.equal(await p.locator('.shop-language a').count(), 6);
        assert.ok(await p.locator('.shop-language a[hreflang="fi"]').isVisible());
        await p.keyboard.press('Escape');
        assert.equal(await p.locator('.shop-language').evaluate((element) => element.open), false);
      } finally { await mobile.close(); }
    });
  }
  await run('language changes preserve a cart and never copy callback secrets', async () => {
    const context = await makeContext();
    try {
      const page = await context.newPage(); await page.goto(`${origin}/?lang=sv&add-to-cart=${juice.id}`);
      await page.goto(`${origin}/cart/?vtoken=private&subscription_id=private&return_url=https://example.invalid&lang=sv`);
      const hrefs = await page.locator('.shop-language a').evaluateAll((links) => links.map((link) => link.href));
      assert.ok(hrefs.every((url) => [...new URL(url).searchParams.keys()].join() === 'lang'));
      await page.locator('.shop-language summary').click(); await page.locator('.shop-language a[hreflang="fi"]').click();
      assert.equal(await page.locator('html').getAttribute('lang'), 'fi');
      assert.equal(new URL(page.url()).searchParams.get('lang'), 'fi');
      const cart = await context.request.get(`${origin}/wp-json/wc/store/v1/cart`); assert.equal((await cart.json()).items[0].id, juice.id);
      await page.goto(`${origin}/?lang=da`); assert.equal(await page.locator('html').getAttribute('lang'), 'da-DK');
      await page.goto(`${origin}/?lang=unsupported`); assert.equal(await page.locator('html').getAttribute('lang'), 'da-DK');
      await page.goto(`${origin}/?lang[]=fi`); assert.equal(await page.locator('html').getAttribute('lang'), 'da-DK');
    } finally { await context.close(); }
  });
  await run('storefront cookie leaves WordPress login language unchanged', async () => {
    const context = await makeContext();
    try {
      const page = await context.newPage(); await page.goto(`${origin}/?lang=fi`); await page.goto(`${origin}/wp-login.php`);
      assert.equal(await page.locator('html').getAttribute('lang'), 'da-DK');
    } finally { await context.close(); }
  });
} finally {
  await browser.close();
  fs.writeFileSync(path.join(out, 'browser-results.json'), JSON.stringify({ origin, passed: checks.length, checks, failures }, null, 2) + '\n');
}
console.log(`${checks.length} checks passed; ${failures.length} failed.`);
if (failures.length) process.exitCode = 1;
