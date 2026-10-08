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
