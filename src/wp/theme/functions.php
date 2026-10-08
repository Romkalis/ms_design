<?php
/**
 * Тема «Дизайн-Цех».
 *
 * WordPress отвечает только за галерею: /gallery и /gallery/<проект>.
 * Остальные страницы — статика из сборки gulp, которая лежит рядом в корне сайта.
 */

defined('ABSPATH') || exit;

// Версия ?v= у css/js, как в статических страницах
const MSK_ASSETS_VERSION = '1.0.1';
const MSK_THEME_VERSION = '1.0.0';

require __DIR__ . '/inc/content-types.php';
require __DIR__ . '/inc/admin-project.php';
require __DIR__ . '/inc/admin-sections.php';
require __DIR__ . '/inc/media.php';
require __DIR__ . '/inc/seo.php';
require __DIR__ . '/inc/cleanup.php';
require __DIR__ . '/inc/template-helpers.php';

if (defined('WP_CLI') && WP_CLI) {
	require __DIR__ . '/inc/cli-import.php';
}

add_action('after_setup_theme', function () {
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('html5', ['script', 'style']);
});

// Адреса проектов без слэша на конце, как у статики: /gallery/tanin-dom
add_action('after_switch_theme', function () {
	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure('/%postname%');
	flush_rewrite_rules(false);
});
