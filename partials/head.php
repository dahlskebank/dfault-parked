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
/**
 * Declared here so VS Code's PHP checker knows where they come from:
 * @var array  $settings
 * @var array  $defaults
 * @var string $domain
 * @var bool   $isKnown
 * @var string $pageTitle
 * @var string $robots
 * @var bool   $isHome
 */
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
// colours must be 6-digit hex values.
$bgImage = preg_match('#^/img/[\w.-]+$#', $settings['background_image']) ? $settings['background_image'] : '';
$accent   = color_or($settings['accent'], $defaults['accent']);
$gradient = gradient_or($settings['gradient'], $defaults['gradient']);
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
	<meta name="theme-color" content="<?= e($gradient[0]) ?>">
	<!-- <meta name="author" content="Daniel Dahl"> -->
<?php if ($showSeo): ?>
	<link rel="canonical" href="<?= e($siteUrl) ?>">
<?php endif; ?>
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" href="<?= e(asset('/style.css')) ?>">

	<!-- This domain's values; style.css uses them via var(--name) -->
	<style>
		:root {
			--accent: <?= $accent ?>;
			--bg-gradient: linear-gradient(90deg, <?= implode(', ', $gradient) ?>);
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
