# Drupal Test Website

Drupal 11-website, lokaal ontwikkeld met DDEV. Opdracht / leerproject als junior frontend & Drupal developer.

Live lokaal: `https://drupal-website.ddev.site`

## Stack

- Drupal 11.4
- PHP 8.4
- DDEV
- Custom theme: `drupal_test` (`web/themes/custom/drupal_test/`)
- Drush

## Wat al werkt

- Content types: News, Articles, Offices
- Taxonomy: Labels, Countries
- Views: `/articles` (filter op tags), `/offices` (filter op country)
- Custom theme actief (niet Olivero)
- Basis paginatemplate: `templates/page.html.twig`

## Lokaal starten

Vereisten: Docker Desktop + DDEV + Git.

```bash
git clone git@github.com:samleveugle/drupal-website.git
cd drupal-website
ddev start
ddev composer install
```

De database zit niet in Git. Na clone heb je een bestaande DDEV-database nodig, of een verse Drupal-installatie via `ddev launch`.

Theme-CSS zit in `web/themes/custom/drupal_test/css/style.css` (tijdelijke roze rand = theme-check).

## Projectstructuur

```text
web/themes/custom/drupal_test/   custom theme
web/                             Drupal docroot
docs/wireframes/                 opdracht-wireframes
```

## Nog te doen

- Sass-setup
- Header / vaste navigatie
- Listings en detailpagina’s volgens wireframes
- Paragraphs of Layout Builder
- Webform, SEO, Focal Point
- Custom Hello World-module
- Config export (`drush cex`)
