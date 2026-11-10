# Isolated multilingual teststore fixture

This fixture uses native WordPress 7.1.4, production-matching WooCommerce 10.6.1,
the released IDguard plugin 3.3.6 and this theme mounted directly. It has a fresh
SQLite database, six synthetic demo products and block cart/checkout pages.
It never reads production credentials or changes a production database.

Requirements: Node 24, pinned `@wp-playground/cli` 3.1.61, Python 3 and the released
3.3.6 plugin package. The default paths reuse the marketing workspace's tooling;
set `PLAYGROUND_CLI` and `IDGUARD_PLUGIN_ZIP` for another local installation.

```sh
bash scripts/local-fixture/setup.sh
# Once ready, in another terminal:
python3 scripts/local-fixture/install-catalogs.py
```

The server is `http://127.0.0.1:18441` by default (`PORT` overrides it). Stop it
normally after testing. To reopen the retained fixture, use `serve.sh`.
To rebuild, stop the server and explicitly remove only
`/tmp/idguard-teststore-localization`, then run `setup.sh` again.

All five non-Danish languages plus Danish use official core/WooCommerce catalogs
for the installed versions. Translation dictionaries in the theme render actual
native output; captures never repaint or translate the DOM.

The fixture disables email, updates and external PHP HTTP. Its only IDguard
availability stub marks the authentication endpoint available so the real
plugin can show its popup. It does not fabricate a verification result, callback,
receipt, order, or payment. WooCommerce itself may create local `wc-checkout-draft` rows when
a block checkout is viewed; the tests never submit or pay them. Browser tests
must block external navigation and
must never complete an authentication or submit an order.

The generated `/tmp/idguard-teststore-localization/build/fixture.json` lists the
actual local page/product IDs and URLs. They differ from production and must not
be copied into production code.
