<?php defined('ABSPATH') || exit; ?>
<!doctype html>
<html lang="ru">

<head>
	<meta charset="UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />

	<link rel="preload" href="/css/main.css?v=<?php echo esc_attr(MSK_ASSETS_VERSION); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'" />
	<noscript>
		<link rel="stylesheet" href="/css/main.css?v=<?php echo esc_attr(MSK_ASSETS_VERSION); ?>" />
	</noscript>
	<link rel="preload" href="/fonts/tilda-sans_regular.woff2" as="font" type="font/woff2" crossorigin />
	<link rel="preload" href="/fonts/tilda-sans_semibold.woff2" as="font" type="font/woff2" crossorigin />

	<!-- Favicons block -->
	<link rel="icon" type="image/png" href="/img/favicons/favicon-96x96.png" sizes="96x96" />
	<link rel="icon" type="image/svg+xml" href="/img/favicons/favicon.svg" />
	<link rel="shortcut icon" href="/img/favicons/favicon.ico" />
	<link rel="apple-touch-icon" sizes="180x180" href="/img/favicons/apple-touch-icon.png" />
	<meta name="apple-mobile-web-app-title" content="design" />
	<link rel="manifest" href="/img/favicons/site.webmanifest" />

	<?php wp_head(); ?>

	@@include('../../html/blocks/analytics.html')
</head>

<body>
	@@include('../../html/blocks/header.html')
