<?php
// ====================== PARKED DOMAINS CONFIG ======================
// Add every new domain here. Super easy to maintain.

$host = strtolower(trim($_SERVER['HTTP_HOST'] ?? ''));
$domain = preg_replace('/^www\./i', '', $host);   // strip www. automatically

$configs = [
	'arksanity.com' => [
		'title'				=> 'Arksanity Server',
		'ga_id'				=> '',
		'background_image'	=> '/img/arksanity.jpg',
		'bg_opacity'		=> 0.65,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'Arksanity - ARK: Survival Evolved',
		'og_description'	=> 'Dedicated Linux Server for the Popular Game ARK: Survival Evolved'
	],
	'brutalina.com' => [
		'title'				=> 'Brutalina the Movie',
		'ga_id'				=> '',
		'background_image'	=> '/img/brutalina.jpg',
		'bg_opacity'		=> 0.75,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'Brutalina the Movie | Too Violent For Your Therapist',
		'og_description'	=> 'The bloodiest cinematic experience of the decade. So unhinged they almost cancelled it before filming started. Coming soonTM (probably).'
	],
	'compoundcomplex.com' => [
		'title'				=> 'Compound Complex',
		'ga_id'				=> '',
		'background_image'	=> '/img/compoundcomplex.jpg',
		'bg_opacity'		=> 0.60,
		'overlay_opacity'	=> 0.75,
		'og_title'			=> 'Compound Complex | Sentences That Make English Teachers Quit',
		'og_description'	=> 'Grammar so convoluted even linguists need therapy. The only website that requires a flowchart to understand itself.'
	],
	'cybabes.org' => [
		'title'				=> 'Cybabes Society',
		'ga_id'				=> '',
		'background_image'	=> '/img/cybabes.png',
		'bg_opacity'		=> 0.75,
		'overlay_opacity'	=> 0.55,
		'og_title'			=> 'Cybabes | Your AI Waifus Are Finally Online (Fashionably 27 Years Late)',
		'og_description'	=> 'Virtual girlfriends who reply... eventually. Better personalities than your ex. Zero emotional baggage (until the update).'
	],
	'cybocop.com' => [
		'title'				=> 'Cybo Cop (TV Series)',
		'ga_id'				=> '',
		'background_image'	=> '/img/cybocop.png',
		'bg_opacity'		=> 0.75,
		'overlay_opacity'	=> 0.35,
		'og_title'			=> 'Cybocop | Half Cop, Half Machine, All Existential Dread',
		'og_description'	=> 'Policing the Internet one bad take at a time. Will arrest you for bad grammar and poor life choices.'
	],
	'dahlskebank.com' => [
		'title'				=> 'Dahlske Bank',
		'ga_id'				=> '',
		'background_image'	=> '/img/dahlskebank.png',
		'bg_opacity'		=> 0.65,
		'overlay_opacity'	=> 0.25,
		'og_title'			=> 'Dahlske Bank | Norwegian Banking With Questionable Ethics',
		'og_description'	=> 'Where your money goes to die... slowly and with excellent customer service. Coming soon (after the next audit).'
	],
	'danieldahl.com' => [
		'title'				=> 'Daniel Dahl',
		'ga_id'				=> '',
		'background_image'	=> '/img/danieldahl.png',
		'bg_opacity'		=> 0.55,
		'overlay_opacity'	=> 0.65,
		'og_title'			=> 'Daniel Dahl | Professional Human, Amateur God',
		'og_description'	=> 'Full-time professional at being Daniel Dahl. Part-time deity. 100% Norwegian chaos energy.'
	],
	'darkdictator.com' => [
		'title'				=> 'The Dark Dictator',
		'ga_id'				=> '',
		'background_image'	=> '/img/darkdictator.png',
		'bg_opacity'		=> 0.65,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'Dark Dictator | Yes We\'re The Villains And We\'re Honest About It',
		'og_description'	=> 'Total world domination in progress. Please stand by. (Or else.)'
	],
	'fatalityfacilitator.com' => [
		'title'				=> 'Fatality Facilitator',
		'ga_id'				=> '',
		'background_image'	=> '/img/fatalityfacilitator.jpg',
		'bg_opacity'		=> 0.35,
		'overlay_opacity'	=> 0.00,
		'og_title'			=> 'Fatality Facilitator | Making Death Slightly More Convenient',
		'og_description'	=> 'Professional help with your inevitable demise since 2005. Fast, discreet, and weirdly polite.'
	],
	'iliketomovie.com' => [
		'title'				=> 'I Like To Movie',
		'ga_id'				=> '',
		'background_image'	=> '/img/iliketomovie.png',
		'bg_opacity'		=> 0.75,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'I Like To Movie | I Don\'t Watch Films, I Mainline Them Like Heroin',
		'og_description'	=> 'Terminal cinephilia. Entire personality is just obscure references and emotional damage. Coming soon (after my 47th rewatch of the same movie).'
	],
	'iliveagain.com' => [
		'title'				=> 'I Live Again',
		'ga_id'				=> '',
		'background_image'	=> '/img/iliveagain.png',
		'bg_opacity'		=> 0.75,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'I Live Again | Death Was A Phase',
		'og_description'	=> 'Came back wrong on purpose. Twice as unhinged and three times as hot. Resurrection kink activated.'
	],
	'kennywang.com' => [
		'title'				=> 'Kenny Wang',
		'ga_id'				=> '',
		'background_image'	=> '/img/kennywang.png',
		'bg_opacity'		=> 0.65,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'Kenny Wang | 100% Real Human Person (Source: Dude Trust Me)',
		'og_description'	=> 'Professional Wang. Amateur Kenny. Full-time identity theft victim of his own name.'
	],
	'kiande.com' => [
		'title'				=> 'KiAnDe',
		'ga_id'				=> '',
		'background_image'	=> '/img/kiande.png',
		'bg_opacity'		=> 0.50,
		'overlay_opacity'	=> 0.50,
		'og_title'			=> 'KiAnDe | Alien Queen In A Human Suit',
		'og_description'	=> 'Xenomorph-coded but make it sexy. We\'re not saying we lay eggs... but we\'re not denying it either.'
	],
	'killingheat.com' => [
		'title'				=> 'Killing Heat',
		'ga_id'				=> '',
		'background_image'	=> '/img/killingheat.png',
		'bg_opacity'		=> 0.65,
		'overlay_opacity'	=> 0.25,
		'og_title'			=> 'Killing Heat | So Hot It Should Be Classified As A War Crime',
		'og_description'	=> 'Global warming is just foreplay. This website will literally melt your face off.'
	],
	'marxisthunter.com' => [
		'title'				=> 'Marxist Hunter',
		'ga_id'				=> '',
		'background_image'	=> '/img/marxisthunter.png',
		'bg_opacity'		=> 0.50,
		'overlay_opacity'	=> 0.30,
		'og_title'			=> 'Marxist Hunter | From Each According To His Ability, To Each According To My 12-Gauge',
		'og_description'	=> 'Licensed commie remover. Class consciousness? Nah. Class annihilation.'
	],
	'meatfetish.com' => [
		'title'				=> 'Meat Fetish',
		'ga_id'				=> '',
		'background_image'	=> '/img/meatfetish.png',
		'bg_opacity'		=> 0.45,
		'overlay_opacity'	=> 0.20,
		'og_title'			=> 'Meat Fetish | We Don\'t Eat The Meat... We Fuck It',
		'og_description'	=> 'Carnivores with commitment issues. The only site where "raw dog" has a completely different meaning.'
	],
	'meatsex.org' => [
		'title'				=> 'Meat Sex',
		'ga_id'				=> '',
		'background_image'	=> '/img/meatsex.jpg',
		'bg_opacity'		=> 0.45,
		'overlay_opacity'	=> 0.30,
		'og_title'			=> 'Meatsex | Where The Sausage Party Gets Extremely Literal',
		'og_description'	=> 'Not safe for vegetarians. Not safe for vegans. Not safe for anyone with a functioning moral compass.'
	],
	'meloslave.com' => [
		'title'				=> 'Meloslave',
		'ga_id'				=> '',
		'background_image'	=> '/img/meloslave.png',
		'bg_opacity'		=> 0.75,
		'overlay_opacity'	=> 0.25,
		'og_title'			=> 'Meloslave | Submit To The Beat And Call Me Master',
		'og_description'	=> 'BDSM but the safe word is lo-fi beats to cry and obey to. Melancholy never sounded so submissive.'
	],
	'reservedekk.no' => [
		'title'				=> 'Reservedekk',
		'ga_id'				=> '',
		'background_image'	=> '/img/reservedekk.png',
		'bg_opacity'		=> 0.55,
		'overlay_opacity'	=> 0.20,
		'og_title'			=> 'Reserved Ekk | Norwegian For "Emotionally Constipated By Choice"',
		'og_description'	=> 'We don\'t do feelings. We do long awkward silences and passive-aggressive "hei". Peak Scandinavian dysfunction.'
	],
	'zombiefetish.com' => [
		'title'				=> 'Zombie Fetish',
		'ga_id'				=> '',
		'background_image'	=> '/img/zombiefetish.jpg',
		'bg_opacity'		=> 0.35,
		'overlay_opacity'	=> 0.00,
		'og_title'			=> 'Zombie Fetish | Cold Dead Flesh Is My Love Language',
		'og_description'	=> 'Brains are overrated. Give us rigor mortis and that sweet post-mortem drip. Necrophilia with extra steps.'
	],
];

$settings = $configs[$domain] ?? [
	'title'				=> 'Coming Soon',
	'ga_id'				=> '',
	'background_image'	=> '',
	'bg_opacity'		=> 0.50,
	'overlay_opacity'	=> 0.35,
	'og_title'			=> 'Coming Soon',
	'og_description'	=> 'D-FAULT PARKED'
];
