<?php
/**
 * Данные проекта и разделов для шаблонов.
 */

defined('ABSPATH') || exit;

/** ID фото галереи в заданном порядке, только существующие вложения. */
function msk_project_gallery_ids(int $post_id): array
{
	$raw = (string) get_post_meta($post_id, '_msk_gallery', true);
	$ids = array_filter(array_map('absint', explode(',', $raw)));

	return array_values(array_filter($ids, fn($id) => wp_attachment_is_image($id)));
}

/** Описание под заголовком на странице проекта. */
function msk_project_lead(int $post_id): string
{
	return trim((string) get_post_meta($post_id, '_msk_lead', true));
}

function msk_project_card_title(int $post_id): string
{
	$title = trim((string) get_post_meta($post_id, '_msk_card_title', true));

	return $title !== '' ? $title : get_the_title($post_id);
}

function msk_project_card_text(int $post_id): string
{
	$text = trim((string) get_post_meta($post_id, '_msk_card_text', true));

	return $text !== '' ? $text : msk_project_lead($post_id);
}

/** Обложка карточки: выбранная обложка, иначе первое фото галереи. */
function msk_project_cover_id(int $post_id): int
{
	$thumb = (int) get_post_thumbnail_id($post_id);
	if ($thumb) {
		return $thumb;
	}
	$gallery = msk_project_gallery_ids($post_id);

	return $gallery[0] ?? 0;
}

/** alt фото: свой alt вложения, иначе название проекта. */
function msk_image_alt(int $attachment_id, string $fallback): string
{
	$alt = trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true));

	return $alt !== '' ? $alt : $fallback;
}

/** Многострочный текст из поля без HTML: экранирует и сохраняет переносы строк. */
function msk_text(string $text): string
{
	return nl2br(esc_html($text), false);
}

/** Разделы в порядке из настроек раздела, только непустые. */
function msk_sections(): array
{
	$terms = get_terms([
		'taxonomy' => MSK_SECTION,
		'hide_empty' => true,
	]);
	if (is_wp_error($terms)) {
		return [];
	}
	usort($terms, function ($a, $b) {
		$order = (int) get_term_meta($a->term_id, 'msk_order', true) <=> (int) get_term_meta($b->term_id, 'msk_order', true);

		return $order ?: strcmp($a->name, $b->name);
	});

	return $terms;
}

function msk_section_nav_label(WP_Term $term): string
{
	$label = trim((string) get_term_meta($term->term_id, 'msk_nav_label', true));

	return $label !== '' ? $label : $term->name;
}

function msk_section_shows_card_text(WP_Term $term): bool
{
	return (bool) get_term_meta($term->term_id, 'msk_show_card_text', true);
}

/** Опубликованные проекты раздела в порядке поля «Порядок». */
function msk_section_projects(WP_Term $term): array
{
	return get_posts([
		'post_type' => MSK_PROJECT,
		'post_status' => 'publish',
		'numberposts' => -1,
		'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
		'tax_query' => [
			[
				'taxonomy' => MSK_SECTION,
				'terms' => $term->term_id,
				'include_children' => false,
			],
		],
		'no_found_rows' => true,
	]);
}
