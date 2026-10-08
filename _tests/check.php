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
		$wantAccent = strtolower($s['accent']);
		$wantGrad   = array_map('strtolower', $s['gradient'] ?? []);
		$wantTheme  = $wantGrad[0] ?? '(no gradient in config)';

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
		if ($img) {
			check(is_file($root . $img), "$label background image file missing: $img");
		}
		// The address bar takes the gradient's first colour; text keeps the accent.
		check($i['theme'] === $wantTheme, "$label theme-color is '{$i['theme']}', expected $wantTheme");
		check(strpos((string) $i['style'], "--accent: $wantAccent;") !== false, "$label --accent is not $wantAccent");
		$wantCss = '--bg-gradient: linear-gradient(90deg, ' . implode(', ', $wantGrad) . ');';
		check($wantGrad && strpos((string) $i['style'], $wantCss) !== false, "$label missing '$wantCss'");
		check($i['h1_count'] === 1, "$label has {$i['h1_count']} <h1> elements, expected 1");
		check(stripos($html, 'bootstrap') === false && stripos($html, 'bootswatch') === false, "$label still references Bootstrap");
		$hasCountdown = strpos($html, 'id="days"') !== false;
		$hasScript = (bool) preg_match('#<script src="/dfault\.js\?v=\d+"></script>#', $html);
		check($hasCountdown === $p['home'], "$label countdown markup present: " . var_export($hasCountdown, true));
		check($hasScript === $p['home'], "$label dfault.js loaded: " . var_export($hasScript, true));
	}
}

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
	if (function_exists('gradient_or')) {
		$fb = ['#33b7e2', '#5e62b0', '#dc307c'];
		check(gradient_or(['#A53E1D', '#7e741b'], $fb) === ['#a53e1d', '#7e741b'], 'gradient_or keeps two valid colours, lower-cased');
		check(gradient_or(['#a53e1d', '#7e741b', '#1b637e', '#3d170b'], $fb) === ['#a53e1d', '#7e741b', '#1b637e', '#3d170b'], 'gradient_or allows four colours');
		check(gradient_or(['#a53e1d'], $fb) === $fb, 'gradient_or needs at least two colours');
		check(gradient_or(['#111111', '#222222', '#333333', '#444444', '#555555'], $fb) === $fb, 'gradient_or allows at most four colours');
		check(gradient_or(['#a53e1d', 'red'], $fb) === $fb, 'gradient_or rejects a bad colour');
		check(gradient_or(['#a53e1d', ['#7e741b']], $fb) === $fb, 'gradient_or rejects a nested array');
		check(gradient_or('#a53e1d', $fb) === $fb, 'gradient_or rejects a plain string');
		check(gradient_or(['#a53e1d', '#7e741b);}body{display:none'], $fb) === $fb, 'gradient_or rejects CSS injection');
		check(gradient_or(null, $fb) === $fb, 'gradient_or handles a missing value');
	} else {
		check(false, 'gradient_or() is not defined in partials/helpers.php');
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
