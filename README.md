# HSC Sponsoren

WordPress plugin for the club website: sponsors and partners maintained once, as a single source of truth.

## Data model

| What | Stored as |
|---|---|
| Sponsor | Post type `hsc_sponsor` (menu **Sponsoren**) |
| Name | Post title |
| Description | Post editor content |
| Image | Featured image |
| Website | Post meta `_hsc_sponsor_website` (http/https URL) |
| Partner since | Post meta `_hsc_sponsor_since` (`Y-m-d`) |
| Type (Hauptsponsor, Premium, Sponsoren, Gönner) | Taxonomy `hsc_sponsor_category` |

The category terms are created by hand under **Sponsoren > Kategorien**. The sponsor list can be filtered by category and searched by name.

## Development

```bash
composer install
composer lint   # php -l + PHPCS (WordPress standards)
composer test   # PHPUnit
bin/build-zip.sh  # -> dist/hsc-sponsoren.zip
```

## Release flow

- `main` and every pull request: lint + tests (`.github/workflows/ci.yml`).
- Push to `production` (e.g. merge `main` into it): `.github/workflows/release.yml`
  derives the next version from [Conventional Commits](https://www.conventionalcommits.org)
  since the last tag (`feat` → minor, `fix`/other → patch, `!`/`BREAKING CHANGE` → major),
  updates plugin header, `readme.txt` and `CHANGELOG.md`, commits to `production`,
  tags `vX.Y.Z` and publishes a GitHub release with `hsc-sponsoren.zip`.
