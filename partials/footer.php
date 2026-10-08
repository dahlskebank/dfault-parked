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
