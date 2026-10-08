<?php
/**
 * Обработка загружаемых фото: webp и ограничение размера, как в статической сборке.
 */

defined('ABSPATH') || exit;

// Оригиналы больше этого размера по длинной стороне WordPress уменьшает при загрузке
add_filter('big_image_size_threshold', fn() => 2000);

add_filter('wp_editor_set_quality', fn() => 82);

// Уменьшенные копии jpg/png сохраняем в webp, если сервер это умеет
add_filter('image_editor_output_format', function (array $formats) {
	static $webp_supported = null;
	if ($webp_supported === null) {
		$webp_supported = wp_image_editor_supports(['mime_type' => 'image/webp']);
	}
	if ($webp_supported) {
		$formats['image/jpeg'] = 'image/webp';
		$formats['image/png'] = 'image/webp';
	}

	return $formats;
});

// Неиспользуемые в теме размеры не генерируем, чтобы не забивать диск
add_filter('intermediate_image_sizes_advanced', function (array $sizes) {
	unset($sizes['1536x1536'], $sizes['2048x2048']);

	return $sizes;
});
