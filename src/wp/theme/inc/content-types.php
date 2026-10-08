<?php
/**
 * Тип записей «Проект» и разделы галереи.
 */

defined('ABSPATH') || exit;

const MSK_PROJECT = 'project';
const MSK_SECTION = 'project_section';

add_action('init', function () {
	register_post_type(MSK_PROJECT, [
		'labels' => [
			'name' => 'Проекты',
			'singular_name' => 'Проект',
			'menu_name' => 'Галерея',
			'all_items' => 'Все проекты',
			'add_new' => 'Добавить проект',
			'add_new_item' => 'Новый проект',
			'edit_item' => 'Редактировать проект',
			'new_item' => 'Новый проект',
			'view_item' => 'Открыть проект',
			'view_items' => 'Открыть галерею',
			'search_items' => 'Искать проекты',
			'not_found' => 'Проектов не найдено',
			'not_found_in_trash' => 'В корзине проектов нет',
			'featured_image' => 'Обложка карточки',
			'set_featured_image' => 'Выбрать обложку',
			'remove_featured_image' => 'Убрать обложку',
			'use_featured_image' => 'Сделать обложкой',
		],
		'public' => true,
		// Классический редактор: поля проекта и галерея видны сразу под текстом
		'show_in_rest' => false,
		'menu_position' => 5,
		'menu_icon' => 'dashicons-format-gallery',
		'has_archive' => 'gallery',
		'rewrite' => ['slug' => 'gallery', 'with_front' => false],
		'supports' => ['title', 'editor', 'thumbnail', 'page-attributes'],
	]);

	register_taxonomy(MSK_SECTION, MSK_PROJECT, [
		'labels' => [
			'name' => 'Разделы галереи',
			'singular_name' => 'Раздел',
			'menu_name' => 'Разделы',
			'all_items' => 'Все разделы',
			'edit_item' => 'Редактировать раздел',
			'add_new_item' => 'Добавить раздел',
			'new_item_name' => 'Название раздела',
			'not_found' => 'Разделов нет',
		],
		// Своих страниц у разделов нет: это блоки на /gallery
		'public' => false,
		'show_ui' => true,
		'show_in_rest' => false,
		'show_admin_column' => true,
		// Галочки вместо поля с метками
		'hierarchical' => true,
		'rewrite' => false,
		'query_var' => false,
	]);
});
