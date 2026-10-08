<?php
/**
 * Убираем из фронтенда всё, чего нет на статических страницах.
 */

defined('ABSPATH') || exit;

// Панель администратора поверх сайта ломает фиксированную шапку
add_filter('show_admin_bar', '__return_false');

remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);

add_action('wp_enqueue_scripts', function () {
	wp_dequeue_style('wp-block-library');
	wp_dequeue_style('classic-theme-styles');
	wp_dequeue_style('global-styles');
}, 100);

// Страницы авторов, вложений и блога сайту не нужны
add_action('template_redirect', function () {
	if (is_author() || is_attachment()) {
		wp_safe_redirect(home_url('/gallery'), 301);
		exit;
	}
});
