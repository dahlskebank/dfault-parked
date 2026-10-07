// === RANDOM "COMING SOON" COUNTDOWN ===
// Completely random target every page load (no real launch date needed)
// Change minDays / maxDays below if you want a different range

const minDays = 30; // minimum days from now
const maxDays = 180; // maximum days from now (feels like "coming soon" but unknown)

const randomMs = (minDays + Math.random() * (maxDays - minDays)) * 24 * 60 * 60 * 1000;
let targetTime = Date.now() + randomMs; // this changes on every refresh!

function updateCountdown() {
	const diff = targetTime - Date.now();

	if (diff <= 0) {
		// Countdown finished (won't normally happen with random future date)
		document.getElementById("days").textContent = "00";
		document.getElementById("hours").textContent = "00";
		document.getElementById("minutes").textContent = "00";
		document.getElementById("seconds").textContent = "00";
		return;
	}

	const days = Math.floor(diff / (1000 * 60 * 60 * 24));
	const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
	const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
	const seconds = Math.floor((diff % (1000 * 60)) / 1000);

	// Update the cards
	document.getElementById("days").textContent = days;
	document.getElementById("hours").textContent = hours.toString().padStart(2, "0");
	document.getElementById("minutes").textContent = minutes.toString().padStart(2, "0");
	document.getElementById("seconds").textContent = seconds.toString().padStart(2, "0");
}

// Start immediately + update every second
updateCountdown();
setInterval(updateCountdown, 1000);
