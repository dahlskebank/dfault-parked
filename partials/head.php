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
	<!-- <meta name="author" content="Daniel Dahl"> -->
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
