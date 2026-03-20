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
