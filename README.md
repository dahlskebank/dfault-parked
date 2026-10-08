# D-FAULT PARKED

A single "coming soon" page shared by Daniel Dahl's parked domains. Every parked domain points at the same folder; `config.php` reads the domain the visitor arrived on and picks that domain's title, background image and gradient, opacities and Open Graph text. Domains that aren't in the list get a generic "Coming Soon" fallback.

Plain PHP on Apache, hand-written CSS, no framework. No build step: the project folder is the web root.

## Adding a domain

1. Put the background image in `img/` (JPG, ~1200–1600 px wide, quality 90).
2. Add an entry to `$configs` in `config.php` (domain without `www.`). List only what differs from `$defaults`, but always set `background_image`, `bg_opacity`, `overlay_opacity` and `gradient` (two hex colours, dark enough for the amber text). The text colour, dfault amber `#ffc107`, comes from `$defaults` and is the same on every domain.
3. Point the domain at the parking host.
4. Run the checks (below).

## Files

| File | What it does |
|------|--------------|
| `config.php` | Host cleanup, `$defaults`, the domain list |
| `index.php` | The landing page |
| `404.php` / `403.php` | Error pages (wired up in `.htaccess`) |
| `partials/head.php` | Everything from `<!DOCTYPE>` to the page wrapper |
| `partials/footer.php` | Footer, scripts, closing tags |
| `partials/helpers.php` | `e()`, `asset()`, `color_or()`, `gradient_or()` |
| `style.css` | All styles (no framework) |
| `dfault.js` | The random "coming soon" countdown |
| `robots.txt` | Allows all crawlers |
| `.htaccess` | Blocked paths, redirects, headers, caching, error pages |
| `img/` | Background images |
| `_originals/` | Untouched source images (blocked from the web) |
| `_docs/` | Design spec and plan (blocked) |
| `_tests/` | Checks (blocked) |

## Checks

PHP and Apache come from Laragon (not on PATH):

```bash
"E:/vlaragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" _tests/check.php   # every page × 25 hosts
node _tests/countdown.test.js                                               # dfault.js
bash _tests/probe.sh                                                        # .htaccess on a throwaway Apache
bash _tests/probe.sh --prod kiande.com                                      # live server, read-only (after deploy)
```

## License

[WTFPL](LICENSE)
