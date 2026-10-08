<?php
/**
 * Настройки раздела галереи: порядок, подпись в меню, тексты на карточках.
 */

defined('ABSPATH') || exit;

add_action(MSK_SECTION . '_add_form_fields', function () {
	?>
	<div class="form-field">
		<label for="msk_order">Порядок</label>
		<input type="number" id="msk_order" name="msk_order" value="0" step="1">
		<p>Разделы на странице галереи идут по возрастанию этого числа.</p>
	</div>
	<div class="form-field">
		<label for="msk_nav_label">Подпись в меню галереи</label>
		<input type="text" id="msk_nav_label" name="msk_nav_label" value="">
		<p>Короткое название для меню над разделами. Если пусто — название раздела.</p>
	</div>
	<div class="form-field">
		<label><input type="checkbox" name="msk_show_card_text" value="1" checked> Показывать текст на карточках</label>
	</div>
	<?php
	wp_nonce_field('msk_section_save', 'msk_section_nonce');
});

add_action(MSK_SECTION . '_edit_form_fields', function (WP_Term $term) {
	?>
	<tr class="form-field">
		<th scope="row"><label for="msk_order">Порядок</label></th>
		<td>
			<input type="number" id="msk_order" name="msk_order" value="<?php echo esc_attr((int) get_term_meta($term->term_id, 'msk_order', true)); ?>" step="1">
			<p class="description">Разделы на странице галереи идут по возрастанию этого числа.</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="msk_nav_label">Подпись в меню галереи</label></th>
		<td>
			<input type="text" id="msk_nav_label" name="msk_nav_label" value="<?php echo esc_attr((string) get_term_meta($term->term_id, 'msk_nav_label', true)); ?>">
			<p class="description">Короткое название для меню над разделами. Если пусто — название раздела.</p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row">Карточки</th>
		<td>
			<label><input type="checkbox" name="msk_show_card_text" value="1" <?php checked(msk_section_shows_card_text($term)); ?>> Показывать текст на карточках</label>
			<?php wp_nonce_field('msk_section_save', 'msk_section_nonce'); ?>
		</td>
	</tr>
	<?php
});

function msk_save_section_meta(int $term_id): void
{
	if (!isset($_POST['msk_section_nonce']) || !wp_verify_nonce(sanitize_key($_POST['msk_section_nonce']), 'msk_section_save')) {
		return;
	}
	if (!current_user_can('edit_term', $term_id)) {
		return;
	}
	update_term_meta($term_id, 'msk_order', (int) ($_POST['msk_order'] ?? 0));
	update_term_meta($term_id, 'msk_nav_label', sanitize_text_field(wp_unslash($_POST['msk_nav_label'] ?? '')));
	update_term_meta($term_id, 'msk_show_card_text', empty($_POST['msk_show_card_text']) ? 0 : 1);
}

add_action('created_' . MSK_SECTION, 'msk_save_section_meta');
add_action('edited_' . MSK_SECTION, 'msk_save_section_meta');

add_filter('manage_edit-' . MSK_SECTION . '_columns', function (array $columns) {
	unset($columns['description']);
	$columns['msk_order'] = 'Порядок';

	return $columns;
});

add_filter('manage_' . MSK_SECTION . '_custom_column', function (string $content, string $column, int $term_id) {
	if ($column === 'msk_order') {
		return (string) (int) get_term_meta($term_id, 'msk_order', true);
	}

	return $content;
}, 10, 3);
