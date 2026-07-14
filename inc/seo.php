<?php
function seo_url_for_path($path = 'home') {
	$baseUrl = 'https://globalministriesdailybread.org';
	$path = trim((string) $path, '/');

	if ($path === '' || $path === 'home') {
		return $baseUrl . '/';
	}

	return $baseUrl . '/' . $path;
}

function seo_current_path() {
	$path = isset($_GET['p']) ? trim($_GET['p'], '/') : 'home';
	return $path === '' ? 'home' : $path;
}

function seo_pages() {
	return [
		'home' => [
			'title' => 'Global Ministries Daily Bread | Faith, Mission, Books and Biblical Teaching',
			'description' => 'Global Ministries Daily Bread equips believers, families, leaders and communities through biblical teaching, ministry resources, books, videos and mission events.',
			'image' => 'images/1720010940_church-removebg-preview.png',
			'type' => 'website',
		],
		'mission' => [
			'title' => 'Mission and Vision | Global Ministries Daily Bread',
			'description' => 'Learn the mission and vision of Global Ministries Daily Bread and its commitment to biblical teaching, empowerment and global ministry.',
		],
		'core_values' => [
			'title' => 'Core Values | Global Ministries Daily Bread',
			'description' => 'Explore the values that guide Global Ministries Daily Bread in faith, discipleship, service and ministry.',
		],
		'team' => [
			'title' => 'Our Team | Global Ministries Daily Bread',
			'description' => 'Meet the people serving through Global Ministries Daily Bread and supporting its ministry work.',
		],
		'contact' => [
			'title' => 'Contact Global Ministries Daily Bread',
			'description' => 'Contact Global Ministries Daily Bread for ministry inquiries, events, books, videos and support.',
		],
		'books' => [
			'title' => 'Books | Global Ministries Daily Bread',
			'description' => 'Browse Christian books by Duke Fitz-Theodore Randolph on ministry, leadership, prayer, calling, marriage, confession and spiritual formation.',
			'image' => 'images/books/book1.png',
		],
		'videos' => [
			'title' => 'Videos | Preachings and Ministry Events',
			'description' => 'Watch preachings, teachings and ministry event videos from Global Ministries Daily Bread.',
			'image' => 'images/1720010940_church-removebg-preview.png',
		],
		'events/empowerment-conference' => [
			'title' => 'Your Time of Empowerment Conference | Global Ministries Daily Bread',
			'description' => 'Join Your Time of Empowerment Conference on August 14-15, 2026 at Jimlizer Hotel, Buru-Buru Nairobi. Theme: Called to Purpose.',
			'image' => 'images/events/empowerment-conference-main.jpeg',
			'type' => 'event',
		],
		'blogs/shepherd' => [
			'title' => 'The Lord is My Good Shepherd | Global Ministries Daily Bread',
			'description' => 'A biblical reflection on Psalm 23, Jesus as the Good Shepherd, and God’s care, guidance and protection for His people.',
			'image' => 'images/blogs/shepherd.png',
			'type' => 'article',
		],
		'blogs/faith' => [
			'title' => 'The Power of Faith | Global Ministries Daily Bread',
			'description' => 'A teaching on faith as trust in God’s promises and strength for Christian living.',
			'image' => 'images/4.jpg',
			'type' => 'article',
		],
		'blogs/baptism' => [
			'title' => 'Saved and Condemned | Global Ministries Daily Bread',
			'description' => 'A biblical teaching on Mark 16:16, faith, baptism and salvation in Jesus Christ.',
			'image' => 'images/blogs/baptism.png',
			'type' => 'article',
		],
	];
}

function seo_book_pages() {
	require_once __DIR__ . '/books_data.php';
	$pages = [];

	foreach ($books as $book) {
		$pages['books/' . $book['slug']] = [
			'title' => $book['title'] . ' | Global Ministries Daily Bread',
			'description' => $book['summary'],
			'image' => $book['cover'],
			'type' => 'book',
		];
	}

	return $pages;
}

function seo_page_data() {
	$path = seo_current_path();
	$pages = array_merge(seo_pages(), seo_book_pages());
	$defaults = seo_pages()['home'];
	$data = isset($pages[$path]) ? array_merge($defaults, $pages[$path]) : $defaults;

	$data['path'] = $path;
	$data['canonical'] = seo_url_for_path($path);
	$data['site_name'] = 'Global Ministries Daily Bread';
	$data['image_url'] = seo_absolute_asset($data['image']);
	$data['robots'] = in_array($path, ['login', 'register', 'forgot'], true) ? 'noindex, nofollow' : 'index, follow';

	return $data;
}

function seo_absolute_asset($asset) {
	if (preg_match('/^https?:\/\//', $asset)) {
		return $asset;
	}

	return 'https://globalministriesdailybread.org/' . ltrim($asset, '/');
}

function seo_json_ld($seo) {
	$graph = [
		[
			'@type' => 'Organization',
			'@id' => 'https://globalministriesdailybread.org/#organization',
			'name' => 'Global Ministries Daily Bread',
			'url' => 'https://globalministriesdailybread.org/',
			'logo' => 'https://globalministriesdailybread.org/images/1720010940_church-removebg-preview.png',
			'sameAs' => [
				'https://www.youtube.com/@globalministries-dailybread',
				'https://web.facebook.com/people/Global-Ministries-Daily-Bread/61570234694399/',
				'https://www.tiktok.com/@global.ministries4',
			],
		],
		[
			'@type' => 'WebSite',
			'@id' => 'https://globalministriesdailybread.org/#website',
			'url' => 'https://globalministriesdailybread.org/',
			'name' => 'Global Ministries Daily Bread',
			'publisher' => [
				'@id' => 'https://globalministriesdailybread.org/#organization',
			],
		],
		[
			'@type' => $seo['type'] === 'article' ? 'Article' : 'WebPage',
			'@id' => $seo['canonical'] . '#webpage',
			'url' => $seo['canonical'],
			'name' => $seo['title'],
			'description' => $seo['description'],
			'image' => $seo['image_url'],
			'isPartOf' => [
				'@id' => 'https://globalministriesdailybread.org/#website',
			],
		],
	];

	if ($seo['type'] === 'event') {
		$graph[] = [
			'@type' => 'Event',
			'name' => 'Your Time of Empowerment Conference',
			'startDate' => '2026-08-14T09:00:00+03:00',
			'endDate' => '2026-08-15T18:00:00+03:00',
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'eventStatus' => 'https://schema.org/EventScheduled',
			'image' => [$seo['image_url']],
			'description' => $seo['description'],
			'location' => [
				'@type' => 'Place',
				'name' => 'Jimlizer Hotel, Buru-Buru Nairobi',
				'address' => [
					'@type' => 'PostalAddress',
					'addressLocality' => 'Nairobi',
					'addressCountry' => 'KE',
				],
			],
			'organizer' => [
				'@id' => 'https://globalministriesdailybread.org/#organization',
			],
		];
	}

	return json_encode([
		'@context' => 'https://schema.org',
		'@graph' => $graph,
	], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
