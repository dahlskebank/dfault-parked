// === RANDOM "COMING SOON" COUNTDOWN ===
// Picks a random target 30–180 days ahead on every page load (there's no
// real launch date; that's the joke) and ticks down once a second.
// Wrapped in a function so its variables stay out of the page's globals.
(function () {
	const minDays = 30; // shortest possible countdown
	const maxDays = 180; // longest possible countdown

	const els = {
		days: document.getElementById("days"),
		hours: document.getElementById("hours"),
		minutes: document.getElementById("minutes"),
		seconds: document.getElementById("seconds"),
	};
	if (!els.days) return; // this page has no countdown

	const day = 24 * 60 * 60 * 1000;
	const targetTime = Date.now() + (minDays + Math.random() * (maxDays - minDays)) * day;

	function pad(n) {
		return String(n).padStart(2, "0");
	}

	function update() {
		const diff = Math.max(0, targetTime - Date.now());
		els.days.textContent = Math.floor(diff / day);
		els.hours.textContent = pad(Math.floor((diff % day) / 3600000));
		els.minutes.textContent = pad(Math.floor((diff % 3600000) / 60000));
		els.seconds.textContent = pad(Math.floor((diff % 60000) / 1000));
	}

	update();
	setInterval(update, 1000);
})();
