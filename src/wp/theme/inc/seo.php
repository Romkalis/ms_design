<?php
/**
 * title, description, canonical и Open Graph для страниц галереи.
 */

defined('ABSPATH') || exit;

const MSK_SITE_NAME = 'Дизайн Цех Ольги Бараевой';
const MSK_OG_SITE_NAME = 'Дизайн-Цех Ольги Бараевой';
const MSK_KEYWORDS = 'дизайн интерьеров, урал, екатеринбург, дизайн, подбор мебели, лучший дизайн, креативный дизайн, современный дизайн';

/** Мета-данные текущей страницы или null, если это не страница галереи. */
function msk_seo_data(): ?array
{
	if (is_post_type_archive(MSK_PROJECT)) {
		$description = 'Портфолио проектов: дизайн интерьеров квартир и загородных домов. Екатеринбург, Урал. Современный и креативный дизайн, комплектация и подбор мебели.';

		return [
			'title' => MSK_SITE_NAME . ' - Портфолио дизайна интерьеров — квартиры и дома',
			'og_title' => 'Портфолио дизайна интерьеров — квартиры и дома | ' . MSK_OG_SITE_NAME,
			'description' => $description,
			'url' => get_post_type_archive_link(MSK_PROJECT),
			'type' => 'website',
			'image' => home_url('/img/logos/logo.webp'),
		];
	}

	if (is_singular(MSK_PROJECT)) {
		$post_id = get_queried_object_id();
		$title = trim((string) get_post_meta($post_id, '_msk_seo_title', true));
		$title = $title !== '' ? $title : get_the_title($post_id) . ' | ' . MSK_SITE_NAME;

		$description = trim((string) get_post_meta($post_id, '_msk_seo_description', true));
		if ($description === '') {
			$description = wp_html_excerpt(preg_replace('/\s+/u', ' ', msk_project_lead($post_id)), 160, '…');
		}

		$cover = msk_project_cover_id($post_id);

		return [
			'title' => $title,
			'og_title' => $title,
			'description' => $description,
			'url' => get_permalink($post_id),
			'type' => 'article',
			'image' => $cover ? wp_get_attachment_image_url($cover, 'large') : home_url('/img/logos/logo.webp'),
		];
	}

	return null;
}

add_filter('pre_get_document_title', function (string $title) {
	$seo = msk_seo_data();

	return $seo ? $seo['title'] : $title;
});

// Канонический адрес выводим сами, чтобы не было двух тегов
remove_action('wp_head', 'rel_canonical');

add_action('wp_head', function () {
	$seo = msk_seo_data();
	if (!$seo) {
		return;
	}
	$tags = [
		['name', 'description', $seo['description']],
		['name', 'keywords', MSK_KEYWORDS],
		['property', 'og:locale', 'ru_RU'],
		['property', 'og:type', $seo['type']],
		['property', 'og:site_name', MSK_OG_SITE_NAME],
		['property', 'og:title', $seo['og_title']],
		['property', 'og:description', $seo['description']],
		['property', 'og:url', $seo['url']],
		['property', 'og:image', $seo['image']],
	];
	foreach ($tags as [$attr, $key, $value]) {
		if ($value !== '') {
			printf('<meta %s="%s" content="%s" />' . "\n", $attr, esc_attr($key), esc_attr($value));
		}
	}
	printf('<link rel="canonical" href="%s" />' . "\n", esc_url($seo['url']));
}, 2);
