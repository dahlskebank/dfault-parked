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
