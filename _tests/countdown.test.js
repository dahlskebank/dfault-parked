// _tests/countdown.test.js — checks dfault.js without a browser.
// Run from the project root:  node _tests/countdown.test.js
const fs = require("fs");
const path = require("path");
const vm = require("vm");

const src = fs.readFileSync(path.join(__dirname, "..", "dfault.js"), "utf8");
let failures = 0;
function check(ok, label) {
	if (!ok) {
		failures++;
		console.log("FAIL  " + label);
	}
}

// Run dfault.js in a sandbox with a fake document.
function run(withCountdown) {
	const els = {};
	for (const id of ["days", "hours", "minutes", "seconds"]) els[id] = { textContent: "--" };
	const ctx = vm.createContext({
		document: { getElementById: (id) => (withCountdown ? els[id] || null : null) },
		setInterval: () => 0,
		Date, Math, String, Number,
	});
	let error = null;
	try {
		vm.runInContext(src, ctx);
	} catch (e) {
		error = e;
	}
	return { els, ctx, error };
}

// 1. A page with the countdown (index.php)
const a = run(true);
check(a.error === null, "throws on the landing page: " + a.error);
const days = Number(a.els.days.textContent);
check(days >= 29 && days <= 180, "days outside 29–180: " + a.els.days.textContent);
for (const id of ["hours", "minutes", "seconds"]) {
	check(/^\d\d$/.test(a.els[id].textContent), id + " is not two digits: " + a.els[id].textContent);
}
check(vm.runInContext("typeof minDays", a.ctx) === "undefined", "leaks minDays into the page's global scope");

// 2. A page without it (404/403, or a future page)
const b = run(false);
check(b.error === null, "throws when #days is missing: " + b.error);

console.log(failures ? failures + " countdown check(s) failed" : "All countdown checks passed");
process.exit(failures ? 1 : 0);
