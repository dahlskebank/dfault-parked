# D-FAULT PARKED refactor — design

Date: 2026-10-07 · Status: awaiting Daniel's review

## Goal

Make the parked page lighter, easier to maintain and visually tidier, with the same concept and content as today:

- One template, shared by every parked domain. `config.php` picks title, image and copy per domain.
- Bootstrap, Bootswatch Quartz, Bootstrap Icons and the Bootstrap JS bundle are replaced by hand-written CSS.
- Each domain gets its own accent colour, pulled from its background image.
- The four big frosted countdown cards become one slim glass bar.
- Every fix from the 2026-10-07 code review is applied.

**Success means:**
- adding a domain takes one short config entry
- each page load weighs a fraction of today's
- the page looks right in Chrome on Windows 11 and Chrome on a Pixel
- no PHP warnings for any host
- the server rules behave as listed under "Verification"

## Context

- As of 2026-10-07, 11 domains serve this page in production: brutalina.com, compoundcomplex.com, cybabes.org, cybocop.com, danieldahl.com, fatalityfacilitator.com, iliketomovie.com, kennywang.com, kiande.com, meatsex.org, zombiefetish.com.
- The other 9 config entries (arksanity.com, dahlskebank.com, darkdictator.com, iliveagain.com, killingheat.com, marxisthunter.com, meatfetish.com, meloslave.com, reservedekk.no) run their own sites. Their entries stay as unused presets.
- The project folder is the web root. There is no build step.

## 1. Files

```
index.php            landing page
404.php / 403.php    error pages (~10 lines each)
config.php           defaults, domain list, host cleanup          blocked
partials/head.php    <!DOCTYPE> … opening of the page wrapper      blocked
partials/footer.php  footer, scripts, closing tags                 blocked
style.css            hand-written, no framework
dfault.js            countdown
robots.txt           allow all
img/                 background images, JPG only
_originals/          untouched originals (PNGs, q100 JPGs, *_unused.jpg)   blocked
_docs/               specs and plans                               blocked
.gitignore, LICENSE, README.md
```

`403.php` and `404.php` stay as separate files. Nothing is deleted, and the `ErrorDocument` lines stay as they are.

## 2. config.php

- **`$defaults`** holds every key:
  - `title` = `Coming Soon`
  - `background_image` = `''`
  - `bg_opacity` = `0.50`
  - `overlay_opacity` = `0.35`
  - `accent` = `#ffc107`
  - `og_title` = `null` (falls back to `title`)
  - `og_description` = `D-FAULT PARKED`
  - `ga_id` = `''`
- **`$configs`:** each domain lists only the keys that differ from the defaults.
- **Merge:** `$settings = array_merge($defaults, $configs[$domain] ?? [])`.
- **Host cleanup:** lowercase, strip `:port`, strip the trailing `.`, strip a leading `www.`.
- **`$isKnown`** is `isset($configs[$domain])`.
- The file gets educational comments explaining each part.

## 3. Page assembly

Each page sets a few variables, then includes the partials.

**Variables:**
- `$pageTitle`: the part after `|`, e.g. "Coming Soon" or "404 Page Not Found".
- `$robots`
- `$scripts`: an array of script paths. Only `index.php` sets one (`/dfault.js`).

**`partials/head.php` outputs:**
- charset and viewport
- `<title>`:
  - known host: `{title} | {pageTitle}`
  - unknown host: just `{pageTitle}`, so no more "Coming Soon | Coming Soon"
- description, robots
- `theme-color` = the accent colour
- `canonical`, only for a known host on `index.php`
- favicon
- `style.css?v={filemtime}`
- GA, only if `ga_id` is set
- one inline `<style>`:
  - `:root { --accent: … }`
  - the `.bg-image` background (overlay gradient + `url(img?v={filemtime})`) and its opacity
  - the accent must match `^#[0-9a-f]{6}$` and both opacities are cast to float before output
- OG and Twitter tags, only for a known host:
  - `og:image` and `twitter:image` only when the domain has a background image
  - `twitter:card` is `summary_large_image` with an image, `summary` without
- the opening `<body>`, the `.bg-image` layer and the opening page wrapper

**Unknown hosts** get `noindex`, and no canonical, `og:url` or `og:image`.

**Error pages** get `noindex`, no canonical and no OG tags. They set their status with `http_response_code()`.

**`partials/footer.php`:** today's footer text and links. `©` and `·` become HTML entities, not icons. Footer links use the accent colour. It also outputs each `$scripts` entry with `?v={filemtime}`.

## 4. Look (style.css)

**Kept from today:**
- background: Quartz's gradient `linear-gradient(90deg, #33b7e2, #5e62b0, #dc307c)` on `body`
- the fixed `.bg-image` layer above it, with the domain's image, overlay and opacity
- font: the system stack (Segoe UI on Windows, Roboto on Android). No web fonts.
- text colours: body `#dee2e6`, secondary text at 75% opacity
- headline:
  - one `<h1>`: "Website" at weight 300, then "Coming Soon" at weight 400 in the accent colour
  - "Coming Soon" drops to its own line below 992px
  - size `clamp(2.6rem, 1.625rem + 4.5vw, 5rem)`, line-height 1.2
  - today's text-shadows
- tagline: 1.25rem, weight 300, same text, same line split below 992px
- footer: weight 600 and 2rem bottom padding from 992px up

**New:**
- **Layout:** a flex column with `min-height: 100dvh` (spacer, centred `<main>`, footer at the bottom) and 16px side padding. Breakpoint 992px: `min-width: 992px` and `max-width: 991.98px`, with no overlap.
- **Countdown bar:** one element using Quartz's glass style:
  - a `linear-gradient(125deg, rgba(255,255,255,.3), rgba(255,255,255,.2) 70%)` background
  - `backdrop-filter: blur(5px)`
  - inset 1px highlights and a soft outer shadow
  - `border-radius: .5rem`
  - centred, at most about 36rem wide

  Inside the bar:
  - four units in one row on every screen width, with 1px dividers at 20% white
  - numbers in the accent colour, weight 300, `clamp(1.8rem, 1.2rem + 3vw, 3.25rem)`, `font-variant-numeric: tabular-nums`
  - labels small, uppercase, letter-spaced, in secondary colour

  Target height is about half of today's cards.
- **Markup:** units are `<div>`/`<span>`, not headings, so each page has exactly one `<h1>`.
- **Focus:** a visible `:focus-visible` outline on links.

## 5. Accent colours

Applied to: "Coming Soon", the countdown numbers, footer links and `theme-color`. `theme-color` tints Chrome's toolbar on Android; desktop Chrome ignores it in normal tabs.

**Picking a colour:**
1. Downscale the image.
2. Choose the most prominent saturated colour, using a hue histogram weighted by saturation × brightness.

**Contrast check:**
- Test the colour against what is actually behind the text: the Quartz gradient, then the image with its overlay at its opacity on top. For the countdown numbers, also add the glass layer.
- If the colour fails, lighten it in HSL until it passes:
  - 3:1 for the headline and the countdown numbers (large text)
  - 4.5:1 for the footer links (small text)

**Review:** a swatch sheet (an HTML file in the scratchpad) shows each domain's image next to its accent and contrast ratios. Daniel approves or changes the values, and only then are they written into `config.php`. Unknown hosts keep `#ffc107`.

## 6. Images

- PNG → JPG at quality 90, progressive, optimised, ICC profile kept. Pixel dimensions unchanged.
- The 5 JPGs in use that were saved at about q100 (arksanity, brutalina, compoundcomplex, fatalityfacilitator, zombiefetish) are re-saved at q90.
- `meatsex.jpg` (about q93) is left untouched.
- All replaced originals move to `_originals/img/`. Both `*_unused.jpg` move there unchanged.
- `config.php` paths are updated from `.png` to `.jpg`.
- Expected weight of the 14 PNGs drops from 11.98 MB to about 3.8 MB.

## 7. .htaccess

- Remove rewrite rules 1–3.
- 301 `/index.php` → `/`. The rule has a `REDIRECT_STATUS` guard, so internal error-page passes never trigger it.
- 301 `www.{domain}` → `https://{domain}` with the path kept. One rule covers every domain.
- HTTP → HTTPS:
  - production already redirects HTTP to HTTPS for the domains checked
  - before deciding, confirm this on a parked domain via real DNS (`curl --resolve`, not the hosts file)
  - add a rule only if the host doesn't already do it
- `Options -Indexes -MultiViews`.
- Block, returning 403:
  - `config.php`, `partials/`, `_originals/`, `_docs/`
  - any path starting with a dot (`.git`, `.gitignore`, …) except `.well-known/`
- Headers:
  - keep `X-Frame-Options`, `X-Content-Type-Options` and `Referrer-Policy`
  - add `Permissions-Policy: camera=(), microphone=(), geolocation=()`
  - `Header unset X-Powered-By`
- Gzip (`mod_deflate`) for HTML, CSS, JS, SVG and ICO.
- Caching (`mod_expires`): 1 year for images, CSS and JS, which is safe because of the `?v=` URLs. HTML is not cached.
- `ErrorDocument 403/404` stay as they are.
- Not included for this Tier 1 page: CSP and HSTS.

## 8. Other files

- **`robots.txt`:** `User-agent: *` and `Allow: /`.
- **`dfault.js`:**
  - wrap in an IIFE
  - stop silently if `#days` is missing
  - keep the random 30–180 day target (a design choice)
  - remove the unreachable "finished" branch
- **README:** update the file table after the refactor.

## Out of scope

Analytics consent, CSP/HSTS, deploy setup, WebP, and a local multi-domain preview (dropped on 2026-10-07).

## Verification

1. **Lint:** `php -l` on every PHP file, using Laragon's PHP 8.3.30.
2. **Render with all warnings on (`E_ALL`)** for all 20 config hosts plus `unknown.example`, `kiande.com:443`, `kiande.com.` and `WWW.Kiande.com`. Each page must have exactly one `<h1>` and zero warnings. Check, per host:
   - correct title, robots and canonical
   - OG tags present or absent as specified in section 3
   - `og:image` only when an image exists
   - `--accent` and `theme-color` match the config
3. **Before/after diff for step 1:** the HTML for all hosts is compared before and after the structure change. Only the intended differences may appear.
4. **Throwaway Apache:** Laragon's httpd 2.4.66 with a scratchpad config and docroot copy, on port 18765, probed with curl. Expected results:

   | Request | Result |
   |---------|--------|
   | `/` | 200 |
   | `/index.php` | 301 `/` |
   | `www.` host | 301 to the bare domain |
   | `/config.php` | 403 page |
   | `/partials/head.php` | 403 |
   | `/_originals/` | 403 |
   | `/_docs/…` | 403 |
   | `/.git/HEAD` | 403 |
   | `/nope` | 404 page |
   | `/nope.php` | 404 page |
   | `/robots.txt` | 200 |

   Also check that the security headers are present on 200, 403 and 404 responses, `X-Powered-By` is gone, `Expires` and `Cache-Control` are set on images, CSS and JS, and gzip is on for HTML and CSS.
5. **Images:** every config image exists, dimensions are unchanged, and a before/after size table is produced.
6. **Accents:** a contrast table for all 20 domains, plus Daniel's swatch approval.
7. **Visuals:** Daniel checks them himself. No browser automation.

## Build order

Each step ends with a commit.

0. Git repo and GitHub `dahlskebank/dfault-parked` (done, `2ab6549`).
1. Config, partials and page assembly, plus the head and meta fixes from section 3. The page looks identical in the browser and Bootstrap is still loaded. `theme-color` stays `#0f172a` and the footer keeps its icons for now.
2. `.htaccess` and `robots.txt`.
3. Images, then accent colours (swatch approval gate). Once approved, the accents go into `config.php`, and `--accent` and `theme-color` are wired up.
4. Remove Bootstrap, add the new `style.css` and countdown bar. Also: the single-`<h1>` markup, footer entities instead of icons, and the `dfault.js` cleanup.
5. README update.

## Amendment 2026-10-08: colour

These changes replace parts of sections 4 and 5. They were decided at the colour approval stop, after the planned accent picker produced near-white pastels.

- **Text colour:** dfault amber `#ffc107` on every domain, for "Coming Soon", the countdown numbers and the footer links. Daniel: "that's the dfault orange construction color". `accent` lives only in `$defaults`.
- **Per-domain colour:**
  - Each domain gets its own background gradient, which replaces the fixed Quartz gradient behind the image.
  - The config key is `'gradient' => ['#c1', '#c2']`: the image's two strongest distinct hues, darkened so the amber stays readable.
  - Unknown hosts keep Quartz's `['#33b7e2', '#5e62b0', '#dc307c']`.
  - The black overlay is unchanged.
- **`theme-color`:** the gradient's first colour, so the Android address bar tints per domain.
- **Countdown bar:** the glass is slightly darker so the amber numbers read well: `linear-gradient(125deg, rgba(255,255,255,.12), rgba(255,255,255,.06) 70%)` over `rgba(0,0,0,.3)`, still blurred 5px.
- **Validation:** `gradient_or()` accepts 2–4 valid hex colours, otherwise it falls back to the default.
- **Review:** Daniel reviews the gradient colours live after deploy. There's no swatch sheet before applying.
