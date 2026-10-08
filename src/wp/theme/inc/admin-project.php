<?php
/**
 * Поля проекта в админке: описание, карточка, галерея фото, SEO.
 */

defined('ABSPATH') || exit;

// Блоки идут в том же порядке, что на странице: описание, фото, история (редактор)
add_action('add_meta_boxes_' . MSK_PROJECT, function () {
	add_meta_box('msk_project_page', 'Описание', 'msk_render_page_box', MSK_PROJECT, 'msk_after_title', 'high');
	add_meta_box('msk_project_gallery', 'Фото проекта', 'msk_render_gallery_box', MSK_PROJECT, 'msk_after_title', 'default');
	add_meta_box('msk_project_card', 'Карточка в галерее', 'msk_render_card_box', MSK_PROJECT, 'normal', 'high');
	add_meta_box('msk_project_seo', 'SEO', 'msk_render_seo_box', MSK_PROJECT, 'normal', 'low');
});

add_action('edit_form_after_title', function (WP_Post $post) {
	if ($post->post_type !== MSK_PROJECT) {
		return;
	}
	echo '<div class="msk-after-title">';
	do_meta_boxes(get_current_screen(), 'msk_after_title', $post);
	echo '</div>';
	echo '<h2 class="msk-editor-heading">История проекта <span>— текст под фотографиями, можно оставить пустым</span></h2>';
});

function msk_render_page_box(WP_Post $post): void
{
	wp_nonce_field('msk_project_save', 'msk_project_nonce');
	?>
	<p>
		<label for="msk_lead">Короткий текст под заголовком страницы</label>
		<textarea id="msk_lead" name="msk_lead" rows="4" class="large-text"><?php echo esc_textarea((string) get_post_meta($post->ID, '_msk_lead', true)); ?></textarea>
	</p>
	<?php
}

function msk_render_gallery_box(WP_Post $post): void
{
	$ids = msk_project_gallery_ids($post->ID);
	?>
	<div class="msk-gallery<?php echo $ids ? '' : ' is-empty'; ?>">
		<input type="hidden" name="msk_gallery" value="<?php echo esc_attr(implode(',', $ids)); ?>">
		<ul class="msk-gallery__list">
			<?php foreach ($ids as $id) : ?>
				<li class="msk-gallery__item" data-id="<?php echo esc_attr($id); ?>">
					<?php echo wp_get_attachment_image($id, 'thumbnail'); ?>
					<button type="button" class="msk-gallery__remove" aria-label="Убрать фото">&times;</button>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="msk-gallery__empty">Фото пока нет.</p>
		<p>
			<button type="button" class="button button-primary msk-gallery__add">Добавить фото</button>
			<button type="button" class="button msk-gallery__clear">Убрать все</button>
		</p>
		<p class="description">Можно выбрать или перетащить сразу много файлов. Порядок меняется перетаскиванием. Фото убирается только из этого проекта, из медиатеки оно не удаляется.</p>
	</div>
	<?php
}

function msk_render_card_box(WP_Post $post): void
{
	?>
	<p class="description">Обложка карточки задаётся справа в блоке «Обложка карточки». Если её нет, берётся первое фото проекта.</p>
	<p>
		<label for="msk_card_title"><strong>Заголовок карточки</strong></label>
		<input type="text" id="msk_card_title" name="msk_card_title" class="large-text" value="<?php echo esc_attr((string) get_post_meta($post->ID, '_msk_card_title', true)); ?>">
		<span class="description">Если пусто — название проекта.</span>
	</p>
	<p>
		<label for="msk_card_text"><strong>Текст карточки</strong></label>
		<textarea id="msk_card_text" name="msk_card_text" rows="3" class="large-text"><?php echo esc_textarea((string) get_post_meta($post->ID, '_msk_card_text', true)); ?></textarea>
		<span class="description">Если пусто — описание проекта. Показывается только в разделах, где включены тексты на карточках.</span>
	</p>
	<?php
}

function msk_render_seo_box(WP_Post $post): void
{
	?>
	<p>
		<label for="msk_seo_title"><strong>Title</strong> — заголовок вкладки и сниппета в поиске</label>
		<input type="text" id="msk_seo_title" name="msk_seo_title" class="large-text" value="<?php echo esc_attr((string) get_post_meta($post->ID, '_msk_seo_title', true)); ?>">
		<span class="description">Если пусто — «Название проекта | Дизайн Цех Ольги Бараевой».</span>
	</p>
	<p>
		<label for="msk_seo_description"><strong>Description</strong></label>
		<textarea id="msk_seo_description" name="msk_seo_description" rows="2" class="large-text"><?php echo esc_textarea((string) get_post_meta($post->ID, '_msk_seo_description', true)); ?></textarea>
		<span class="description">Если пусто — начало описания проекта.</span>
	</p>
	<?php
}

add_action('save_post_' . MSK_PROJECT, function (int $post_id) {
	if (!isset($_POST['msk_project_nonce']) || !wp_verify_nonce(sanitize_key($_POST['msk_project_nonce']), 'msk_project_save')) {
		return;
	}
	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
		return;
	}

	$text_fields = ['msk_card_title' => '_msk_card_title', 'msk_seo_title' => '_msk_seo_title'];
	foreach ($text_fields as $field => $key) {
		update_post_meta($post_id, $key, sanitize_text_field(wp_unslash($_POST[$field] ?? '')));
	}

	$textarea_fields = ['msk_lead' => '_msk_lead', 'msk_card_text' => '_msk_card_text', 'msk_seo_description' => '_msk_seo_description'];
	foreach ($textarea_fields as $field => $key) {
		update_post_meta($post_id, $key, sanitize_textarea_field(wp_unslash($_POST[$field] ?? '')));
	}

	$ids = array_filter(array_map('absint', explode(',', (string) wp_unslash($_POST['msk_gallery'] ?? ''))));
	update_post_meta($post_id, '_msk_gallery', implode(',', array_unique($ids)));
});

add_action('admin_enqueue_scripts', function (string $hook) {
	$screen = get_current_screen();
	if (!$screen || $screen->post_type !== MSK_PROJECT || !in_array($hook, ['post.php', 'post-new.php'], true)) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style('msk-admin', get_theme_file_uri('assets/admin.css'), [], MSK_THEME_VERSION);
	wp_enqueue_script('msk-admin-gallery', get_theme_file_uri('assets/admin-gallery.js'), ['jquery', 'jquery-ui-sortable'], MSK_THEME_VERSION, true);
});

// Колонка с обложкой и порядком в списке проектов
add_filter('manage_' . MSK_PROJECT . '_posts_columns', function (array $columns) {
	return array_slice($columns, 0, 1, true)
		+ ['msk_cover' => 'Обложка']
		+ array_slice($columns, 1, null, true)
		+ ['menu_order' => 'Порядок'];
});

add_action('manage_' . MSK_PROJECT . '_posts_custom_column', function (string $column, int $post_id) {
	if ($column === 'msk_cover') {
		$cover = msk_project_cover_id($post_id);
		echo $cover ? wp_get_attachment_image($cover, [60, 60]) : '—';
	}
	if ($column === 'menu_order') {
		echo (int) get_post_field('menu_order', $post_id);
	}
}, 10, 2);

add_filter('manage_edit-' . MSK_PROJECT . '_sortable_columns', function (array $columns) {
	$columns['menu_order'] = 'menu_order';

	return $columns;
});

// В админке проекты по умолчанию в том же порядке, что на сайте
add_action('pre_get_posts', function (WP_Query $query) {
	if (is_admin() && $query->is_main_query() && $query->get('post_type') === MSK_PROJECT && !$query->get('orderby')) {
		$query->set('orderby', ['menu_order' => 'ASC', 'date' => 'DESC']);
	}
});
