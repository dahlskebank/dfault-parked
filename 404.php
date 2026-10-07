<?php
require_once __DIR__ . '/config.php';
header("HTTP/1.1 404 Not Found");
header("Status: 404 Not Found");
?>
<!DOCTYPE html>
<html lang="en" class="h-100" data-bs-theme="dark">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<meta name="description" content="D-FAULT PARKED | Coming soon page for <?php echo htmlspecialchars($settings['title'] ?? ucfirst($domain)); ?>">
	<meta name="theme-color" content="#0f172a">
	<!-- <meta name="author" content="Daniel Dahl"> -->
	<meta name="robots" content="noindex" />
	<title><?php echo htmlspecialchars($settings['title']); ?> | 404 Page Not Found</title>
	<link rel="canonical" href="https://<?php echo htmlspecialchars($domain); ?>/" />
	<link rel="icon" href="/favicon.ico" type="image/x-icon">

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/5.3.8/quartz/bootstrap.min.css" integrity="sha512-dnVRkLGpagS9BYaiWREn1h+iXsQukido4lIuMQFNc0ZBI285WEfmpQWf5sjL0yWS4JfrEJ1XUZ0O99zacIPmPA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">

	<link rel="stylesheet" href="/style.css" id="dfault-css" type="text/css" media="all" />

	<?php if (!empty($settings['ga_id'])): ?>
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($settings['ga_id']); ?>"></script>
		<script>
			window.dataLayer = window.dataLayer || [];

			function gtag() {
				dataLayer.push(arguments);
			}
			gtag('js', new Date());
			gtag('config', '<?php echo htmlspecialchars($settings['ga_id']); ?>')
		</script>
	<?php endif; ?>
	<?php if (!empty($settings['background_image'])): ?>
		<style>
			.bg-image {
				background-image:
					linear-gradient(rgba(0, 0, 0, <?php echo $settings['overlay_opacity']; ?>),
						rgba(0, 0, 0, <?php echo $settings['overlay_opacity']; ?>)),
					url('<?php echo htmlspecialchars($settings['background_image']); ?>') !important;
				opacity: <?php echo $settings['bg_opacity']; ?>;
			}
		</style>
	<?php endif; ?>

	<meta property="og:type" content="website" />
	<meta property="og:url" content="https://<?php echo htmlspecialchars($domain); ?>/" />
	<meta property="og:title" content="<?php echo htmlspecialchars($settings['og_title'] ?? $settings['title'] ?? ucfirst($domain)); ?>" />
	<meta property="og:description" content="<?php echo htmlspecialchars($settings['og_description'] ?? 'D-FAULT PARKED'); ?>" />
	<meta property="og:image" content="https://<?= htmlspecialchars($domain) ?><?= htmlspecialchars($settings['background_image']) ?>" />
	<meta name="twitter:card" content="summary_large_image" />
	<meta name="twitter:title" content="<?php echo htmlspecialchars($settings['og_title'] ?? $settings['title'] ?? ucfirst($domain)); ?>" />
	<meta name="twitter:description" content="<?php echo htmlspecialchars($settings['og_description'] ?? 'D-FAULT PARKED'); ?>" />
	<meta name="twitter:image" content="https://<?= htmlspecialchars($domain) ?><?= htmlspecialchars($settings['background_image']) ?>" />

</head>

<body class="d-flex h-100">
	<div class="bg-image"></div>
	<div class="container d-flex w-100 h-100 p-1 mx-auto flex-column">
		<div class="mb-auto"> </div>

		<main class="text-center">
			<h1 class="display-1">404<br>Page Not Found</h1>
		</main>

		<footer class="mt-auto text-center" role="contentinfo">
			<p class="m-0">
				<a href="https://dfault.it/" target="_blank" title="D-FAULT PENDING" class="text-warning">D-FAULT PARKED</a><br>
				<span class="d-none d-lg-inline">Copyright</span> <i class="bi bi-c-circle"></i> 2005&ndash;<?php echo date("Y") ?><i class="bi bi-dot"></i><a href="https://<?php echo htmlspecialchars($domain); ?>/" title="<?php echo htmlspecialchars(ucfirst($domain)); ?>" class="text-warning"><?php echo htmlspecialchars($settings['title']); ?></a><br>
				<a href="https://dahlskebank.com/" target="_blank" title="Dahlske Bank" class="text-warning">Powered by dB</a> /
				<a href="https://danieldahl.com/" target="_blank" title="Daniel Dahl" class="text-warning">Fueled by dD</a> /
				<a href="https://dxd.no/" target="_blank" title="deus ex Dahl" class="text-warning">Pinnacle of dxD</a>
			</p>
		</footer>

	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
	<!-- <script src="/dfault.js"></script> -->

</body>

</html>
