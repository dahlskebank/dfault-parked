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
