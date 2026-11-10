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

1. Tag en kopi af den aktive temamappe og noter den aktuelle commit. Kontrollér,
   at ændringerne er testet og merget i temarepositoryets `main`.
2. Fra WordPress-roden i Forge → **teststore.idguard.dk → Commands** kan en
   eksisterende Git-installation opdateres sådan:

   ```bash
   set -euo pipefail
   theme_directory="$(wp --skip-plugins --skip-themes eval 'echo get_stylesheet_directory();')"
   git -C "$theme_directory" status --short
   ```

   Stop og gennemgå eventuelle lokale ændringer, før du fortsætter. Temaet skal
   være `idguard-shop` og pege på dette repository. Opdatér derefter:

   ```bash
   set -euo pipefail
   theme_directory="$(wp --skip-plugins --skip-themes eval 'echo get_stylesheet_directory();')"
   test "$(basename "$theme_directory")" = idguard-shop
   test -z "$(git -C "$theme_directory" status --porcelain)"
   test "$(git -C "$theme_directory" branch --show-current)" = main
   git -C "$theme_directory" pull --ff-only origin main
   bash "$theme_directory/scripts/install-languages.sh"
   wp cache flush
   ```

   Installer-scriptet henter de officielle sprogpakker og kontrollerer, at alle
   seks core-, WooCommerce- og blok-checkout-kataloger findes. Det ændrer ikke
   `WPLANG`. Hvis sprogpakker mangler, skal fejlen løses, før websitet linker til
   de nye demo-sprog. Har installationen ikke en Git-mappe, opdatér temaet via
   WordPress' normale tema-upload med mappenavnet `idguard-shop`, og kør derefter
   sproginstaller-scriptet fra WordPress-roden.
3. Kontrollér `/?lang=da`, `en`, `sv`, `no`, `nl`, `fi`: forside, produkt,
   tilføj-til-kurv, kurv, checkout, skift sprog og tilbage til dansk. Kontrollér
   både vare med og uden alderskrav; verificeringsreglerne skal være uændrede.
   Kontroller MitID-knappens destination uden at logge ind eller oprette ordrer.
4. Ryd eventuel side-/proxy-cache. Demoens svar sender no-cache/private og
   `Vary: Cookie`; en ekstern tvungen sidecache skal fortsat udelukke disse
   sessionsafhængige sider. Kontrollér `Content-Language` og cookie-isolation i
   to separate browsersessioner.
5. Kontrollér, at `/wp-login.php` og administrationen stadig bruger deres
   normale sprog. Test sproglinks med callback-parametre: links må aldrig kopiere
   tokens, ordrenøgler eller nonces til andre sider.

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
