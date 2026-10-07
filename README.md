# D-FAULT PARKED

A single "coming soon" page shared by Daniel Dahl's parked domains. Every parked domain points at the same folder; `config.php` reads the domain the visitor arrived on and picks that domain's title, background image, opacity and Open Graph text. Domains that aren't in the list get a generic "Coming Soon" fallback.

Plain PHP on Apache. No build step: the project folder is the web root.

## Adding a domain

1. Put the background image in `img/`.
2. Add an entry for the domain (without `www.`) to the `$configs` array in `config.php`.
3. Point the domain at the parking host.

## Files

| File | What it does |
|------|--------------|
| `config.php` | Domain list and per-domain settings |
| `index.php` | The landing page |
| `404.php` / `403.php` | Error pages (wired up in `.htaccess`) |
| `style.css` | Custom styles on top of the theme |
| `dfault.js` | The random "coming soon" countdown |
| `.htaccess` | Security headers, rewrites, error pages |

## License

[WTFPL](LICENSE)
