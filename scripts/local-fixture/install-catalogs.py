#!/usr/bin/env python3
"""Install official native catalogs into an isolated local fixture, never production."""
from pathlib import Path
import io
import json
import re
import sys
import urllib.request
import zipfile

site = Path(sys.argv[1] if len(sys.argv) > 1 else '/tmp/idguard-teststore-localization/site').resolve()
if not site.is_relative_to(Path('/tmp/idguard-teststore-localization').resolve()):
    raise SystemExit('This helper is restricted to /tmp/idguard-teststore-localization/.')
core = re.search(r"\$wp_version = '([^']+)'", (site / 'wp-includes/version.php').read_text()).group(1)
woo = re.search(r'Version:\s+([\d.]+)', (site / 'wp-content/plugins/woocommerce/woocommerce.php').read_text()).group(1)
languages = site / 'wp-content/languages'
for kind, version, slug, target in [
    ('core', core, '', languages),
    ('plugins', woo, 'woocommerce', languages / 'plugins'),
]:
    url = f'https://api.wordpress.org/translations/{kind}/1.0/?version={version}'
    if slug:
        url += '&slug=' + slug
    catalog = json.load(urllib.request.urlopen(url))
    target.mkdir(parents=True, exist_ok=True)
    for locale in ['da_DK', 'en_GB', 'sv_SE', 'nb_NO', 'nl_NL', 'fi']:
        package = next((item for item in catalog['translations'] if item['language'] == locale), None)
        if not package:
            raise RuntimeError(f'No native {kind} catalog for {locale}')
        archive = zipfile.ZipFile(io.BytesIO(urllib.request.urlopen(package['package']).read()))
        for name in archive.namelist():
            if not name.endswith('/'):
                (target / Path(name).name).write_bytes(archive.read(name))
        print(f'{kind}/{locale}: native {version} catalog installed')

# WordPress rejects an unavailable WPLANG during the initial bootstrap. The
# fixture's native catalogs now exist; match production's stored Danish locale.
# This is restricted above to the throwaway /tmp SQLite fixture only.
import sqlite3
sqlite_path = next((site / 'wp-content/database').rglob('.ht.sqlite'))
connection = sqlite3.connect(sqlite_path)
connection.execute("INSERT INTO wp_options (option_name, option_value, autoload) VALUES ('WPLANG', 'da_DK', 'yes') ON CONFLICT(option_name) DO UPDATE SET option_value='da_DK'")
connection.commit()
connection.close()
print('Fixture default locale: da_DK (production-matching; no frontend option writes).')
