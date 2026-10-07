<?php
// =====================================================================
//  partials/footer.php — footer, page scripts and closing tags
// =====================================================================
/**
 * Set by config.php and the page (see partials/head.php):
 * @var array $settings
 * @var array $scripts
 */
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
