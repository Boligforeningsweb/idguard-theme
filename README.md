# IDguard Test Store Theme

Et minimalistisk WooCommerce-tema bygget til [teststore.idguard.dk](https://teststore.idguard.dk).

## Hvad er det?

Denne shop eksisterer udelukkende for at demonstrere og teste **[IDguard](https://idguard.dk)** — et WooCommerce-plugin til aldersverifikation med MitID.

Siden 1. oktober 2024 kræver dansk lovgivning at webshops verificerer kundens alder ved salg af aldersbegrænsede produkter som alkohol, tobak og CBD.

Temaet er med vilje enkelt og minimalistisk. Det skal se flot ud, men ikke stjæle fokus fra IDguard-pluginnet.

## Installation

```bash
cd wp-content/themes
git clone https://github.com/Boligforeningsweb/idguard-theme.git idguard-shop
wp theme activate idguard-shop
```

## Opsætning af testdata

Temaet inkluderer 6 testprodukter med billeder, priser og beskrivelser — klar til brug.

1. Gå til **Udseende → IDguard Testdata** i WordPress admin
2. Klik **Opret testprodukter**
3. Shoppen er nu klar med testdata

Du kan til enhver tid nulstille eller slette testprodukterne fra samme side.

### Inkluderede produkter

| Produkt | Pris | Aldersverifikation |
|---|---|---|
| Rødvin - Château Test 2020 | 149 kr | Ja (alkohol) |
| Whisky - Single Malt Test | 399 kr | Ja (alkohol) |
| Økologisk Æblejuice | 49 kr | Nej |
| CBD Olie 10% | 299 kr | Ja (CBD) |
| Gavekort 500 kr | 500 kr | Nej |
| E-cigaret Startkit | 199 kr | Ja (nikotin) |

## Opdatering

```bash
cd wp-content/themes/idguard-shop
git pull origin main
```

## Om IDguard

[IDguard](https://idguard.dk) er et WooCommerce/Shopify-plugin der håndterer alderskontrol via MitID. Pluginnet sikrer at kunder der køber aldersbegrænsede produkter (alkohol, tobak, CBD m.m.) verificerer deres alder inden køb.

Det understøtter:
- Aldersbegrænsning pr. produkt eller kategori
- Tilpasselig verifikations-popup (farver, tekst, knapper)
- Dansk og engelsk sprog
- Overholdelse af dansk lovgivning om aldersverifikation

Læs mere på [idguard.dk](https://idguard.dk).

## Krav

- WordPress 6.0+
- PHP 8.0+
- WooCommerce 8.0+

## Design

- **Inter** typografi via Google Fonts
- Mørk header/footer, lyst indholdsområde
- Produktkort med subtile hover-effekter
- 3 → 2 → 1 kolonne responsivt grid
- Ingen sidebars, ingen widgets, ingen rod — kun produkter

## Licens

GPL v2 eller nyere.


## Sprogversioner (1.3.0)

Testbutikken understøtter dansk, engelsk, svensk, norsk bokmål, nederlandsk og
finsk. Eksempel: `https://teststore.idguard.dk/?lang=sv`. Sprogvalget bevares i
session-cookien `idguard_teststore_language` gennem produktvisning, kurv, checkout,
WooCommerce Store API og retur fra MitID. Cookien er HttpOnly, SameSite=Lax og
Secure på HTTPS; den udløber, når browsersessionen slutter. Ugyldige sprog
ignoreres. `?lang=da` vælger aktivt dansk, også efter et andet sprog.

Temaets tekster, de seks testprodukters navne/beskrivelser og butikkens
MitID-popup vises på det valgte sprog. WordPress/WooCommerce bruger deres
**officielle oversættelser**, inklusive JavaScript-kataloger til blok-kassen.
Det rigtige MitID-loginvindue er fortsat MitIDs danske vindue. Der tilføjes
hverken BankID, FTN eller andre godkendelsesmetoder. Demopriserne er stadig DKK.

Alle oversættelser sker ved visning; testdata, sikkerhedsindstillinger og
WordPress' globale sprog ændres ikke. Administration og WordPress-login beholder
deres tidligere sprog. Genimportér eller nulstil ikke testprodukterne for at
aktivere sprogversionerne.

### Deployment

Kør hele den følgende blok fra WordPress-roden i Forge → **teststore.idguard.dk → Commands**. Den kontrollerer først WP-CLI, WordPress, den aktive temamappe, dens Git-rod, `main`, en ren arbejdsmappe og det præcise godkendte origin. Derefter henter den `main`, fastlåser den hentede commit, kontrollerer fast-forward og sprogkontrollen fra netop den commit, installerer officielle sprogpakker uden at aktivere dem og kontrollerer alle kataloger, før temaet opdateres. Ved fejl i forberedelsen forbliver den aktive temaversion urørt. WordPress' `WPLANG`, testprodukter og IDguard-/betalingsindstillinger bevares.

```bash
set -euo pipefail

fail() { printf '%s\n' "$1" >&2; exit 1; }
command -v wp >/dev/null || fail 'WP-CLI mangler. Stop her og send fejlteksten.'
command -v git >/dev/null || fail 'Git mangler. Stop her og send fejlteksten.'
wp --skip-plugins --skip-themes core is-installed || fail 'WordPress er ikke installeret. Ingen ændringer er foretaget.'

theme_directory="$(wp --skip-plugins --skip-themes eval 'echo get_stylesheet_directory();')" || fail 'Det aktive tema kunne ikke findes. Ingen ændringer er foretaget.'
test "$(basename "$theme_directory")" = idguard-shop || fail 'Det aktive tema hedder ikke idguard-shop. Ingen ændringer er foretaget.'
theme_real="$(cd -P "$theme_directory" 2>/dev/null && pwd)" || fail 'Temamappen kunne ikke findes. Ingen ændringer er foretaget.'
repo_top="$(git -C "$theme_directory" rev-parse --show-toplevel 2>/dev/null)" || fail 'Det aktive tema er ikke en Git-installation. Ingen ændringer er foretaget.'
repo_real="$(cd -P "$repo_top" 2>/dev/null && pwd)" || fail 'Git-roden kunne ikke findes. Ingen ændringer er foretaget.'
test "$repo_real" = "$theme_real" || fail 'Git-roden er ikke den aktive temamappe. Ingen ændringer er foretaget.'
test -z "$(git -C "$theme_directory" status --porcelain 2>/dev/null)" || fail 'Temamappen har lokale ændringer. Ingen ændringer er foretaget.'
test "$(git -C "$theme_directory" branch --show-current 2>/dev/null)" = main || fail 'Temaets aktive gren er ikke main. Ingen ændringer er foretaget.'

origin="$(git -C "$theme_directory" remote get-url origin 2>/dev/null)" || fail 'Temaets origin kunne ikke kontrolleres. Ingen ændringer er foretaget.'
case "$origin" in
  https://github.com/Boligforeningsweb/idguard-theme|https://github.com/Boligforeningsweb/idguard-theme.git|git@github.com:Boligforeningsweb/idguard-theme|git@github.com:Boligforeningsweb/idguard-theme.git|ssh://git@github.com/Boligforeningsweb/idguard-theme|ssh://git@github.com/Boligforeningsweb/idguard-theme.git) ;;
  *) fail 'Temaets origin er ikke det godkendte IDguard-temarepository. Ingen ændringer er foretaget.' ;;
esac

git -C "$theme_directory" fetch origin main || fail 'Hentning fra det godkendte temarepository mislykkedes. Ingen ændringer er foretaget.'
target="$(git -C "$theme_directory" rev-parse --verify FETCH_HEAD 2>/dev/null)" || fail 'Den hentede temaversion kunne ikke fastlåses. Ingen ændringer er foretaget.'
git -C "$theme_directory" merge-base --is-ancestor HEAD "$target" || fail 'Den hentede temaversion kan ikke opdateres som fast-forward. Ingen ændringer er foretaget.'
git -C "$theme_directory" cat-file -e "$target:scripts/check-language-readiness.php" 2>/dev/null || fail 'Den hentede temaversion mangler sprogkontrollen. Ingen ændringer er foretaget.'

mktemp_bin="$(command -v mktemp 2>/dev/null || true)"
if test -z "$mktemp_bin"; then
  for candidate in /usr/bin/mktemp /bin/mktemp; do
    if test -x "$candidate"; then mktemp_bin="$candidate"; break; fi
  done
fi
test -n "$mktemp_bin" || fail 'Værktøjet til midlertidige filer mangler. Ingen ændringer er foretaget.'
checker="$("$mktemp_bin" "${TMPDIR:-/tmp}/idguard-language-readiness.XXXXXX")" || fail 'Der kunne ikke oprettes en midlertidig sprogkontrol. Ingen ændringer er foretaget.'
rm_bin="$(command -v rm 2>/dev/null || true)"
if test -z "$rm_bin"; then
  for candidate in /bin/rm /usr/bin/rm; do
    if test -x "$candidate"; then rm_bin="$candidate"; break; fi
  done
fi
test -n "$rm_bin" || fail 'Værktøjet til oprydning af midlertidige filer mangler. Ingen ændringer er foretaget.'
trap '"$rm_bin" -f "$checker"' EXIT
git -C "$theme_directory" show "$target:scripts/check-language-readiness.php" > "$checker" || fail 'Sprogkontrollen kunne ikke læses fra den hentede temaversion. Ingen ændringer er foretaget.'

before_language="$(wp --skip-plugins --skip-themes option get WPLANG)" || fail 'WordPress-sproget kunne ikke kontrolleres. Ingen ændringer er foretaget.'
wp --skip-plugins --skip-themes language core install da_DK en_GB sv_SE nb_NO nl_NL fi || fail 'Installation af officielle WordPress-sprogpakker mislykkedes. Temaet er ikke opdateret.'
wp --skip-plugins --skip-themes language plugin install woocommerce da_DK en_GB sv_SE nb_NO nl_NL fi || fail 'Installation af officielle WooCommerce-sprogpakker mislykkedes. Temaet er ikke opdateret.'
after_language="$(wp --skip-plugins --skip-themes option get WPLANG)" || fail 'WordPress-sproget kunne ikke genkontrolleres. Temaet er ikke opdateret.'
test "$after_language" = "$before_language" || fail 'WordPress-sproget ændrede sig uventet. Temaet er ikke opdateret.'
wp --skip-plugins --skip-themes eval-file "$checker" || fail 'Sprogkontrollen fejlede. Temaet er ikke opdateret.'

git -C "$theme_directory" merge --ff-only "$target" || fail 'Den kontrollerede temaversion kunne ikke flettes. Gennemgå installationen.'
wp --skip-plugins --skip-themes cache flush
```

Kontrollér derefter `/?lang=da`, `en`, `sv`, `no`, `nl`, `fi`: forside, produkt,
tilføj-til-kurv, kurv, checkout, skift sprog og tilbage til dansk. Kontrollér
både vare med og uden alderskrav; verificeringsreglerne skal være uændrede.
Kontrollér MitID-knappens destination uden at logge ind eller oprette ordrer.
Ryd eventuel side-/proxy-cache. Demoens svar sender no-cache/private og
`Vary: Cookie`; en ekstern tvungen sidecache skal fortsat udelukke disse
sessionsafhængige sider. Kontrollér `Content-Language` og cookie-isolation i to
separate browsersessioner. Kontrollér, at `/wp-login.php` og administrationen
stadig bruger deres normale sprog. Test sproglinks med callback-parametre: links
må aldrig kopiere tokens, ordrenøgler eller nonces til andre sider.

En lokal test eller en PR er ikke et produktionsdeployment. Websitets oversatte
links/screenshots udgives efter testbutikkens tema og sprogpakker er installeret.

### Rollback

Gendan den gemte temaversion (eller revert releasecommit på `main` og opdatér
igen). Der er ingen databaseændringer at tilbageføre. De officielle sprogpakker
kan blive liggende; standarden er fortsat dansk. Session-cookien bliver ignoreret
af det tidligere tema og udløber med browsersessionen.

### Kontroller

```bash
php tests/language.php
node tests/presentation.mjs
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l
bash -n scripts/install-languages.sh
```

PHP-prøverne kontrollerer gyldige/ugyldige sprog, eksplicit dansk, sessioner,
URL-grænser, private query-parametre, admin/login/API-isolation, popupindstillinger
og afgrænsning til de seks testprodukter. JavaScript-prøverne kontrollerer præcis
oversættelse af legacy-popuptekster, native alert-argumenter/returværdi,
uændrede ukendte beskeder og bevaring af loading-spinneren.
