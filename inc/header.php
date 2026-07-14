<!DOCTYPE html>
<?php
	require_once __DIR__ . '/seo.php';
	$seo = seo_page_data();
?>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<meta name="description" content="<?php echo htmlspecialchars($seo['description']); ?>">
		<meta name="author" content="Global Ministries Daily Bread">
		<meta name="keywords" content="Global Ministries Daily Bread, Christian ministry, Bible teaching, Duke Randolph, Christian books, ministry events, preaching videos, empowerment conference">
		<meta name="robots" content="<?php echo htmlspecialchars($seo['robots']); ?>">
		<!-- google site verification for SEO -->
		<meta name="google-site-verification" content="aE06oe9Xe1oXcWmx8Wz_vqJYLkNF44EJlrITeTZImJc" />
	
		<title><?php echo htmlspecialchars($seo['title']); ?></title>
		<link rel="canonical" href="<?php echo htmlspecialchars($seo['canonical']); ?>">
		<meta property="og:type" content="<?php echo $seo['type'] === 'article' ? 'article' : 'website'; ?>">
		<meta property="og:title" content="<?php echo htmlspecialchars($seo['title']); ?>">
		<meta property="og:description" content="<?php echo htmlspecialchars($seo['description']); ?>">
		<meta property="og:url" content="<?php echo htmlspecialchars($seo['canonical']); ?>">
		<meta property="og:image" content="<?php echo htmlspecialchars($seo['image_url']); ?>">
		<meta property="og:site_name" content="<?php echo htmlspecialchars($seo['site_name']); ?>">
		<meta name="twitter:card" content="summary_large_image">
		<meta name="twitter:title" content="<?php echo htmlspecialchars($seo['title']); ?>">
		<meta name="twitter:description" content="<?php echo htmlspecialchars($seo['description']); ?>">
		<meta name="twitter:image" content="<?php echo htmlspecialchars($seo['image_url']); ?>">
		<script type="application/ld+json"><?php echo seo_json_ld($seo); ?></script>
		<!-- favicon -->
		<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
		<link rel="manifest" href="/site.webmanifest">
		<!-- Bootstrap -->
		<link rel="stylesheet" href="scripts/bootstrap/bootstrap.min.css">
		<!-- IonIcons -->
		<link rel="stylesheet" href="scripts/ionicons/css/ionicons.min.css">
		<!-- Toast -->
		<link rel="stylesheet" href="scripts/toast/jquery.toast.min.css">
		<!-- OwlCarousel -->
		<link rel="stylesheet" href="scripts/owlcarousel/dist/assets/owl.carousel.min.css">
		<link rel="stylesheet" href="scripts/owlcarousel/dist/assets/owl.theme.default.min.css">
		<!-- Magnific Popup -->
		<link rel="stylesheet" href="scripts/magnific-popup/dist/magnific-popup.css">
		<link rel="stylesheet" href="scripts/sweetalert/dist/sweetalert.css">
		<!-- Custom style -->
		<link rel="stylesheet" href="css/style.css">
		<link rel="stylesheet" href="css/skins/all.css">
		<link rel="stylesheet" href="css/demo.css">
		<link rel="stylesheet" href="css/variable.css">

		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	</head>
