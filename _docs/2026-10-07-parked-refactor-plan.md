# D-FAULT PARKED Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the parked page on shared partials and hand-written CSS, with a per-domain accent colour, a slim glass countdown bar, a clean `.htaccess` and JPG backgrounds. Content and concept stay the same.

**Architecture:**
- **Plain PHP, no build step.** The project folder is the web root.
- **`config.php`:** normalises the Host header, merges the domain's entry over `$defaults`, and sets `$isKnown`.
- **Pages:** `index.php`, `404.php` and `403.php` set four variables, then include `partials/head.php` and `partials/footer.php`.
- **Styling:** per-domain values reach the CSS as custom properties in one inline `<style>`. Everything else lives in `style.css`.

**Tech Stack:** PHP (production version unknown, so keep it 7.4-compatible), Apache 2.4 `.htaccess` behind the host's nginx, vanilla CSS and JS. Tooling: Laragon's PHP 8.3.30 and Apache 2.4.66 for tests, Python with Pillow for images, Node 24 for the JS test.

**Spec:** `_docs/2026-10-07-parked-refactor-design.md` (approved 2026-10-07).

**Additions beyond the spec's file list, both needed for verification:**
- `partials/helpers.php`, holding `e()`, `asset()` and `color_or()`
- `_tests/`, holding `check.php`, `probe.sh` and `countdown.test.js`, blocked by the `^_` rule

## Global Constraints

- **PHP 7.4-compatible site code:** no `match`, no `str_contains`/`str_starts_with`/`str_ends_with`, no nullsafe `?->`, no named arguments, no `??=`.
- **No build step:** the project root is the web root.
- **After Task 4:** no CDN, no framework, no web fonts. The system font stack only.
- **Indentation:** tabs, as in the existing files.
- **Comments:** short, educational comments in the PHP, CSS and `.htaccess`. Daniel is learning from them.
- **Moving files:** use `git mv` into `_originals/` and never delete. The `*_unused.jpg` files move unchanged.
- **Per-image values stay explicit:** `bg_opacity` and `overlay_opacity` stay in every domain entry, even when they equal a default. Only `ga_id` (always `''`) is removed.
- **Tool paths:**
  - PHP: `E:/vlaragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe` (written as `$PHP` below)
  - Apache: `E:/vlaragon/bin/apache/httpd-2.4.66-260223-Win64-VS18`
  - throwaway port: `18765`
- **Laragon:** never touch its config or running server.
- **Visuals:** no browser automation. Daniel checks them himself.
- **Commits:**
  - one commit per task, ending with `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`
  - push after each task (`git push`)
- **Accents:** written to `config.php` only after Daniel approves the swatch sheet (Task 3 gate).
- **Fallback accent:** `#ffc107`.

Shell setup used in every task (Git Bash, run from the project root `e:/www/dev/___parked`):

```bash
PHP="E:/vlaragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe"
SCRATCH="C:/Users/JACKST~1/AppData/Local/Temp/claude/e--www-dev----parked/a60ea5da-c930-4f67-83bb-1fd6000b9fa0/scratchpad"
```

## Review Focus

1. **Quotes, apostrophes and `&` in titles or OG text** (e.g. `Yes We're The Villains`, `"Emotionally Constipated By Choice"`, `"raw dog"`) must come out of the HTML exactly as typed, with no double-escaping and no broken attributes. Pinned in Task 1: `check.php` compares the decoded `<title>`, `og:title` and `og:description` with the config values.
2. **A typo'd or hostile `accent` value** (`#ffc10`, `red`, `#FFC107`, `#fff;}body{display:none`) must fall back to or be normalised into a valid 6-digit hex. It must never print broken CSS. Pinned in Task 3: unit checks on `color_or()`.
3. **PNGs with transparency or a colour profile** would turn black or shift colour as JPG. Conversion must stop on transparency and verify size and colour fidelity. Pinned in Task 3: `convert_images.py` exits non-zero on transparent pixels, a dimension change, or a mean channel difference ≥ 3.
4. **Bot probes** (`/wp-login.php`, `/xmlrpc.php`, `/.env`, `/nope/`) must get a plain 404 or 403 page, never a redirect chain. Pinned in Task 2: `probe.sh` lines.
5. **Production differs from local Apache.** nginx sits in front, HTTPS is handled at host level, the www certificate has expired, and the PHP version is unknown. After deploy, the same rules must hold on the live server. Pinned in Task 2: `probe.sh --prod <domain>`, a read-only run against the real-DNS IP, done after deploy.

---

### Task 1: Config defaults, partials and page assembly (no visual change)

**Files:**
- Create: `_tests/check.php`, `partials/helpers.php`, `partials/head.php`, `partials/footer.php`
- Modify (full rewrite): `config.php`, `index.php`, `404.php`, `403.php`

**Interfaces:**
- Produces, from `config.php`:
  - `$host` (string)
  - `$domain` (string: lowercased, no port, trailing dot or `www.`)
  - `$defaults` (array)
  - `$configs` (array keyed by domain)
  - `$isKnown` (bool)
  - `$settings` (array: defaults merged with the domain entry; `og_title` is never null)
- Produces, from `partials/helpers.php`: `e($value): string` (HTML escape) and `asset($path): string` (adds `?v=<filemtime>` when the file exists)
- Page contract. Every page sets these before including `partials/head.php`:
  - `$pageTitle` (string)
  - `$robots` (string)
  - `$isHome` (bool)
  - `$scripts` (array of URL paths)
- Produces `_tests/check.php`. CLI flags: `--dump=DIR` saves rendered pages, and `--dump-only` skips the checks. Exit code 0/1. Tasks 3 and 4 extend its per-host checks.

- [ ] **Step 1: Write the test harness `_tests/check.php`**

```php
<?php
// =====================================================================
//  _tests/check.php — render every page for many hosts, check the HTML
// ---------------------------------------------------------------------
//  PHP isn't on PATH, so run it with Laragon's PHP from the project root:
//    "E:/vlaragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" _tests/check.php
//  Options:
//    --dump=DIR    also save every rendered page into DIR (for diffing)
//    --dump-only   only save pages, skip the checks
//  Exit code 0 = all checks passed, 1 = something failed.
// =====================================================================

$root = dirname(__DIR__);

// Load the domain list the same way the site does.
$_SERVER['HTTP_HOST'] = '';
require $root . '/config.php';

$dump = null;
$dumpOnly = in_array('--dump-only', $argv, true);
foreach ($argv as $arg) {
	if (strpos($arg, '--dump=') === 0) {
		$dump = substr($arg, 7);
		if (!is_dir($dump)) {
			mkdir($dump, 0777, true);
		}
	}
}

$checks = 0;
$failures = 0;
function check($ok, $label) {
	global $checks, $failures;
	$checks++;
	if (!$ok) {
		$failures++;
		echo "FAIL  $label\n";
	}
}

// Render one page as if the browser sent "Host: $host". Each render runs in
// its own PHP process, so pages can't leak variables into each other.
function render($page, $host) {
	global $root;
	$code = '$_SERVER["HTTP_HOST"] = $argv[1]; chdir($argv[2]); include $argv[3];';
	$cmd = [PHP_BINARY, '-d', 'display_errors=stderr', '-d', 'error_reporting=-1', '-r', $code, '--', $host, $root, $page];
	$proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
	$out = stream_get_contents($pipes[1]);
	$err = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	proc_close($proc);
	return [$out, trim($err)];
}

// Pull the bits we check out of the HTML.
function inspect($html) {
	$dom = new DOMDocument();
	libxml_use_internal_errors(true);
	$dom->loadHTML($html);
	libxml_clear_errors();
	$x = new DOMXPath($dom);
	$first = function ($query) use ($x) {
		$nodes = $x->query($query);
		return $nodes->length ? $nodes->item(0)->nodeValue : null;
	};
	return [
		'title'     => $first('//title'),
		'robots'    => $first('//meta[@name="robots"]/@content'),
		'canonical' => $first('//link[@rel="canonical"]/@href'),
		'og_url'    => $first('//meta[@property="og:url"]/@content'),
		'og_title'  => $first('//meta[@property="og:title"]/@content'),
		'og_desc'   => $first('//meta[@property="og:description"]/@content'),
		'og_image'  => $first('//meta[@property="og:image"]/@content'),
		'tw_card'   => $first('//meta[@name="twitter:card"]/@content'),
		'tw_image'  => $first('//meta[@name="twitter:image"]/@content'),
		'theme'     => $first('//meta[@name="theme-color"]/@content'),
		'style'     => $first('//head/style'),
		'h1_count'  => $x->query('//h1')->length,
	];
}

// 1. Lint every PHP file.
if (!$dumpOnly) {
	foreach (array_merge(glob("$root/*.php"), glob("$root/partials/*.php")) as $file) {
		exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $lint, $status);
		check($status === 0, 'lint ' . basename($file) . ': ' . implode(' ', $lint));
		$lint = [];
	}
	if (!isset($defaults)) {
		echo "FAIL  config.php has no \$defaults yet (old structure)\n";
		exit(1);
	}
}

// 2. Hosts to try: [what the browser sends, config key it must resolve to (null = unknown)].
$hosts = [];
foreach (array_keys($configs) as $d) {
	$hosts[] = [$d, $d];
}
$hosts[] = ['kiande.com:443', 'kiande.com'];
$hosts[] = ['kiande.com.', 'kiande.com'];
$hosts[] = ['WWW.Kiande.com', 'kiande.com'];
$hosts[] = ['unknown.example', null];
$hosts[] = ['', null];

$pages = [
	'index.php' => ['suffix' => 'Coming Soon',        'robots' => 'index, follow', 'home' => true],
	'404.php'   => ['suffix' => '404 Page Not Found', 'robots' => 'noindex',       'home' => false],
	'403.php'   => ['suffix' => '403 Access Denied',  'robots' => 'noindex',       'home' => false],
];

foreach ($pages as $page => $p) {
	foreach ($hosts as [$host, $key]) {
		[$html, $err] = render($page, $host);
		$label = sprintf('%-9s %-24s', $page, $host === '' ? '(empty)' : $host);
		if ($dump) {
			$name = str_replace('.php', '', $page) . '__' . preg_replace('/[^a-z0-9.]+/i', '_', $host === '' ? 'empty' : $host);
			file_put_contents("$dump/$name.html", $html);
		}
		if ($dumpOnly) {
			continue;
		}

		$i     = inspect($html);
		$known = $key !== null;
		$s     = $known ? array_merge($defaults, $configs[$key]) : $defaults;
		$ogT   = $s['og_title'] !== null ? $s['og_title'] : $s['title'];
		$seo   = $known && $p['home'];
		$img   = $s['background_image'];
		$url   = "https://$key/";

		check($err === '', "$label PHP printed: " . strtok($err, "\n"));
		check($i['title'] === ($known ? "{$s['title']} | {$p['suffix']}" : $p['suffix']), "$label title is '{$i['title']}'");
		check($i['robots'] === ($known ? $p['robots'] : 'noindex'), "$label robots is '{$i['robots']}'");
		check($i['canonical'] === ($seo ? $url : null), "$label canonical is '" . ($i['canonical'] ?? 'none') . "'");
		check($i['og_url'] === ($seo ? $url : null), "$label og:url is '" . ($i['og_url'] ?? 'none') . "'");
		check($i['og_title'] === ($seo ? $ogT : null), "$label og:title is '" . ($i['og_title'] ?? 'none') . "'");
		check($i['og_desc'] === ($seo ? $s['og_description'] : null), "$label og:description is '" . ($i['og_desc'] ?? 'none') . "'");
		check($i['og_image'] === ($seo && $img ? "https://$key$img" : null), "$label og:image is '" . ($i['og_image'] ?? 'none') . "'");
		check($i['tw_image'] === ($seo && $img ? "https://$key$img" : null), "$label twitter:image is '" . ($i['tw_image'] ?? 'none') . "'");
		check($i['tw_card'] === ($seo ? ($img ? 'summary_large_image' : 'summary') : null), "$label twitter:card is '" . ($i['tw_card'] ?? 'none') . "'");
		if ($img) {
			check(strpos((string) $i['style'], $img . '?v=') !== false, "$label background image missing or not cache-busted");
		}
	}
}

// 3. Literal spot checks, so a bug in the merge logic can't hide behind
//    expectations computed from the same config.
if (!$dumpOnly) {
	$i = inspect(render('index.php', 'WWW.KIANDE.COM:80')[0]);
	check($i['title'] === 'KiAnDe | Coming Soon', "spot: kiande title is '{$i['title']}'");
	$i = inspect(render('index.php', 'darkdictator.com')[0]);
	check($i['og_title'] === "Dark Dictator | Yes We're The Villains And We're Honest About It", "spot: apostrophes in og:title");
	$i = inspect(render('index.php', 'unknown.example')[0]);
	check($i['title'] === 'Coming Soon' && $i['canonical'] === null, "spot: unknown host title/canonical");
}

if ($dumpOnly) {
	echo "Dumped pages to $dump\n";
	exit(0);
}
echo $failures ? "$failures of $checks checks failed\n" : "All $checks checks passed\n";
exit($failures ? 1 : 0);
```

- [ ] **Step 2: Snapshot the current output for the before/after diff**

Run: `"$PHP" _tests/check.php --dump-only --dump="$SCRATCH/before"`
Expected: `Dumped pages to …/before`, with 75 files (3 pages × 25 hosts).

- [ ] **Step 3: Run the checks against the old code and watch them fail**

Run: `"$PHP" _tests/check.php`
Expected: the lint lines pass, then `FAIL  config.php has no $defaults yet (old structure)` and exit code 1.

- [ ] **Step 4: Rewrite `config.php`**

```php
<?php
// =====================================================================
//  D-FAULT PARKED — domain config
// ---------------------------------------------------------------------
//  Every parked domain points at this same folder. This file works out
//  which domain the visitor typed, then picks that domain's settings.
//
//  Adding a domain: put its image in /img/, then add an entry to
//  $configs below with only the keys that differ from $defaults.
// =====================================================================

// ---------------------------------------------------------------------
// 1. Which domain is this?
// ---------------------------------------------------------------------
// HTTP_HOST is whatever the browser sent, e.g. "www.Kiande.com",
// "kiande.com:443" or "kiande.com." (a trailing dot is valid DNS).
// Normalise so all of those match the 'kiande.com' key.
$host   = strtolower(trim($_SERVER['HTTP_HOST'] ?? ''));
$host   = preg_replace('/:\d+$/', '', $host);   // strip :port
$host   = rtrim($host, '.');                    // strip trailing dot
$domain = preg_replace('/^www\./', '', $host);  // strip www.

// ---------------------------------------------------------------------
// 2. Defaults — every setting a domain can have, with its fallback
// ---------------------------------------------------------------------
$defaults = [
	'title'            => 'Coming Soon',
	'background_image' => '',        // '/img/name.jpg', or '' for gradient only
	'bg_opacity'       => 0.50,      // opacity of the whole image layer
	'overlay_opacity'  => 0.35,      // black overlay on top of the image
	'accent'           => '#ffc107', // highlight colour, 6-digit hex
	'og_title'         => null,      // null = use title
	'og_description'   => 'D-FAULT PARKED',
	'ga_id'            => '',        // Google Analytics ID, '' = off
];

// ---------------------------------------------------------------------
// 3. Domains — only what differs from $defaults
// ---------------------------------------------------------------------
// Opacities stay listed on every domain because they're tuned per image.
$configs = [
	'arksanity.com' => [
		'title'				=> 'Arksanity Server',
		'background_image'	=> '/img/arksanity.jpg',
		'bg_opacity'		=> 0.65,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'Arksanity - ARK: Survival Evolved',
		'og_description'	=> 'Dedicated Linux Server for the Popular Game ARK: Survival Evolved'
	],
	'brutalina.com' => [
		'title'				=> 'Brutalina the Movie',
		'background_image'	=> '/img/brutalina.jpg',
		'bg_opacity'		=> 0.75,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'Brutalina the Movie | Too Violent For Your Therapist',
		'og_description'	=> 'The bloodiest cinematic experience of the decade. So unhinged they almost cancelled it before filming started. Coming soonTM (probably).'
	],
	'compoundcomplex.com' => [
		'title'				=> 'Compound Complex',
		'background_image'	=> '/img/compoundcomplex.jpg',
		'bg_opacity'		=> 0.60,
		'overlay_opacity'	=> 0.75,
		'og_title'			=> 'Compound Complex | Sentences That Make English Teachers Quit',
		'og_description'	=> 'Grammar so convoluted even linguists need therapy. The only website that requires a flowchart to understand itself.'
	],
	'cybabes.org' => [
		'title'				=> 'Cybabes Society',
		'background_image'	=> '/img/cybabes.png',
		'bg_opacity'		=> 0.75,
		'overlay_opacity'	=> 0.55,
		'og_title'			=> 'Cybabes | Your AI Waifus Are Finally Online (Fashionably 27 Years Late)',
		'og_description'	=> 'Virtual girlfriends who reply... eventually. Better personalities than your ex. Zero emotional baggage (until the update).'
	],
	'cybocop.com' => [
		'title'				=> 'Cybo Cop (TV Series)',
		'background_image'	=> '/img/cybocop.png',
		'bg_opacity'		=> 0.75,
		'overlay_opacity'	=> 0.35,
		'og_title'			=> 'Cybocop | Half Cop, Half Machine, All Existential Dread',
		'og_description'	=> 'Policing the Internet one bad take at a time. Will arrest you for bad grammar and poor life choices.'
	],
	'dahlskebank.com' => [
		'title'				=> 'Dahlske Bank',
		'background_image'	=> '/img/dahlskebank.png',
		'bg_opacity'		=> 0.65,
		'overlay_opacity'	=> 0.25,
		'og_title'			=> 'Dahlske Bank | Norwegian Banking With Questionable Ethics',
		'og_description'	=> 'Where your money goes to die... slowly and with excellent customer service. Coming soon (after the next audit).'
	],
	'danieldahl.com' => [
		'title'				=> 'Daniel Dahl',
		'background_image'	=> '/img/danieldahl.png',
		'bg_opacity'		=> 0.55,
		'overlay_opacity'	=> 0.65,
		'og_title'			=> 'Daniel Dahl | Professional Human, Amateur God',
		'og_description'	=> 'Full-time professional at being Daniel Dahl. Part-time deity. 100% Norwegian chaos energy.'
	],
	'darkdictator.com' => [
		'title'				=> 'The Dark Dictator',
		'background_image'	=> '/img/darkdictator.png',
		'bg_opacity'		=> 0.65,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'Dark Dictator | Yes We\'re The Villains And We\'re Honest About It',
		'og_description'	=> 'Total world domination in progress. Please stand by. (Or else.)'
	],
	'fatalityfacilitator.com' => [
		'title'				=> 'Fatality Facilitator',
		'background_image'	=> '/img/fatalityfacilitator.jpg',
		'bg_opacity'		=> 0.35,
		'overlay_opacity'	=> 0.00,
		'og_title'			=> 'Fatality Facilitator | Making Death Slightly More Convenient',
		'og_description'	=> 'Professional help with your inevitable demise since 2005. Fast, discreet, and weirdly polite.'
	],
	'iliketomovie.com' => [
		'title'				=> 'I Like To Movie',
		'background_image'	=> '/img/iliketomovie.png',
		'bg_opacity'		=> 0.75,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'I Like To Movie | I Don\'t Watch Films, I Mainline Them Like Heroin',
		'og_description'	=> 'Terminal cinephilia. Entire personality is just obscure references and emotional damage. Coming soon (after my 47th rewatch of the same movie).'
	],
	'iliveagain.com' => [
		'title'				=> 'I Live Again',
		'background_image'	=> '/img/iliveagain.png',
		'bg_opacity'		=> 0.75,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'I Live Again | Death Was A Phase',
		'og_description'	=> 'Came back wrong on purpose. Twice as unhinged and three times as hot. Resurrection kink activated.'
	],
	'kennywang.com' => [
		'title'				=> 'Kenny Wang',
		'background_image'	=> '/img/kennywang.png',
		'bg_opacity'		=> 0.65,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'Kenny Wang | 100% Real Human Person (Source: Dude Trust Me)',
		'og_description'	=> 'Professional Wang. Amateur Kenny. Full-time identity theft victim of his own name.'
	],
	'kiande.com' => [
		'title'				=> 'KiAnDe',
		'background_image'	=> '/img/kiande.png',
		'bg_opacity'		=> 0.50,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'KiAnDe | Alien Queen In A Human Suit',
		'og_description'	=> 'Xenomorph-coded but make it sexy. We\'re not saying we lay eggs... but we\'re not denying it either.'
	],
	'killingheat.com' => [
		'title'				=> 'Killing Heat',
		'background_image'	=> '/img/killingheat.png',
		'bg_opacity'		=> 0.65,
		'overlay_opacity'	=> 0.25,
		'og_title'			=> 'Killing Heat | So Hot It Should Be Classified As A War Crime',
		'og_description'	=> 'Global warming is just foreplay. This website will literally melt your face off.'
	],
	'marxisthunter.com' => [
		'title'				=> 'Marxist Hunter',
		'background_image'	=> '/img/marxisthunter.png',
		'bg_opacity'		=> 0.50,
		'overlay_opacity'	=> 0.30,
		'og_title'			=> 'Marxist Hunter | From Each According To His Ability, To Each According To My 12-Gauge',
		'og_description'	=> 'Licensed commie remover. Class consciousness? Nah. Class annihilation.'
	],
	'meatfetish.com' => [
		'title'				=> 'Meat Fetish',
		'background_image'	=> '/img/meatfetish.png',
		'bg_opacity'		=> 0.45,
		'overlay_opacity'	=> 0.20,
		'og_title'			=> 'Meat Fetish | We Don\'t Eat The Meat... We Fuck It',
		'og_description'	=> 'Carnivores with commitment issues. The only site where "raw dog" has a completely different meaning.'
	],
	'meatsex.org' => [
		'title'				=> 'Meat Sex',
		'background_image'	=> '/img/meatsex.jpg',
		'bg_opacity'		=> 0.45,
		'overlay_opacity'	=> 0.30,
		'og_title'			=> 'Meatsex | Where The Sausage Party Gets Extremely Literal',
		'og_description'	=> 'Not safe for vegetarians. Not safe for vegans. Not safe for anyone with a functioning moral compass.'
	],
	'meloslave.com' => [
		'title'				=> 'Meloslave',
		'background_image'	=> '/img/meloslave.png',
		'bg_opacity'		=> 0.75,
		'overlay_opacity'	=> 0.25,
		'og_title'			=> 'Meloslave | Submit To The Beat And Call Me Master',
		'og_description'	=> 'BDSM but the safe word is lo-fi beats to cry and obey to. Melancholy never sounded so submissive.'
	],
	'reservedekk.no' => [
		'title'				=> 'Reservedekk',
		'background_image'	=> '/img/reservedekk.png',
		'bg_opacity'		=> 0.55,
		'overlay_opacity'	=> 0.20,
		'og_title'			=> 'Reserved Ekk | Norwegian For "Emotionally Constipated By Choice"',
		'og_description'	=> 'We don\'t do feelings. We do long awkward silences and passive-aggressive "hei". Peak Scandinavian dysfunction.'
	],
	'zombiefetish.com' => [
		'title'				=> 'Zombie Fetish',
		'background_image'	=> '/img/zombiefetish.jpg',
		'bg_opacity'		=> 0.35,
		'overlay_opacity'	=> 0.00,
		'og_title'			=> 'Zombie Fetish | Cold Dead Flesh Is My Love Language',
		'og_description'	=> 'Brains are overrated. Give us rigor mortis and that sweet post-mortem drip. Necrophilia with extra steps.'
	],
];

// ---------------------------------------------------------------------
// 4. This request's settings
// ---------------------------------------------------------------------
// $isKnown is false for any host not listed above: a typo, a bare IP, or
// someone else's domain pointed at this server. The templates then send
// noindex and skip canonical/OG tags, so search engines never index this
// page under a domain we don't own.
$isKnown  = isset($configs[$domain]);
$settings = array_merge($defaults, $configs[$domain] ?? []);
if ($settings['og_title'] === null) {
	$settings['og_title'] = $settings['title'];
}
```

- [ ] **Step 5: Run the checks again, so the template bugs show**

Run: `"$PHP" _tests/check.php`
Expected: FAIL lines from the old templates. Examples:
- `index.php unknown.example robots is 'index, follow'`
- the canonical pointing to `https://unknown.example/`
- `404.php kiande.com canonical is 'https://kiande.com/'`
- `og:image is 'https://unknown.example'`
- `title is 'Coming Soon | Coming Soon'`
- `background image missing or not cache-busted`

Exit code 1.

- [ ] **Step 6: Create `partials/helpers.php`**

```php
<?php
// =====================================================================
//  partials/helpers.php — small functions the partials share
// =====================================================================
isset($settings) || exit; // opened directly in a browser: show nothing

// e(): escape text for HTML (content and attribute values).
function e($value) {
	return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// asset(): add ?v=<last-modified time> to a local file URL, e.g.
// '/style.css' -> '/style.css?v=1760000000'. Browsers may then cache the
// file for a year (see .htaccess) and still fetch a new copy the moment
// the file changes, because its URL changes with it.
function asset($path) {
	$file = dirname(__DIR__) . $path;
	return is_file($file) ? $path . '?v=' . filemtime($file) : $path;
}
```

- [ ] **Step 7: Create `partials/head.php` (Task 1 version: Bootstrap still loaded, markup as today)**

```php
<?php
// =====================================================================
//  partials/head.php — everything from <!DOCTYPE> to the page wrapper
// ---------------------------------------------------------------------
//  A page includes config.php, sets these, then includes this file:
//    $pageTitle  text after the "|" in <title>, e.g. 'Coming Soon'
//    $robots     content for <meta name="robots">
//    $isHome     true only on index.php (enables canonical + OG tags)
//    $scripts    script URLs for footer.php to load
//  $settings, $domain, $isKnown and $defaults come from config.php.
// =====================================================================
isset($settings) || exit; // opened directly in a browser: show nothing
require_once __DIR__ . '/helpers.php';

// Never let search engines index this page under a domain we don't own.
if (!$isKnown) {
	$robots = 'noindex';
}

// Canonical + Open Graph only belong on the landing page of a known domain.
$showSeo     = $isHome && $isKnown;
$siteUrl     = 'https://' . $domain . '/';
$fullTitle   = $isKnown ? $settings['title'] . ' | ' . $pageTitle : $pageTitle;
$description = $isKnown ? 'D-FAULT PARKED | Coming soon page for ' . $settings['title'] : 'D-FAULT PARKED | Coming soon';

// Only accept image paths shaped like /img/name.ext, because the value is
// printed inside CSS, where HTML escaping doesn't protect anything.
$bgImage = preg_match('#^/img/[\w.-]+$#', $settings['background_image']) ? $settings['background_image'] : '';
?>
<!DOCTYPE html>
<html lang="en" class="h-100" data-bs-theme="dark">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<meta name="description" content="<?= e($description) ?>">
	<meta name="theme-color" content="#0f172a">
	<meta name="robots" content="<?= e($robots) ?>" />
	<title><?= e($fullTitle) ?></title>
<?php if ($showSeo): ?>
	<link rel="canonical" href="<?= e($siteUrl) ?>" />
<?php endif; ?>
	<link rel="icon" href="/favicon.ico" type="image/x-icon">

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/5.3.8/quartz/bootstrap.min.css" integrity="sha512-dnVRkLGpagS9BYaiWREn1h+iXsQukido4lIuMQFNc0ZBI285WEfmpQWf5sjL0yWS4JfrEJ1XUZ0O99zacIPmPA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">

	<link rel="stylesheet" href="<?= e(asset('/style.css')) ?>" id="dfault-css" type="text/css" media="all" />

<?php if ($settings['ga_id'] !== ''): ?>
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e(rawurlencode($settings['ga_id'])) ?>"></script>
	<script>
		window.dataLayer = window.dataLayer || [];

		function gtag() {
			dataLayer.push(arguments);
		}
		gtag('js', new Date());
		gtag('config', <?= json_encode($settings['ga_id']) ?>);
	</script>
<?php endif; ?>
<?php if ($bgImage !== ''): ?>
	<style>
		.bg-image {
			background-image:
				linear-gradient(rgba(0, 0, 0, <?= (float) $settings['overlay_opacity'] ?>),
					rgba(0, 0, 0, <?= (float) $settings['overlay_opacity'] ?>)),
				url('<?= asset($bgImage) ?>') !important;
			opacity: <?= (float) $settings['bg_opacity'] ?>;
		}
	</style>
<?php endif; ?>
<?php if ($showSeo): ?>

	<meta property="og:type" content="website" />
	<meta property="og:url" content="<?= e($siteUrl) ?>" />
	<meta property="og:title" content="<?= e($settings['og_title']) ?>" />
	<meta property="og:description" content="<?= e($settings['og_description']) ?>" />
<?php if ($bgImage !== ''): ?>
	<meta property="og:image" content="<?= e('https://' . $domain . $bgImage) ?>" />
<?php endif; ?>
	<meta name="twitter:card" content="<?= $bgImage !== '' ? 'summary_large_image' : 'summary' ?>" />
	<meta name="twitter:title" content="<?= e($settings['og_title']) ?>" />
	<meta name="twitter:description" content="<?= e($settings['og_description']) ?>" />
<?php if ($bgImage !== ''): ?>
	<meta name="twitter:image" content="<?= e('https://' . $domain . $bgImage) ?>" />
<?php endif; ?>
<?php endif; ?>

</head>

<body class="d-flex h-100">
	<div class="bg-image"></div>
	<div class="container d-flex w-100 h-100 p-1 mx-auto flex-column">
		<div class="mb-auto"> </div>
```

- [ ] **Step 8: Create `partials/footer.php` (Task 1 version)**

```php
<?php
// =====================================================================
//  partials/footer.php — footer, page scripts and closing tags
// =====================================================================
isset($settings) || exit; // opened directly in a browser: show nothing
?>

		<footer class="mt-auto text-center" role="contentinfo">
			<p class="m-0">
				<a href="https://dfault.it/" target="_blank" title="D-FAULT PENDING" class="text-warning">D-FAULT PARKED</a><br>
				<span class="d-none d-lg-inline">Copyright</span> <i class="bi bi-c-circle"></i> 2005&ndash;<?= date('Y') ?><i class="bi bi-dot"></i><a href="/" title="<?= e($settings['title']) ?>" class="text-warning"><?= e($settings['title']) ?></a><br>
				<a href="https://dahlskebank.com/" target="_blank" title="Dahlske Bank" class="text-warning">Powered by dB</a> /
				<a href="https://danieldahl.com/" target="_blank" title="Daniel Dahl" class="text-warning">Fueled by dD</a> /
				<a href="https://dxd.no/" target="_blank" title="deus ex Dahl" class="text-warning">Pinnacle of dxD</a>
			</p>
		</footer>

	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<?php foreach ($scripts as $src): ?>
	<script src="<?= e(asset($src)) ?>"></script>
<?php endforeach; ?>

</body>

</html>
```

- [ ] **Step 9: Rewrite `index.php`**

```php
<?php
// =====================================================================
//  index.php — the landing page every parked domain shows
// =====================================================================
require_once __DIR__ . '/config.php';

$pageTitle = 'Coming Soon';
$robots    = 'index, follow';
$isHome    = true;
$scripts   = ['/dfault.js'];

require __DIR__ . '/partials/head.php';
?>

		<main class="text-center">
			<h1 class="display-1 dd-fw4">Website <span class="d-lg-inline d-block text-warning">Coming Soon</span></h1>
			<blockquote>
				<p class="lead pb-2">Oh, yes. Very soon. <span class="d-lg-inline d-block">They are building it now.</span></p>
			</blockquote>

			<div class="container">
				<div class="row">
					<div class="col-6 col-lg-3">
						<div class="card mb-1">
							<div class="card-body">
								<h1 id="days" class="card-title display-1">--</h1>
							</div>
							<div class="card-footer text-body-secondary">Days</div>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="card mb-1">
							<div class="card-body">
								<h1 id="hours" class="card-title display-1">--</h1>
							</div>
							<div class="card-footer text-body-secondary">Hours</div>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="card mb-1">
							<div class="card-body">
								<h1 id="minutes" class="card-title display-1">--</h1>
							</div>
							<div class="card-footer text-body-secondary">Minutes</div>
						</div>
					</div>
					<div class="col-6 col-lg-3">
						<div class="card mb-1">
							<div class="card-body">
								<h1 id="seconds" class="card-title display-1">--</h1>
							</div>
							<div class="card-footer text-body-secondary">Seconds</div>
						</div>
					</div>
				</div>
			</div>

		</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
```

- [ ] **Step 10: Rewrite `404.php` and `403.php`**

`404.php`:

```php
<?php
// =====================================================================
//  404.php — shown by Apache for missing pages (ErrorDocument 404)
// =====================================================================
require_once __DIR__ . '/config.php';
http_response_code(404);

$pageTitle = '404 Page Not Found';
$robots    = 'noindex';
$isHome    = false;
$scripts   = [];

require __DIR__ . '/partials/head.php';
?>

		<main class="text-center">
			<h1 class="display-1">404<br>Page Not Found</h1>
		</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
```

`403.php`:

```php
<?php
// =====================================================================
//  403.php — shown by Apache for blocked paths (ErrorDocument 403)
// =====================================================================
require_once __DIR__ . '/config.php';
http_response_code(403);

$pageTitle = '403 Access Denied';
$robots    = 'noindex';
$isHome    = false;
$scripts   = [];

require __DIR__ . '/partials/head.php';
?>

		<main class="text-center">
			<h1 class="display-1">403<br>Access Denied</h1>
		</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
```

- [ ] **Step 11: Run the checks; all must pass**

Run: `"$PHP" _tests/check.php`
Expected: `All N checks passed`, exit code 0.

- [ ] **Step 12: Before/after diff**

Run:
```bash
"$PHP" _tests/check.php --dump-only --dump="$SCRATCH/after"
diff -rwB "$SCRATCH/before" "$SCRATCH/after" | grep -E '^[<>]' | sed -E 's/\?v=[0-9]+//g' | sort | uniq -c | sort -rn | head -60
```

**Expected differences, and only these:**
- `?v=<time>` added to `style.css`, `dfault.js` and the background `url()`.
- `opacity: X` gains a `;` on index.
- The spacer `mb-auto d-lg-block` becomes `mb-auto`.
- The footer's domain link becomes `href="/"` with the title as its `title` attribute.
- Error pages lose canonical, all OG/Twitter tags, and the commented-out `<!-- <script src="/dfault.js"></script> -->` line.
- Unknown and empty hosts:
  - robots becomes `noindex`
  - canonical and OG tags disappear
  - the title becomes `Coming Soon`
  - the description becomes `D-FAULT PARKED | Coming soon`
- `kiande.com:443` and `kiande.com.` now render KiAnDe instead of the fallback.
- The GA block is unchanged (empty IDs).

Any other difference is a bug: fix it, then re-run Steps 11–12.

- [ ] **Step 13: Commit and push**

```bash
git add _tests/check.php partials config.php index.php 404.php 403.php
git commit -m "refactor: config defaults, shared partials and page assembly

- config.php: \$defaults + per-domain overrides, host normalisation
  (port, trailing dot, www), \$isKnown
- partials/head.php + footer.php shared by index/404/403
- unknown hosts: noindex, no canonical/OG; no broken og:image
- error pages: no canonical/OG, http_response_code()
- ?v=filemtime cache-busting on css/js/background
- _tests/check.php renders every page for 25 hosts and checks the head

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
git push
```

---

### Task 2: `.htaccess`, `robots.txt` and the Apache probe

**Files:**
- Create: `_tests/probe.sh`, `robots.txt`
- Modify (full rewrite): `.htaccess`

**Interfaces:**
- Consumes: the `404.php`/`403.php` pages from Task 1 (bodies contain "Page Not Found" / "Access Denied"), and `asset()` URLs with `?v=`.
- Produces: `bash _tests/probe.sh` (local) and `bash _tests/probe.sh --prod <domain>` (live, read-only). Exit code 0/1.

- [ ] **Step 1: Write `_tests/probe.sh`**

```bash
#!/usr/bin/env bash
# =====================================================================
#  _tests/probe.sh — check every .htaccess rule with real HTTP requests
# ---------------------------------------------------------------------
#  Local (default): copies the site to a temp folder and serves it with
#  Laragon's Apache + PHP on its own port and config, so Laragon itself
#  is never touched. Then fires curl requests and checks the answers.
#    bash _tests/probe.sh
#
#  Live (after a deploy): read-only requests to the real server. Uses
#  the IP from public DNS, so the Windows hosts file can't interfere.
#    bash _tests/probe.sh --prod kiande.com
#
#  Exit code 0 = everything passed.
# =====================================================================
set -u
APACHE="${APACHE:-E:/vlaragon/bin/apache/httpd-2.4.66-260223-Win64-VS18}"
PHPDIR="${PHPDIR:-E:/vlaragon/bin/php/php-8.3.30-Win32-vs16-x64}"
PORT="${PORT:-18765}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WORK="$(cygpath -m "${TMPDIR:-/tmp}")/parked-probe"
BODY="$WORK/body.html"
PROD_IP=""
FAILS=0

pass() { printf 'ok    %s\n' "$1"; }
fail() { printf 'FAIL  %s\n' "$1"; FAILS=$((FAILS + 1)); }

# req <scheme> <host> <path> [curl args] — saves body to $BODY, prints "status location"
req() {
	local scheme="$1" host="$2" path="$3"; shift 3
	if [ -n "$PROD_IP" ]; then
		local port=443; [ "$scheme" = http ] && port=80
		curl -s -o "$BODY" -w '%{http_code} %{redirect_url}' --max-time 15 \
			--resolve "$host:$port:$PROD_IP" "$@" "$scheme://$host$path"
	else
		curl -s -o "$BODY" -w '%{http_code} %{redirect_url}' --max-time 15 \
			-H "Host: $host" "$@" "http://127.0.0.1:$PORT$path"
	fi
}

# headers <host> <path> [curl args] — prints the response headers
headers() {
	local host="$1" path="$2"; shift 2
	if [ -n "$PROD_IP" ]; then
		curl -s -D - -o /dev/null --max-time 15 --resolve "$host:443:$PROD_IP" "$@" "https://$host$path"
	else
		curl -s -D - -o /dev/null --max-time 15 -H "Host: $host" "$@" "http://127.0.0.1:$PORT$path"
	fi
}

# expect <label> <scheme> <host> <path> <status> [location] [text the body must contain]
expect() {
	local label="$1" scheme="$2" host="$3" path="$4" want="$5" wantloc="${6:-}" wanttext="${7:-}"
	local got loc
	read -r got loc <<< "$(req "$scheme" "$host" "$path")"
	if [ "$got" != "$want" ]; then fail "$label: got $got${loc:+ -> $loc}, expected $want"; return; fi
	if [ -n "$wantloc" ] && [ "$loc" != "$wantloc" ]; then fail "$label: redirects to '$loc', expected '$wantloc'"; return; fi
	if [ -n "$wanttext" ] && ! grep -q "$wanttext" "$BODY"; then fail "$label: body lacks '$wanttext'"; return; fi
	pass "$label"
}

has()   { if grep -qiE "$3" <<< "$2"; then pass "$1"; else fail "$1"; fi; }
lacks() { if grep -qiE "$3" <<< "$2"; then fail "$1"; else pass "$1"; fi; }

start_local() {
	rm -rf "$WORK"; mkdir -p "$WORK/site"
	# Copy the site without .git, then plant a fake .git/HEAD to prove it's blocked.
	tar -C "$ROOT" --exclude=.git -cf - . | tar -C "$WORK/site" -xf -
	mkdir -p "$WORK/site/.git" && echo "ref: refs/heads/main" > "$WORK/site/.git/HEAD"
	cat > "$WORK/httpd.conf" <<EOF
ServerRoot "$APACHE"
Listen 127.0.0.1:$PORT
ServerName localhost
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule authz_host_module modules/mod_authz_host.so
LoadModule dir_module modules/mod_dir.so
LoadModule mime_module modules/mod_mime.so
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
LoadModule expires_module modules/mod_expires.so
LoadModule filter_module modules/mod_filter.so
LoadModule deflate_module modules/mod_deflate.so
LoadModule php_module "$PHPDIR/php8apache2_4.dll"
PHPIniDir "$PHPDIR"
AddHandler application/x-httpd-php .php
TypesConfig conf/mime.types
PidFile "$WORK/httpd.pid"
ErrorLog "$WORK/error.log"
DocumentRoot "$WORK/site"
<Directory "$WORK/site">
	AllowOverride All
	Require all granted
</Directory>
DirectoryIndex index.php
EOF
	"$APACHE/bin/httpd.exe" -f "$WORK/httpd.conf" &
	if ! curl -s -o /dev/null --retry 30 --retry-connrefused --retry-delay 1 "http://127.0.0.1:$PORT/"; then
		echo "Apache did not start; see $WORK/error.log"; exit 2
	fi
}

stop_local() {
	[ -f "$WORK/httpd.pid" ] && taskkill //F //T //PID "$(cat "$WORK/httpd.pid")" > /dev/null 2>&1
}

if [ "${1:-}" = "--prod" ]; then
	D="${2:?usage: probe.sh --prod <domain>}"
	PROD_IP="$(nslookup -type=A "$D" 1.1.1.1 2>/dev/null | awk '/^Name:/{f=1} f&&/^Address/{print $2; exit}')"
	[ -n "$PROD_IP" ] || { echo "Could not resolve $D"; exit 2; }
	mkdir -p "$WORK"
	SELF="https://$D"
	echo "Live checks against $D ($PROD_IP)"
	expect "http -> https (host level)" http "$D" / 301 "https://$D/"
else
	D="kiande.com"
	SELF="http://$D"
	start_local
	trap stop_local EXIT
fi
IMG="$(cd "$ROOT/img" && ls | head -1)"

# Pages
expect "home page"               https "$D" /                200 "" "Coming Soon"
expect "/index.php -> /"         https "$D" /index.php       301 "$SELF/"
expect "robots.txt"              https "$D" /robots.txt      200 "" "User-agent"
expect "missing page"            https "$D" /nope            404 "" "Page Not Found"
expect "missing folder"          https "$D" /nope/           404 "" "Page Not Found"
expect "missing .php"            https "$D" /nope.php        404 "" "Page Not Found"
expect "bot probe wp-login"      https "$D" /wp-login.php    404 "" "Page Not Found"
expect "bot probe xmlrpc"        https "$D" /xmlrpc.php      404 "" "Page Not Found"

# www -> bare domain, path and query kept
expect "www -> bare"             http "www.$D" /             301 "https://$D/"
expect "www keeps path+query"    http "www.$D" "/a/b?c=1"    301 "https://$D/a/b?c=1"

# Blocked paths
expect "config.php blocked"      https "$D" /config.php      403 "" "Access Denied"
expect "partials/ blocked"       https "$D" /partials/       403 "" "Access Denied"
expect "partial file blocked"    https "$D" /partials/head.php 403 "" "Access Denied"
expect "_docs blocked"           https "$D" /_docs/          403 "" "Access Denied"
expect "_originals blocked"      https "$D" /_originals/     403 "" "Access Denied"
expect "_tests blocked"          https "$D" /_tests/check.php 403 "" "Access Denied"
expect ".git blocked"            https "$D" /.git/HEAD       403 "" "Access Denied"
expect ".gitignore blocked"      https "$D" /.gitignore      403 "" "Access Denied"
expect ".env blocked"            https "$D" /.env            403 "" "Access Denied"
expect ".well-known not blocked" https "$D" /.well-known/x   404 "" "Page Not Found"

# Security headers on 200, 403 and 404
for path in / /config.php /nope; do
	H="$(headers "$D" "$path")"
	has   "X-Frame-Options on $path"        "$H" '^X-Frame-Options: SAMEORIGIN'
	has   "X-Content-Type-Options on $path" "$H" '^X-Content-Type-Options: nosniff'
	has   "Referrer-Policy on $path"        "$H" '^Referrer-Policy: strict-origin-when-cross-origin'
	has   "Permissions-Policy on $path"     "$H" '^Permissions-Policy: camera=\(\), microphone=\(\), geolocation=\(\)'
	lacks "no X-Powered-By on $path"        "$H" '^X-Powered-By:'
done

# Caching + compression
has "HTML not cached"      "$(headers "$D" /)"            'Cache-Control: max-age=0'
has "CSS cached 1 year"    "$(headers "$D" /style.css)"   'Cache-Control: max-age=31536000'
has "image cached 1 year"  "$(headers "$D" "/img/$IMG")"  'Cache-Control: max-age=31536000'
has "HTML gzipped"         "$(headers "$D" / -H 'Accept-Encoding: gzip')"          'Content-Encoding: gzip'
has "CSS gzipped"          "$(headers "$D" /style.css -H 'Accept-Encoding: gzip')" 'Content-Encoding: gzip'

echo
if [ "$FAILS" -eq 0 ]; then echo "All probe checks passed"; else echo "$FAILS probe check(s) failed"; fi
[ "$FAILS" -eq 0 ]
```

(In local mode the `https`/`http` scheme argument is ignored. Every request goes to `http://127.0.0.1:18765` with a Host header.)

- [ ] **Step 2: Run the probe against the current `.htaccess` and watch it fail**

Run: `bash _tests/probe.sh`
Expected FAIL lines include:
- `/index.php -> /: got 301 -> http://kiande.com/index/`
- `robots.txt: got 404`
- `missing .php: got 301`
- `www -> bare: got 200`
- `partials/ blocked: got 404`
- `_docs blocked`
- `.git blocked: got 200`
- `Permissions-Policy on /`
- `no X-Powered-By on /`
- the cache and gzip lines

Exit code 1.

- [ ] **Step 3: Rewrite `.htaccess`**

```apache
# =====================================================================
#  .htaccess — D-FAULT PARKED
# ---------------------------------------------------------------------
#  Apache reads this file on every request. Sections:
#  1. Basics   2. Blocked paths   3. Redirects   4. Security headers
#  5. Compression   6. Caching   7. Error pages
#
#  HTTP -> HTTPS is not here: the host's nginx already redirects every
#  http:// request to https:// before Apache sees it.
# =====================================================================

# ---------------------------------------------------------------------
# 1. BASICS
# ---------------------------------------------------------------------
# -Indexes: never list a folder's files.
# -MultiViews: no guessing, e.g. answering /404 with 404.php on its own.
Options -Indexes -MultiViews
AddDefaultCharset UTF-8
RewriteEngine On

# ---------------------------------------------------------------------
# 2. BLOCKED PATHS (answered with the 403 page)
# ---------------------------------------------------------------------
# config.php and partials/ only work when a page includes them.
RewriteRule ^(config\.php|partials/) - [F]
# Top-level names starting with "_" are private: _docs/, _originals/, _tests/.
RewriteRule ^_ - [F]
# Anything starting with a dot (.git/, .gitignore, .env ...), except
# .well-known/, which certificate checks and security.txt use.
RewriteRule (^|/)\.(?!well-known/) - [F]

# ---------------------------------------------------------------------
# 3. REDIRECTS
# ---------------------------------------------------------------------
# www.example.com/anything -> https://example.com/anything. One rule
# covers every parked domain: %1 is whatever followed "www.", minus
# any :port.
RewriteCond %{HTTP_HOST} ^www\.([^:]+)(:\d+)?$ [NC]
RewriteRule ^ https://%1%{REQUEST_URI} [R=301,L,NE]

# /index.php -> / so the page has one address. The REDIRECT_STATUS test
# skips Apache's internal passes (like loading an error page); only the
# visitor's own request may trigger the redirect.
RewriteCond %{ENV:REDIRECT_STATUS} ^$
RewriteRule ^index\.php$ / [R=301,L]

# ---------------------------------------------------------------------
# 4. SECURITY HEADERS
# ---------------------------------------------------------------------
# "always" also adds them to error pages (403/404).
<IfModule mod_headers.c>
	Header always set X-Frame-Options "SAMEORIGIN"
	Header always set X-Content-Type-Options "nosniff"
	Header always set Referrer-Policy "strict-origin-when-cross-origin"
	Header always set Permissions-Policy "camera=(), microphone=(), geolocation=()"
	# Don't advertise the PHP version.
	Header unset X-Powered-By
	Header always unset X-Powered-By
</IfModule>

# ---------------------------------------------------------------------
# 5. COMPRESSION (gzip text files; JPGs are already compressed)
# ---------------------------------------------------------------------
<IfModule mod_deflate.c>
	AddOutputFilterByType DEFLATE text/html text/css text/plain text/javascript application/javascript image/svg+xml image/x-icon image/vnd.microsoft.icon
</IfModule>

# ---------------------------------------------------------------------
# 6. CACHING
# ---------------------------------------------------------------------
# CSS, JS and background images are linked with ?v=<modified time>
# (asset() in partials/helpers.php), so a changed file gets a new URL.
# That makes a 1-year cache safe. HTML is never cached, so config and
# copy changes show up immediately.
<IfModule mod_expires.c>
	ExpiresActive On
	ExpiresDefault "access plus 0 seconds"
	ExpiresByType text/html "access plus 0 seconds"
	ExpiresByType text/css "access plus 1 year"
	ExpiresByType text/javascript "access plus 1 year"
	ExpiresByType application/javascript "access plus 1 year"
	ExpiresByType image/jpeg "access plus 1 year"
	ExpiresByType image/png "access plus 1 year"
	ExpiresByType image/x-icon "access plus 1 month"
	ExpiresByType image/vnd.microsoft.icon "access plus 1 month"
</IfModule>

# ---------------------------------------------------------------------
# 7. ERROR PAGES
# ---------------------------------------------------------------------
ErrorDocument 404 /404.php
ErrorDocument 403 /403.php
```

- [ ] **Step 4: Create `robots.txt`**

```
User-agent: *
Allow: /
```

- [ ] **Step 5: Run the probe; all must pass**

Run: `bash _tests/probe.sh`
Expected: every line `ok`, then `All probe checks passed`, exit code 0.

If `no X-Powered-By` still fails with mod_php: confirm in `$WORK/error.log` that mod_headers loaded, and keep both the `Header unset` and the `Header always unset` lines. Production already hides it.

- [ ] **Step 6: Re-run the page checks (nothing should regress)**

Run: `"$PHP" _tests/check.php`
Expected: `All N checks passed`.

- [ ] **Step 7: Commit and push**

```bash
git add .htaccess robots.txt _tests/probe.sh
git commit -m "feat: rewrite .htaccess, add robots.txt and Apache probe

- drop the broken clean-URL rules; /index.php -> /, www -> bare domain
- block config.php, partials/, _* folders and dot-paths (.git etc.)
- Permissions-Policy, unset X-Powered-By, gzip, 1-year asset cache
- _tests/probe.sh checks every rule on a throwaway Apache, or
  read-only against production with --prod <domain>

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
git push
```

---

### Task 3: JPG backgrounds and per-domain accent colours

**Files:**
- Move (`git mv`): 14 PNGs, 5 high-quality JPGs and the 2 `*_unused.jpg` from `img/` to `_originals/img/`
- Create: 19 JPGs in `img/`; scratchpad scripts `convert_images.py`, `accents.py`, `apply_accents.py` (not committed)
- Modify: `config.php` (`.png` → `.jpg` paths; add `accent` per domain), `partials/helpers.php` (add `color_or()`), `partials/head.php` (accent-driven `theme-color` and `--accent`), `_tests/check.php` (new checks)

**Interfaces:**
- Consumes: `$defaults['accent']`, and `$configs[...]['background_image']` from Task 1.
- Produces:
  - `color_or($value, $fallback): string`, which returns a lowercase `#rrggbb` or `$fallback`
  - `$accent` in `head.php`
  - an inline `:root { --accent: #rrggbb; }` on every page, which Task 4's CSS consumes

- [ ] **Step 1: Add the image and accent checks to `_tests/check.php`**

Insert after the line `$url   = "https://$key/";`:

```php
		$wantAccent = strtolower($s['accent']);
```

Insert after the existing `if ($img) { … cache-busted … }` block:

```php
		if ($img) {
			check(is_file($root . $img), "$label background image file missing: $img");
		}
		check($i['theme'] === $wantAccent, "$label theme-color is '{$i['theme']}', expected $wantAccent");
		check(strpos((string) $i['style'], "--accent: $wantAccent;") !== false, "$label --accent is not $wantAccent");
```

Insert before `// 3. Literal spot checks`:

```php
// Accent validation (Review Focus 2): a bad value must never reach the CSS.
if (!$dumpOnly) {
	require_once $root . '/partials/helpers.php';
	if (function_exists('color_or')) {
		check(color_or('#A1B2C3', '#ffc107') === '#a1b2c3', 'color_or keeps a valid colour, lower-cased');
		check(color_or('#ffc10', '#ffc107') === '#ffc107', 'color_or rejects a 5-digit typo');
		check(color_or('red', '#ffc107') === '#ffc107', 'color_or rejects colour names');
		check(color_or('#fff;}body{display:none', '#ffc107') === '#ffc107', 'color_or rejects CSS injection');
		check(color_or(null, '#ffc107') === '#ffc107', 'color_or handles a missing value');
	} else {
		check(false, 'color_or() is not defined in partials/helpers.php');
	}
}
```

- [ ] **Step 2: Run the checks and watch them fail**

Run: `"$PHP" _tests/check.php`
Expected:
- `color_or() is not defined`
- every host fails `theme-color is '#0f172a', expected #ffc107`
- every host fails `--accent is not #ffc107`

Exit code 1.

- [ ] **Step 3: Add `color_or()` to `partials/helpers.php`** (append at the end)

```php

// color_or(): return $value as a lowercase 6-digit hex colour ('#a1b2c3'),
// or $fallback if it isn't one. The result is printed inside CSS, so
// anything else (typos, colour names, injected CSS) is rejected.
function color_or($value, $fallback) {
	$value = strtolower(trim((string) $value));
	return preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : $fallback;
}
```

- [ ] **Step 4: Wire the accent into `partials/head.php`**

After the `$bgImage = …` line add:

```php
$accent  = color_or($settings['accent'], $defaults['accent']);
```

Replace `<meta name="theme-color" content="#0f172a">` with:

```php
	<!-- Tints Chrome's toolbar on Android in this domain's colour -->
	<meta name="theme-color" content="<?= e($accent) ?>">
```

Replace the whole `<?php if ($bgImage !== ''): ?> <style> … </style> <?php endif; ?>` block with:

```php
	<style>
		:root { --accent: <?= $accent ?>; }
<?php if ($bgImage !== ''): ?>
		.bg-image {
			background-image:
				linear-gradient(rgba(0, 0, 0, <?= (float) $settings['overlay_opacity'] ?>),
					rgba(0, 0, 0, <?= (float) $settings['overlay_opacity'] ?>)),
				url('<?= asset($bgImage) ?>') !important;
			opacity: <?= (float) $settings['bg_opacity'] ?>;
		}
<?php endif; ?>
	</style>
```

- [ ] **Step 5: Run the checks; accent checks pass (every domain still on the `#ffc107` default)**

Run: `"$PHP" _tests/check.php`
Expected: `All N checks passed`.

- [ ] **Step 6: Move the originals out of `img/`**

```bash
mkdir -p _originals/img
git mv img/cybabes.png img/cybocop.png img/dahlskebank.png img/danieldahl.png \
       img/darkdictator.png img/iliketomovie.png img/iliveagain.png img/kennywang.png \
       img/kiande.png img/killingheat.png img/marxisthunter.png img/meatfetish.png \
       img/meloslave.png img/reservedekk.png \
       img/arksanity.jpg img/brutalina.jpg img/compoundcomplex.jpg \
       img/fatalityfacilitator.jpg img/zombiefetish.jpg \
       img/cybabes_unused.jpg img/darkdictator_unused.jpg \
       _originals/img/
sed -i -E "s#(/img/[a-z]+)\.png'#\1.jpg'#" config.php
grep -c "\.png'" config.php
```
Expected: the last command prints `0`. Only `img/meatsex.jpg` remains in `img/`.

- [ ] **Step 7: Run the checks and watch the file check fail**

Run: `"$PHP" _tests/check.php`
Expected: `background image file missing: /img/<name>.jpg` for the 19 converted names, exit code 1.

- [ ] **Step 8: Write and run `$SCRATCH/convert_images.py`**

```python
# convert_images.py — originals in _originals/img/ -> q90 JPGs in img/
# Run from the project root:  python "$SCRATCH/convert_images.py"
import os, sys
from PIL import Image, ImageChops, ImageStat

ROOT = os.getcwd()
SRC = os.path.join(ROOT, "_originals", "img")
DST = os.path.join(ROOT, "img")
JOBS = [
    "cybabes.png", "cybocop.png", "dahlskebank.png", "danieldahl.png", "darkdictator.png",
    "iliketomovie.png", "iliveagain.png", "kennywang.png", "kiande.png", "killingheat.png",
    "marxisthunter.png", "meatfetish.png", "meloslave.png", "reservedekk.png",
    "arksanity.jpg", "brutalina.jpg", "compoundcomplex.jpg", "fatalityfacilitator.jpg", "zombiefetish.jpg",
]
problems = 0
total_before = total_after = 0
for name in JOBS:
    src = os.path.join(SRC, name)
    im = Image.open(src)
    # Review Focus 3: transparent pixels would turn black in a JPG.
    if im.mode in ("RGBA", "LA") or (im.mode == "P" and "transparency" in im.info):
        if im.convert("RGBA").getchannel("A").getextrema()[0] < 255:
            print(f"STOP  {name}: has transparent pixels")
            problems += 1
            continue
    icc = im.info.get("icc_profile")
    rgb = im.convert("RGB")
    out = os.path.join(DST, os.path.splitext(name)[0] + ".jpg")
    extra = {"icc_profile": icc} if icc else {}
    rgb.save(out, "JPEG", quality=90, optimize=True, progressive=True, **extra)
    back = Image.open(out).convert("RGB")
    diff = max(ImageStat.Stat(ImageChops.difference(rgb, back)).mean)
    ok = back.size == rgb.size and diff < 3.0
    b, a = os.path.getsize(src) // 1024, os.path.getsize(out) // 1024
    total_before += b
    total_after += a
    print(f"{'ok  ' if ok else 'FAIL'}  {name:26s} {b:5d} KB -> {a:4d} KB  {back.size[0]}x{back.size[1]}  mean diff {diff:.2f}  icc={'yes' if icc else 'no'}")
    problems += 0 if ok else 1
print(f"total {total_before} KB -> {total_after} KB")
sys.exit(1 if problems else 0)
```

Run: `python "$SCRATCH/convert_images.py"`
Expected: 19 `ok` lines and a PNG total of roughly 12 MB → 3.8 MB, exit code 0. If any line says `STOP` or `FAIL`, halt and report it to Daniel.

- [ ] **Step 9: Run the checks; all must pass**

Run: `"$PHP" _tests/check.php`
Expected: `All N checks passed`.

- [ ] **Step 10: Commit the image change (accents come after approval)**

```bash
git add -A img _originals config.php partials/helpers.php partials/head.php _tests/check.php
git commit -m "feat: JPG backgrounds at q90, accent plumbing

- 14 PNGs + 5 q100 JPGs re-encoded to q90 JPG (same pixels, ICC kept)
- originals and *_unused.jpg moved to _originals/img/ (blocked)
- color_or() validates accents; theme-color + --accent come from config

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
git push
```

- [ ] **Step 11: Dump the config to JSON for the accent script**

```bash
"$PHP" -r '$_SERVER["HTTP_HOST"]=""; include "config.php"; echo json_encode(["defaults"=>$defaults,"configs"=>$configs]);' > "$SCRATCH/config.json"
```

- [ ] **Step 12: Write and run `$SCRATCH/accents.py`**

```python
# accents.py — suggest one accent per domain from its background image,
# lightened until readable where it's used. Writes accents.json and
# swatches.html next to this script. Run from the project root.
import base64, colorsys, io, json, os
from PIL import Image, ImageFilter

ROOT = os.getcwd()
HERE = os.path.dirname(os.path.abspath(__file__))
W, H = 1280, 800                      # simulated desktop viewport
DEFAULT = (0xFF, 0xC1, 0x07)
cfg = json.load(open(os.path.join(HERE, "config.json"), encoding="utf-8"))

def lum(c):  # WCAG relative luminance, c = (r, g, b) 0..255
    def ch(v):
        v /= 255
        return v / 12.92 if v <= 0.04045 else ((v + 0.055) / 1.055) ** 2.4
    return 0.2126 * ch(c[0]) + 0.7152 * ch(c[1]) + 0.0722 * ch(c[2])

def gradient():  # Quartz body gradient: 90deg #33b7e2 -> #5e62b0 -> #dc307c
    stops = [(0x33, 0xB7, 0xE2), (0x5E, 0x62, 0xB0), (0xDC, 0x30, 0x7C)]
    row = Image.new("RGB", (W, 1))
    for x in range(W):
        t = x / (W - 1) * 2
        i = min(int(t), 1)
        f = t - i
        row.putpixel((x, 0), tuple(round(stops[i][k] + (stops[i + 1][k] - stops[i][k]) * f) for k in range(3)))
    return row.resize((W, H))

def cover(im):  # background-size: cover; background-position: center
    s = max(W / im.width, H / im.height)
    im = im.resize((round(im.width * s), round(im.height * s)), Image.LANCZOS)
    l, t = (im.width - W) // 2, (im.height - H) // 2
    return im.crop((l, t, l + W, t + H))

def backdrop(c):  # what the browser paints behind the text
    base = gradient()
    if not c["background_image"]:
        return base
    im = cover(Image.open(os.path.join(ROOT, c["background_image"].lstrip("/"))).convert("RGB"))
    im = Image.blend(im, Image.new("RGB", (W, H)), float(c["overlay_opacity"]))  # black overlay
    return Image.blend(base, im, float(c["bg_opacity"]))                          # layer opacity

def l90(img, box):  # 90th-percentile luminance in a region = realistic worst case
    vals = sorted(lum(p) for p in img.crop(box).resize((80, 40)).getdata())
    return vals[int(len(vals) * 0.9)]

def candidate(c):  # most prominent saturated hue in the image, made vivid
    if not c["background_image"]:
        return DEFAULT
    im = Image.open(os.path.join(ROOT, c["background_image"].lstrip("/"))).convert("RGB")
    im.thumbnail((160, 160))
    bins = [[0.0, 0.0, 0.0, 0.0] for _ in range(36)]
    for r, g, b in im.getdata():
        h, s, v = colorsys.rgb_to_hsv(r / 255, g / 255, b / 255)
        if s < 0.35 or v < 0.25:
            continue
        w = s * v
        bn = bins[int(h * 36) % 36]
        bn[0] += w; bn[1] += r * w; bn[2] += g * w; bn[3] += b * w
    best = max(bins, key=lambda bn: bn[0])
    if best[0] == 0:
        return DEFAULT
    h, l, s = colorsys.rgb_to_hls(best[1] / best[0] / 255, best[2] / best[0] / 255, best[3] / best[0] / 255)
    r, g, b = colorsys.hls_to_rgb(h, max(l, 0.55), max(s, 0.75))
    return (round(r * 255), round(g * 255), round(b * 255))

def tune(c, needs):  # lighten in HSL until every (backdrop L, min ratio) passes
    h, l, s = colorsys.rgb_to_hls(*(v / 255 for v in c))
    while True:
        rgb = tuple(round(v * 255) for v in colorsys.hls_to_rgb(h, l, s))
        ratios = [(lum(rgb) + 0.05) / (lb + 0.05) for lb, _ in needs]
        if all(r >= m for r, (_, m) in zip(ratios, needs)):
            return rgb, ratios, True
        if l >= 0.97:
            return rgb, ratios, False
        l = min(0.97, l + 0.01)

hexc = lambda c: "#%02x%02x%02x" % c
rows, result = [], {}
for domain, entry in cfg["configs"].items():
    c = {**cfg["defaults"], **entry}
    bd = backdrop(c)
    head_box = (int(W * .15), int(H * .30), int(W * .85), int(H * .45))
    bar_box = ((W - 576) // 2, int(H * .55), (W + 576) // 2, int(H * .68))
    foot_box = (int(W * .30), int(H * .88), int(W * .70), int(H * .97))
    glass = Image.blend(bd.crop(bar_box).filter(ImageFilter.GaussianBlur(5)),
                        Image.new("RGB", (bar_box[2] - bar_box[0], bar_box[3] - bar_box[1]), (255, 255, 255)), 0.25)
    needs = [(l90(bd, head_box), 3.0),
             (l90(glass, (0, 0, glass.width, glass.height)), 3.0),
             (l90(bd, foot_box), 4.5)]
    cand = candidate(c)
    acc, ratios, ok = tune(cand, needs)
    result[domain] = hexc(acc)
    thumb = bd.resize((320, 200))
    buf = io.BytesIO(); thumb.save(buf, "JPEG", quality=80)
    rows.append((domain, hexc(cand), hexc(acc), ratios, ok, base64.b64encode(buf.getvalue()).decode()))

json.dump(result, open(os.path.join(HERE, "accents.json"), "w"), indent=1)

html = ["<!doctype html><meta charset=utf-8><title>Accent swatches</title><style>",
        "body{background:#111;color:#ddd;font:14px system-ui;margin:24px}",
        ".r{display:flex;gap:16px;align-items:center;margin:0 0 14px}",
        ".t{position:relative;width:320px;height:200px;border-radius:6px;overflow:hidden;flex:none}",
        ".t img{position:absolute;inset:0}",
        ".h{position:absolute;top:58px;width:100%;text-align:center;font-size:26px;text-shadow:1px 2px 3px #0008}",
        ".g{position:absolute;top:112px;left:70px;width:180px;height:30px;border-radius:5px;display:flex;justify-content:space-around;align-items:center;",
        "background:linear-gradient(125deg,#fff4,#fff3 70%);backdrop-filter:blur(5px);font-size:20px;font-weight:300}",
        ".f{position:absolute;bottom:8px;width:100%;text-align:center;font-size:11px;font-weight:600}",
        ".s{width:46px;height:46px;border-radius:6px;display:inline-block;vertical-align:middle}",
        ".bad{color:#f66}</style><h1>Accent swatches</h1>",
        "<p>Ratios: headline (needs 3.0) / countdown on glass (3.0) / footer links (4.5).</p>"]
for d, cand, acc, ratios, ok, b64 in rows:
    html.append(
        f"<div class=r><div class=t><img src='data:image/jpeg;base64,{b64}'>"
        f"<div class=h style='color:{acc}'>Coming Soon</div>"
        f"<div class=g style='color:{acc}'><span>142</span><span>07</span><span>33</span></div>"
        f"<div class=f style='color:{acc}'>Powered by dB / Fueled by dD</div></div>"
        f"<div><b>{d}</b><br>picked <span class=s style='background:{cand}'></span> {cand} "
        f"→ final <span class=s style='background:{acc}'></span> <b>{acc}</b><br>"
        f"<span class='{'' if ok else 'bad'}'>{' / '.join(f'{r:.1f}' for r in ratios)}{'' if ok else '  — cannot reach target, needs a decision'}</span></div></div>")
open(os.path.join(HERE, "swatches.html"), "w", encoding="utf-8").write("\n".join(html))
print("\n".join(f"{d:26s} {acc}  {' / '.join(f'{r:.1f}' for r in ratios)}  {'ok' if ok else 'NEEDS DECISION'}" for d, _, acc, ratios, ok, _ in rows))
```

Run: `python "$SCRATCH/accents.py"`
Expected: 20 lines, one per domain (`domain  #rrggbb  head / glass / footer  ok`), plus `accents.json` and `swatches.html` written in `$SCRATCH`.

- [ ] **Step 13: GATE — Daniel approves the swatches**

Give Daniel the full path to `$SCRATCH/swatches.html` to open in Chrome, along with the contrast table. Point out any `NEEDS DECISION` rows. Wait for his approval, or his changed hex values, and edit `accents.json` to match. **Do not continue until he approves.**

- [ ] **Step 14: Write and run `$SCRATCH/apply_accents.py`**

```python
# apply_accents.py — add 'accent' => '#rrggbb' to each domain in config.php,
# on the line after its background_image. Run from the project root.
import json, os, re
HERE = os.path.dirname(os.path.abspath(__file__))
acc = json.load(open(os.path.join(HERE, "accents.json"), encoding="utf-8"))
path = "config.php"
src = open(path, encoding="utf-8", newline="").read()
for domain, hexval in acc.items():
    assert re.fullmatch(r"#[0-9a-f]{6}", hexval), (domain, hexval)
    pat = re.compile(r"(\t'" + re.escape(domain) + r"' => \[\r?\n(?:\t\t[^\n]*\n)*?\t\t'background_image'\t=> '[^']*',(\r?\n))")
    src, n = pat.subn(lambda m: m.group(1) + "\t\t'accent'\t\t\t=> '" + hexval + "'," + m.group(2), src, count=1)
    assert n == 1, f"no background_image line found for {domain}"
open(path, "w", encoding="utf-8", newline="").write(src)
print(f"added {len(acc)} accents")
```

Run: `python "$SCRATCH/apply_accents.py"`, then `grep -c "'accent'" config.php`
Expected: `added 20 accents`, then `21` (20 domains + the default).

- [ ] **Step 15: Run the checks; all must pass (`theme-color`/`--accent` now match each domain)**

Run: `"$PHP" _tests/check.php`
Expected: `All N checks passed`.

- [ ] **Step 16: Commit and push**

```bash
git add config.php
git commit -m "feat: per-domain accent colours

Sampled from each background image, lightened until they pass WCAG
contrast (3:1 headline/countdown, 4.5:1 footer links), approved by
Daniel from the swatch sheet.

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
git push
```

---

### Task 4: Drop Bootstrap — new markup, `style.css`, countdown bar, `dfault.js`

**Files:**
- Create: `_tests/countdown.test.js`
- Modify (full rewrite): `partials/head.php`, `partials/footer.php`, `index.php`, `404.php`, `403.php`, `style.css`, `dfault.js`
- Modify: `_tests/check.php` (structure checks)

**Interfaces:**
- Consumes:
  - `$accent`, `color_or()`, `asset()` and `e()` (Tasks 1 and 3)
  - the page contract: `$pageTitle`, `$robots`, `$isHome`, `$scripts`
- Produces:
  - CSS custom properties `--accent`, `--bg-image`, `--bg-opacity` and `--overlay-opacity`, set inline by `head.php` and read by `style.css`
  - DOM ids `days`, `hours`, `minutes` and `seconds`, read by `dfault.js`

- [ ] **Step 1: Write `_tests/countdown.test.js`**

```js
// _tests/countdown.test.js — checks dfault.js without a browser.
// Run from the project root:  node _tests/countdown.test.js
const fs = require("fs");
const path = require("path");
const vm = require("vm");

const src = fs.readFileSync(path.join(__dirname, "..", "dfault.js"), "utf8");
let failures = 0;
function check(ok, label) {
	if (!ok) {
		failures++;
		console.log("FAIL  " + label);
	}
}

// Run dfault.js in a sandbox with a fake document.
function run(withCountdown) {
	const els = {};
	for (const id of ["days", "hours", "minutes", "seconds"]) els[id] = { textContent: "--" };
	const ctx = vm.createContext({
		document: { getElementById: (id) => (withCountdown ? els[id] || null : null) },
		setInterval: () => 0,
		Date, Math, String, Number,
	});
	let error = null;
	try {
		vm.runInContext(src, ctx);
	} catch (e) {
		error = e;
	}
	return { els, ctx, error };
}

// 1. A page with the countdown (index.php)
const a = run(true);
check(a.error === null, "throws on the landing page: " + a.error);
const days = Number(a.els.days.textContent);
check(days >= 29 && days <= 180, "days outside 29–180: " + a.els.days.textContent);
for (const id of ["hours", "minutes", "seconds"]) {
	check(/^\d\d$/.test(a.els[id].textContent), id + " is not two digits: " + a.els[id].textContent);
}
check(vm.runInContext("typeof minDays", a.ctx) === "undefined", "leaks minDays into the page's global scope");

// 2. A page without it (404/403, or a future page)
const b = run(false);
check(b.error === null, "throws when #days is missing: " + b.error);

console.log(failures ? failures + " countdown check(s) failed" : "All countdown checks passed");
process.exit(failures ? 1 : 0);
```

- [ ] **Step 2: Run it against the current `dfault.js` and watch it fail**

Run: `node _tests/countdown.test.js`
Expected: `FAIL  leaks minDays into the page's global scope` and `FAIL  throws when #days is missing: TypeError…`, exit code 1.

- [ ] **Step 3: Add the structure checks to `_tests/check.php`**

Insert after the `--accent` check line (inside the per-host loop):

```php
		check($i['h1_count'] === 1, "$label has {$i['h1_count']} <h1> elements, expected 1");
		check(stripos($html, 'bootstrap') === false && stripos($html, 'bootswatch') === false, "$label still references Bootstrap");
		$hasCountdown = strpos($html, 'id="days"') !== false;
		$hasScript = (bool) preg_match('#<script src="/dfault\.js\?v=\d+"></script>#', $html);
		check($hasCountdown === $p['home'], "$label countdown markup present: " . var_export($hasCountdown, true));
		check($hasScript === $p['home'], "$label dfault.js loaded: " . var_export($hasScript, true));
```

- [ ] **Step 4: Run the page checks and watch them fail**

Run: `"$PHP" _tests/check.php`
Expected:
- `index.php … has 5 <h1> elements`
- `still references Bootstrap` on every page

Exit code 1.

- [ ] **Step 5: Rewrite `partials/head.php` (final)**

```php
<?php
// =====================================================================
//  partials/head.php — everything from <!DOCTYPE> to the page wrapper
// ---------------------------------------------------------------------
//  A page includes config.php, sets these, then includes this file:
//    $pageTitle  text after the "|" in <title>, e.g. 'Coming Soon'
//    $robots     content for <meta name="robots">
//    $isHome     true only on index.php (enables canonical + OG tags)
//    $scripts    script URLs for footer.php to load
//  $settings, $domain, $isKnown and $defaults come from config.php.
// =====================================================================
isset($settings) || exit; // opened directly in a browser: show nothing
require_once __DIR__ . '/helpers.php';

// Never let search engines index this page under a domain we don't own.
if (!$isKnown) {
	$robots = 'noindex';
}

// Canonical + Open Graph only belong on the landing page of a known domain.
$showSeo     = $isHome && $isKnown;
$siteUrl     = 'https://' . $domain . '/';
$fullTitle   = $isKnown ? $settings['title'] . ' | ' . $pageTitle : $pageTitle;
$description = $isKnown ? 'D-FAULT PARKED | Coming soon page for ' . $settings['title'] : 'D-FAULT PARKED | Coming soon';

// Values printed inside CSS are validated, because HTML escaping doesn't
// protect anything there: image paths must look like /img/name.ext, and
// the accent must be a 6-digit hex colour.
$bgImage = preg_match('#^/img/[\w.-]+$#', $settings['background_image']) ? $settings['background_image'] : '';
$accent  = color_or($settings['accent'], $defaults['accent']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= e($fullTitle) ?></title>
	<meta name="description" content="<?= e($description) ?>">
	<meta name="robots" content="<?= e($robots) ?>">
	<!-- Tints Chrome's toolbar on Android in this domain's colour -->
	<meta name="theme-color" content="<?= e($accent) ?>">
<?php if ($showSeo): ?>
	<link rel="canonical" href="<?= e($siteUrl) ?>">
<?php endif; ?>
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" href="<?= e(asset('/style.css')) ?>">

	<!-- This domain's values; style.css uses them via var(--name) -->
	<style>
		:root {
			--accent: <?= $accent ?>;
<?php if ($bgImage !== ''): ?>
			--bg-image: url('<?= asset($bgImage) ?>');
			--bg-opacity: <?= (float) $settings['bg_opacity'] ?>;
			--overlay-opacity: <?= (float) $settings['overlay_opacity'] ?>;
<?php endif; ?>
		}
	</style>
<?php if ($settings['ga_id'] !== ''): ?>

	<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e(rawurlencode($settings['ga_id'])) ?>"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag() { dataLayer.push(arguments); }
		gtag('js', new Date());
		gtag('config', <?= json_encode($settings['ga_id']) ?>);
	</script>
<?php endif; ?>
<?php if ($showSeo): ?>

	<meta property="og:type" content="website">
	<meta property="og:url" content="<?= e($siteUrl) ?>">
	<meta property="og:title" content="<?= e($settings['og_title']) ?>">
	<meta property="og:description" content="<?= e($settings['og_description']) ?>">
<?php if ($bgImage !== ''): ?>
	<meta property="og:image" content="<?= e('https://' . $domain . $bgImage) ?>">
<?php endif; ?>
	<meta name="twitter:card" content="<?= $bgImage !== '' ? 'summary_large_image' : 'summary' ?>">
	<meta name="twitter:title" content="<?= e($settings['og_title']) ?>">
	<meta name="twitter:description" content="<?= e($settings['og_description']) ?>">
<?php if ($bgImage !== ''): ?>
	<meta name="twitter:image" content="<?= e('https://' . $domain . $bgImage) ?>">
<?php endif; ?>
<?php endif; ?>
</head>

<body>
	<div class="bg-image" aria-hidden="true"></div>
	<div class="page">
```

- [ ] **Step 6: Rewrite `partials/footer.php` (final)**

```php
<?php
// =====================================================================
//  partials/footer.php — footer, page scripts and closing tags
// =====================================================================
isset($settings) || exit; // opened directly in a browser: show nothing
?>

		<footer class="site-footer">
			<p>
				<a href="https://dfault.it/" target="_blank" title="D-FAULT PENDING">D-FAULT PARKED</a><br>
				<span class="wide-only">Copyright</span> &copy; 2005&ndash;<?= date('Y') ?> &middot; <a href="/" title="<?= e($settings['title']) ?>"><?= e($settings['title']) ?></a><br>
				<a href="https://dahlskebank.com/" target="_blank" title="Dahlske Bank">Powered by dB</a> /
				<a href="https://danieldahl.com/" target="_blank" title="Daniel Dahl">Fueled by dD</a> /
				<a href="https://dxd.no/" target="_blank" title="deus ex Dahl">Pinnacle of dxD</a>
			</p>
		</footer>

	</div>
<?php foreach ($scripts as $src): ?>
	<script src="<?= e(asset($src)) ?>"></script>
<?php endforeach; ?>
</body>

</html>
```

- [ ] **Step 7: Rewrite `index.php` (final)**

```php
<?php
// =====================================================================
//  index.php — the landing page every parked domain shows
// =====================================================================
require_once __DIR__ . '/config.php';

$pageTitle = 'Coming Soon';
$robots    = 'index, follow';
$isHome    = true;
$scripts   = ['/dfault.js'];

require __DIR__ . '/partials/head.php';
?>

		<main>
			<h1 class="headline">Website <span>Coming Soon</span></h1>
			<p class="tagline">Oh, yes. Very soon. <span>They are building it now.</span></p>

			<!-- Random countdown, filled in by dfault.js ("--" until it runs) -->
			<div class="countdown">
				<div class="countdown-unit"><span class="countdown-num" id="days">--</span><span class="countdown-label">Days</span></div>
				<div class="countdown-unit"><span class="countdown-num" id="hours">--</span><span class="countdown-label">Hours</span></div>
				<div class="countdown-unit"><span class="countdown-num" id="minutes">--</span><span class="countdown-label">Minutes</span></div>
				<div class="countdown-unit"><span class="countdown-num" id="seconds">--</span><span class="countdown-label">Seconds</span></div>
			</div>
		</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
```

- [ ] **Step 8: Rewrite `404.php` and `403.php` (final)**

`404.php`:

```php
<?php
// =====================================================================
//  404.php — shown by Apache for missing pages (ErrorDocument 404)
// =====================================================================
require_once __DIR__ . '/config.php';
http_response_code(404);

$pageTitle = '404 Page Not Found';
$robots    = 'noindex';
$isHome    = false;
$scripts   = [];

require __DIR__ . '/partials/head.php';
?>

		<main>
			<h1 class="headline">404<br>Page Not Found</h1>
		</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
```

`403.php`:

```php
<?php
// =====================================================================
//  403.php — shown by Apache for blocked paths (ErrorDocument 403)
// =====================================================================
require_once __DIR__ . '/config.php';
http_response_code(403);

$pageTitle = '403 Access Denied';
$robots    = 'noindex';
$isHome    = false;
$scripts   = [];

require __DIR__ . '/partials/head.php';
?>

		<main>
			<h1 class="headline">403<br>Access Denied</h1>
		</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
```

- [ ] **Step 9: Rewrite `style.css` (final)**

```css
/*
	Theme Name:		D-FAULT
	Theme URI:		https://dfault.it/
	Author:			Daniel Dahl
	Author URI:		https://danieldahl.com/
	Description:	D-FAULT PARKED
	Version:		v26-0.1.0_parked
	License:		WTFPL
	License URI:	http://www.wtfpl.net/txt/copying/
*/

/* ==================================================================
   Hand-written styles, no framework.
   Per-domain values come from an inline <style> in partials/head.php:
     --accent           highlight colour
     --bg-image         url() of the background image
     --bg-opacity       opacity of the image layer
     --overlay-opacity  darkness of the black overlay on the image
   ================================================================== */

/* ---------- 1. Tokens ---------- */
:root {
	--text: #dee2e6;
	--text-muted: rgba(222, 226, 230, 0.75);
	--accent: #ffc107; /* fallback; head.php sets the real one */
	--radius: 0.5rem;
	--font: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", "Liberation Sans", Arial, sans-serif;
}

/* ---------- 2. Base ---------- */
*,
*::before,
*::after {
	box-sizing: border-box;
}

body {
	margin: 0;
	font-family: var(--font);
	font-size: 1rem;
	line-height: 1.5;
	color: var(--text);
	/* The Quartz theme's gradient, kept from the old Bootswatch look */
	background: #686dc3 linear-gradient(90deg, #33b7e2, #5e62b0, #dc307c);
	-webkit-text-size-adjust: 100%;
}

a {
	color: var(--accent);
	text-decoration: none;
}

a:hover {
	text-decoration: underline;
}

a:focus-visible {
	outline: 2px solid var(--accent);
	outline-offset: 2px;
	border-radius: 2px;
}

/* ---------- 3. Background image layer ---------- */
/* A fixed layer behind everything: overlay gradient on top of the image,
   the whole layer faded so the body gradient shows through. With no
   image, the variables are unset and only the body gradient shows. */
.bg-image {
	position: fixed;
	inset: 0;
	z-index: -1;
	background-image:
		linear-gradient(rgba(0, 0, 0, var(--overlay-opacity, 0)), rgba(0, 0, 0, var(--overlay-opacity, 0))),
		var(--bg-image, none);
	background-size: cover;
	background-position: center;
	opacity: var(--bg-opacity, 1);
}

/* ---------- 4. Page layout ---------- */
/* Full-height column: main sits in the middle, footer at the bottom.
   100dvh follows the visible screen while Chrome's address bar on
   Android slides in and out; 100vh is the fallback. */
.page {
	display: flex;
	flex-direction: column;
	min-height: 100vh;
	min-height: 100dvh;
	padding: 1rem 16px 0;
	text-align: center;
}

main {
	margin-block: auto;
	padding-block: 2rem;
}

/* ---------- 5. Headline + tagline ---------- */
.headline {
	margin: 0 0 1rem;
	font-size: clamp(2.6rem, 1.625rem + 4.5vw, 5rem);
	font-weight: 300;
	line-height: 1.2;
	text-shadow: 2px 4px 3px rgba(0, 0, 0, 0.3);
}

.headline span {
	font-weight: 400;
	color: var(--accent);
}

.tagline {
	margin: 0 0 1.5rem;
	font-size: 1.25rem;
	font-weight: 300;
	text-shadow: 1px 2px 4px rgba(0, 0, 0, 0.85);
}

/* ---------- 6. Countdown bar ---------- */
/* One frosted-glass panel, using the Quartz theme's glass recipe. */
.countdown {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	max-width: 36rem;
	margin: 0 auto;
	border-radius: var(--radius);
	background-image: linear-gradient(125deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.2) 70%);
	-webkit-backdrop-filter: blur(5px);
	backdrop-filter: blur(5px);
	box-shadow:
		inset 1px 1px rgba(255, 255, 255, 0.2),
		inset -1px -1px rgba(255, 255, 255, 0.1),
		0 8px 32px rgba(0, 0, 0, 0.37);
}

.countdown-unit {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 0.75rem 0.25rem;
}

/* Thin divider between units */
.countdown-unit + .countdown-unit {
	border-left: 1px solid rgba(255, 255, 255, 0.2);
}

.countdown-num {
	font-size: clamp(1.8rem, 1.2rem + 3vw, 3.25rem);
	font-weight: 300;
	line-height: 1.1;
	color: var(--accent);
	/* Same-width digits, so the numbers don't wobble as they tick */
	font-variant-numeric: tabular-nums;
	text-shadow: 2px 2px 2px rgba(0, 0, 0, 0.5);
}

.countdown-label {
	margin-top: 0.25rem;
	font-size: 0.75rem;
	letter-spacing: 0.08em;
	text-transform: uppercase;
	color: var(--text-muted);
	text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.75);
}

/* ---------- 7. Footer ---------- */
.site-footer {
	padding-bottom: 1rem;
	text-shadow: 1px 2px 4px rgba(0, 0, 0, 0.85);
}

.site-footer p {
	margin: 0;
}

.wide-only {
	display: none;
}

/* ---------- 8. Breakpoint (matches Bootstrap's old lg: 992px) ---------- */
@media (max-width: 991.98px) {
	/* Second half of the headline and tagline on its own line */
	.headline span,
	.tagline span {
		display: block;
	}
}

@media (min-width: 992px) {
	.site-footer {
		font-weight: 600;
		padding-bottom: 2rem;
	}

	.wide-only {
		display: inline;
	}
}
```

- [ ] **Step 10: Rewrite `dfault.js` (final)**

```js
// === RANDOM "COMING SOON" COUNTDOWN ===
// Picks a random target 30–180 days ahead on every page load (there's no
// real launch date; that's the joke) and ticks down once a second.
// Wrapped in a function so its variables stay out of the page's globals.
(function () {
	const minDays = 30; // shortest possible countdown
	const maxDays = 180; // longest possible countdown

	const els = {
		days: document.getElementById("days"),
		hours: document.getElementById("hours"),
		minutes: document.getElementById("minutes"),
		seconds: document.getElementById("seconds"),
	};
	if (!els.days) return; // this page has no countdown

	const day = 24 * 60 * 60 * 1000;
	const targetTime = Date.now() + (minDays + Math.random() * (maxDays - minDays)) * day;

	function pad(n) {
		return String(n).padStart(2, "0");
	}

	function update() {
		const diff = Math.max(0, targetTime - Date.now());
		els.days.textContent = Math.floor(diff / day);
		els.hours.textContent = pad(Math.floor((diff % day) / 3600000));
		els.minutes.textContent = pad(Math.floor((diff % 3600000) / 60000));
		els.seconds.textContent = pad(Math.floor((diff % 60000) / 1000));
	}

	update();
	setInterval(update, 1000);
})();
```

- [ ] **Step 11: Run all tests; all must pass**

Run:
```bash
node _tests/countdown.test.js
"$PHP" _tests/check.php
bash _tests/probe.sh
```
Expected: `All countdown checks passed`, `All N checks passed`, `All probe checks passed`.

- [ ] **Step 12: Weight check**

Run:
```bash
"$PHP" _tests/check.php --dump-only --dump="$SCRATCH/final"
wc -c style.css dfault.js
grep -oE '<(link|script)[^>]*(href|src)="https?://[^"]+' "$SCRATCH/final/index__kiande.com.html"
```
Expected: `style.css` under 6 KB. The `grep` prints exactly one line, the canonical `<link rel="canonical" href="https://kiande.com/`. That proves no external stylesheet or script remains.

- [ ] **Step 13: Daniel checks the visuals**

Ask Daniel to look at a few parked domains locally, on desktop Chrome and his Pixel, by pointing them at Laragon in the hosts file. Also ask him to look at an error page. **Wait for his OK or his tweaks before committing.**

- [ ] **Step 14: Commit and push**

```bash
git add partials index.php 404.php 403.php style.css dfault.js _tests/check.php _tests/countdown.test.js
git commit -m "feat: drop Bootstrap for hand-written CSS, slim glass countdown

- no CDN CSS/JS/icon font; system font stack
- one <h1> per page; countdown is a single frosted bar, one row on
  every screen, accent-coloured tabular digits
- per-domain values reach CSS as custom properties
- 100dvh layout; footer entities instead of icons
- dfault.js wrapped in an IIFE, safe on pages without a countdown

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
git push
```

---

### Task 5: README update

**Files:**
- Modify: `README.md`

**Interfaces:**
- Consumes: the final file layout and test commands from Tasks 1–4.

- [ ] **Step 1: Replace the "Adding a domain" and "Files" sections of `README.md`**

```markdown
## Adding a domain

1. Put the background image in `img/` (JPG, ~1200–1600 px wide, quality 90).
2. Add an entry to `$configs` in `config.php` (domain without `www.`). List only what differs from `$defaults`, but always set `background_image`, `bg_opacity`, `overlay_opacity` and `accent`.
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
| `partials/helpers.php` | `e()`, `asset()`, `color_or()` |
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
```

- [ ] **Step 2: Commit and push**

```bash
git add README.md
git commit -m "docs: README for the new structure and checks

Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>"
git push
```

---

## Manual items for Daniel (outside the code)

- **`https://www.<parked domain>` serves an expired certificate** (checked via real DNS on 2026-10-07 for kiande.com and brutalina.com). Browsers show a warning before Apache can redirect. Fix in the hosting panel: renew or add `www.` to the certificate.
- **After deploying:** run `bash _tests/probe.sh --prod kiande.com` (Review Focus 5).
