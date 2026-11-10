import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
import { execFileSync } from 'node:child_process';
const dictionaries = JSON.parse(execFileSync('php', ['-r', "echo json_encode(require 'inc/translations.php');"], { encoding: 'utf8' }));
const source = fs.readFileSync('assets/language.js', 'utf8');
const originals = [
  'Du skal verificere din alder for at fortsætte til kassen.',
  'Konfigurationsfejl: Ugyldig aldersgrænse. Kontakt venligst support.',
  'Du afbrød verifikationsprocessen. Du skal verificere din alder for at fortsætte.',
  'Ingen ID token indstillet eller ukendt fejl. Kontakt support.',
];
const keys = ['alert_verify_required', 'alert_invalid_age', 'alert_cancelled', 'alert_token_error'];
let count = 0;
for (const [language, copy] of Object.entries(dictionaries)) {
  const calls = [];
  const nativeAlert = function (...args) { calls.push(args); return 'native-return'; };
  let observed = false;
  let update;
  function Observer(callback) { update = callback; this.observe = () => { observed = true; }; }
  const popup = { label: 'Aldersbekræftelse', getAttribute() { return this.label; }, setAttribute(name, value) { this.label = value; } };
  const close = { label: 'Luk', getAttribute() { return this.label; }, setAttribute(name, value) { this.label = value; } };
  const spinner = { nodeType: 1, textContent: '' };
  const loadingText = { nodeType: 3, textContent: ' Vent venligst...' };
  const dom = { 'idguard-popup': popup, 'idguard-close': close, 'idguard-popup-loading': { childNodes: [spinner, loadingText] } };
  const window = { alert: nativeAlert, MutationObserver: Observer, idguardShopPresentation: {
    language, alerts: Object.fromEntries(originals.map((original, index) => [original, copy[keys[index]]])),
    popupAria: copy.popup_aria, popupLoading: copy.popup_loading, popupClose: copy.popup_close,
  } };
  vm.runInNewContext(source, { window, MutationObserver: Observer, document: {
    body: {}, getElementById: (id) => dom[id], querySelectorAll: () => [], addEventListener() {},
  } });
  if (language === 'da') {
    assert.equal(window.alert, nativeAlert);
    assert.equal(observed, false);
    count += 2;
    continue;
  }
  originals.forEach((original, index) => {
    assert.equal(window.alert(original), 'native-return');
    assert.deepEqual(calls.at(-1), [copy[keys[index]]]);
    count += 2;
  });
  const unknown = { toString: () => 'An unrelated object' };
  assert.equal(window.alert(unknown, 'extra-argument'), 'native-return');
  assert.deepEqual(calls.at(-1), [unknown, 'extra-argument']);
  window.alert('Unrelated alert');
  assert.deepEqual(calls.at(-1), ['Unrelated alert']);
  window.alert();
  assert.deepEqual(calls.at(-1), []);
  assert.equal(popup.label, copy.popup_aria);
  assert.equal(close.label, copy.popup_close);
  assert.equal(loadingText.textContent, ' ' + copy.popup_loading);
  assert.equal(dom['idguard-popup-loading'].childNodes[0], spinner);
  assert.equal(observed, true);
  // Future/custom popup copy is preserved; only the known legacy strings map.
  popup.label = 'A custom label';
  close.label = 'A custom close';
  loadingText.textContent = 'A custom message';
  update();
  assert.equal(popup.label, 'A custom label');
  assert.equal(close.label, 'A custom close');
  assert.equal(loadingText.textContent, 'A custom message');
  count += 12;
}
console.log(`${count} scoped presentation assertions passed.`);
