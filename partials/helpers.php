<?php
// =====================================================================
//  partials/helpers.php — small functions the partials share
// =====================================================================
isset($settings) || exit; // opened directly in a browser: show nothing

// e(): escape text for HTML (content and attribute values).
function e($value) {
	return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// asset(): add ?v=<last-modified time> to a local file URL, e.g.
// '/style.css' -> '/style.css?v=1760000000'. Browsers may then cache the
// file for a year (see .htaccess) and still fetch a new copy the moment
// the file changes, because its URL changes with it.
function asset($path) {
	$file = dirname(__DIR__) . $path;
	return is_file($file) ? $path . '?v=' . filemtime($file) : $path;
}

// color_or(): return $value as a lowercase 6-digit hex colour ('#a1b2c3'),
// or $fallback if it isn't one. The result is printed inside CSS, so
// anything else (typos, colour names, injected CSS) is rejected.
function color_or($value, $fallback) {
	$value = strtolower(trim((string) $value));
	return preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : $fallback;
}

// gradient_or(): return $value as a list of 2–4 lowercase hex colours for
// the background gradient, or $fallback if it isn't exactly that.
function gradient_or($value, $fallback) {
	if (!is_array($value) || count($value) < 2 || count($value) > 4) {
		return $fallback;
	}
	$colours = [];
	foreach ($value as $colour) {
		$colour = is_string($colour) ? color_or($colour, null) : null;
		if ($colour === null) {
			return $fallback;
		}
		$colours[] = $colour;
	}
	return $colours;
}
