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
