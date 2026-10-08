<?php
/**
 * Перенос галереи из статических страниц в WordPress.
 *
 *   wp msk import-gallery /путь/к/src --user=<логин администратора> [--dry-run]
 *
 * Читает src/html/blocks/gallery/gallery__content.html и src/html/gallery/*.html,
 * фото берёт из src/img. Повторный запуск обновляет проекты и разделы, уже
 * загруженные фото не дублирует.
 */

defined('ABSPATH') || exit;

class MSK_Gallery_Import
{
	private string $src = '';
	private bool $dry_run = false;
	private array $stats = ['sections' => 0, 'projects' => 0, 'uploaded' => 0, 'reused' => 0, 'missing' => 0];

	/**
	 * @param array $args       [0] — папка src из репозитория
	 * @param array $assoc_args --dry-run
	 */
	public function __invoke(array $args, array $assoc_args): void
	{
		$this->src = rtrim((string) realpath($args[0] ?? ''), '/');
		if ($this->src === '' || !is_file($this->src . '/html/blocks/gallery/gallery__content.html')) {
			WP_CLI::error('Не нашёл html/blocks/gallery/gallery__content.html. Укажите путь к папке src из репозитория.');
		}
		$this->dry_run = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'dry-run', false);

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$sections = $this->parse_gallery();
		$projects = $this->collect_projects($sections);

		WP_CLI::log(sprintf('Найдено разделов: %d, проектов: %d%s', count($sections), count($projects), $this->dry_run ? ' (пробный запуск, ничего не сохраняется)' : ''));

		wp_defer_term_counting(true);
		$term_ids = $this->import_sections($sections);
		foreach ($projects as $slug => $project) {
			$this->import_project($slug, $project, $term_ids);
		}
		wp_defer_term_counting(false);

		WP_CLI::success(sprintf(
			'Разделов: %d, проектов: %d, фото загружено: %d, уже были: %d, не найдено файлов: %d',
			$this->stats['sections'],
			$this->stats['projects'],
			$this->stats['uploaded'],
			$this->stats['reused'],
			$this->stats['missing']
		));
	}

	/** Разделы и карточки со страницы галереи, в порядке на странице. */
	private function parse_gallery(): array
	{
		$xpath = $this->load($this->src . '/html/blocks/gallery/gallery__content.html');

		$nav_labels = [];
		foreach ($xpath->query('//a[' . self::has_class('gallery__nav-link') . ']') as $link) {
			$nav_labels[ltrim($link->getAttribute('href'), '#')] = self::text($link);
		}

		$sections = [];
		foreach ($xpath->query('//div[' . self::has_class('gallery__section') . ']') as $node) {
			$slug = $node->getAttribute('id');
			$cards = [];
			foreach ($xpath->query('.//a[' . self::has_class('gallery__item') . ']', $node) as $card) {
				$href = trim($card->getAttribute('href'), '/');
				if (!str_starts_with($href, 'gallery/')) {
					continue;
				}
				$img = $xpath->query('.//img', $card)->item(0);
				$cards[] = [
					'slug' => substr($href, strlen('gallery/')),
					'cover' => $img ? $img->getAttribute('src') : '',
					'cover_alt' => $img ? $img->getAttribute('alt') : '',
					'title' => self::text($xpath->query('.//*[' . self::has_class('gallery__slide-title') . ']', $card)->item(0)),
					'text' => self::multiline($xpath->query('.//*[' . self::has_class('gallery__slide-description') . ']', $card)->item(0)),
				];
			}
			$name = self::text($xpath->query('.//h2', $node)->item(0));
			$sections[] = [
				'slug' => $slug,
				'name' => $name,
				'nav_label' => $nav_labels[$slug] ?? '',
				'show_card_text' => (bool) array_filter(array_column($cards, 'text')),
				'cards' => $cards,
			];
		}

		return $sections;
	}

	/** Проекты по slug: данные карточки из первого раздела, где проект встретился. */
	private function collect_projects(array $sections): array
	{
		$projects = [];
		foreach ($sections as $section) {
			foreach ($section['cards'] as $card) {
				$slug = $card['slug'];
				if (!isset($projects[$slug])) {
					$projects[$slug] = $card + ['order' => count($projects) * 10, 'sections' => []];
				}
				$projects[$slug]['sections'][] = $section['slug'];
			}
		}

		return $projects;
	}

	private function import_sections(array $sections): array
	{
		$term_ids = [];
		foreach ($sections as $index => $section) {
			WP_CLI::log("Раздел: {$section['name']}");
			$this->stats['sections']++;
			if ($this->dry_run) {
				continue;
			}
			$term = get_term_by('slug', $section['slug'], MSK_SECTION);
			$result = $term
				? wp_update_term($term->term_id, MSK_SECTION, ['name' => $section['name']])
				: wp_insert_term($section['name'], MSK_SECTION, ['slug' => $section['slug']]);
			if (is_wp_error($result)) {
				WP_CLI::warning("Раздел {$section['slug']}: " . $result->get_error_message());
				continue;
			}
			$term_id = (int) $result['term_id'];
			update_term_meta($term_id, 'msk_order', ($index + 1) * 10);
			update_term_meta($term_id, 'msk_nav_label', $section['nav_label'] !== $section['name'] ? $section['nav_label'] : '');
			update_term_meta($term_id, 'msk_show_card_text', $section['show_card_text'] ? 1 : 0);
			$term_ids[$section['slug']] = $term_id;
		}

		return $term_ids;
	}

	private function import_project(string $slug, array $card, array $term_ids): void
	{
		$page = $this->parse_project_page($slug);
		$title = $page['title'] !== '' ? $page['title'] : $card['title'];
		$images = $page['images'];

		WP_CLI::log(sprintf('Проект: %s — фото: %d', $title, count($images)));
		$this->stats['projects']++;

		$existing = get_posts([
			'post_type' => MSK_PROJECT,
			'name' => $slug,
			'post_status' => 'any',
			'numberposts' => 1,
			'fields' => 'ids',
		]);

		if ($this->dry_run) {
			foreach (array_merge([$card['cover']], array_column($images, 'src')) as $src) {
				if ($src !== '' && !is_file($this->file_path($src))) {
					WP_CLI::warning("Нет файла: {$src}");
					$this->stats['missing']++;
				}
			}

			return;
		}

		$post_id = wp_insert_post([
			'ID' => $existing[0] ?? 0,
			'post_type' => MSK_PROJECT,
			'post_status' => 'publish',
			'post_name' => $slug,
			'post_title' => $title,
			'post_content' => $page['history'],
			'menu_order' => $card['order'],
		], true);
		if (is_wp_error($post_id)) {
			WP_CLI::warning("Проект {$slug}: " . $post_id->get_error_message());

			return;
		}

		wp_set_object_terms($post_id, array_values(array_intersect_key($term_ids, array_flip($card['sections']))), MSK_SECTION);

		update_post_meta($post_id, '_msk_lead', $page['lead']);
		update_post_meta($post_id, '_msk_card_title', $card['title'] !== $title ? $card['title'] : '');
		update_post_meta($post_id, '_msk_card_text', self::same_text($card['text'], $page['lead']) ? '' : $card['text']);
		update_post_meta($post_id, '_msk_seo_title', $page['seo_title']);
		update_post_meta($post_id, '_msk_seo_description', $page['seo_description']);

		$gallery = [];
		foreach ($images as $image) {
			$id = $this->import_image($image['src'], $post_id, $slug, $image['alt']);
			if ($id) {
				$gallery[] = $id;
			}
		}
		update_post_meta($post_id, '_msk_gallery', implode(',', array_unique($gallery)));

		if ($card['cover'] !== '') {
			$cover_id = $this->import_image($card['cover'], $post_id, $slug, $card['cover_alt']);
			if ($cover_id) {
				set_post_thumbnail($post_id, $cover_id);
			}
		}
	}

	private function parse_project_page(string $slug): array
	{
		$page = ['title' => '', 'lead' => '', 'history' => '', 'images' => [], 'seo_title' => '', 'seo_description' => ''];
		$file = $this->src . '/html/gallery/' . $slug . '.html';
		if (!is_file($file)) {
			WP_CLI::warning("Нет страницы проекта: html/gallery/{$slug}.html");

			return $page;
		}
		$xpath = $this->load($file);

		$page['title'] = self::text($xpath->query('//h1')->item(0));
		$page['lead'] = self::multiline($xpath->query('//*[' . self::has_class('works__description') . ']')->item(0));
		$page['seo_title'] = self::text($xpath->query('//title')->item(0));
		$description = $xpath->query('//meta[@name="description"]')->item(0);
		$page['seo_description'] = $description ? trim(preg_replace('/\s+/u', ' ', $description->getAttribute('content'))) : '';

		$paragraphs = [];
		foreach ($xpath->query('//*[' . self::has_class('work__history') . ']') as $node) {
			foreach (preg_split('/(?:<br\s*\/?>\s*){2,}/i', self::inner_html($node)) as $paragraph) {
				// Переносы строк из вёрстки схлопываем, а одиночный <br> оставляем
				// переводом строки, как его хранит классический редактор
				$paragraph = preg_replace('/<br\s*\/?>/i', "\x00", $paragraph);
				$paragraph = preg_replace('/\s+/u', ' ', $paragraph);
				$paragraph = trim(preg_replace('/ ?\x00 ?/', "\n", $paragraph));
				if ($paragraph !== '') {
					$paragraphs[] = $paragraph;
				}
			}
		}
		$page['history'] = implode("\n\n", $paragraphs);

		foreach ($xpath->query('//img[' . self::has_class('works__img') . ']') as $img) {
			$page['images'][] = ['src' => $img->getAttribute('src'), 'alt' => $img->getAttribute('alt')];
		}

		return $page;
	}

	/** Загружает фото в медиатеку или возвращает уже загруженное из того же файла. */
	private function import_image(string $src, int $post_id, string $slug, string $alt): int
	{
		$source = self::source_key($src);
		$existing = get_posts([
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'meta_key' => '_msk_source',
			'meta_value' => $source,
			'numberposts' => 1,
			'fields' => 'ids',
		]);
		if ($existing) {
			$this->stats['reused']++;

			return (int) $existing[0];
		}

		$file = $this->file_path($src);
		if (!is_file($file)) {
			WP_CLI::warning("Нет файла: {$source}");
			$this->stats['missing']++;

			return 0;
		}

		// media_handle_sideload перемещает файл, поэтому отдаём ему копию
		$tmp = wp_tempnam(basename($file));
		copy($file, $tmp);
		$id = media_handle_sideload(['name' => sanitize_file_name($slug . '-' . basename($file)), 'tmp_name' => $tmp], $post_id);
		if (is_wp_error($id)) {
			@unlink($tmp);
			WP_CLI::warning("{$source}: " . $id->get_error_message());

			return 0;
		}

		update_post_meta($id, '_msk_source', $source);
		if ($alt !== '') {
			update_post_meta($id, '_wp_attachment_image_alt', $alt);
		}
		$this->stats['uploaded']++;

		return (int) $id;
	}

	private static function source_key(string $src): string
	{
		return '/' . ltrim(rawurldecode((string) parse_url($src, PHP_URL_PATH)), './');
	}

	private function file_path(string $src): string
	{
		return $this->src . self::source_key($src);
	}

	private function load(string $file): DOMXPath
	{
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTML('<?xml encoding="UTF-8">' . file_get_contents($file));
		libxml_clear_errors();

		return new DOMXPath($dom);
	}

	private static function has_class(string $class): string
	{
		return "contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')";
	}

	private static function inner_html(DOMNode $node): string
	{
		$html = '';
		foreach ($node->childNodes as $child) {
			$html .= $node->ownerDocument->saveHTML($child);
		}

		return $html;
	}

	private static function text(?DOMNode $node): string
	{
		return $node ? trim(preg_replace('/\s+/u', ' ', $node->textContent)) : '';
	}

	/** Текст без тегов, <br> становится переводом строки. */
	private static function multiline(?DOMNode $node): string
	{
		if (!$node) {
			return '';
		}
		$lines = array_map(
			fn($part) => trim(preg_replace('/\s+/u', ' ', html_entity_decode(wp_strip_all_tags($part), ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
			preg_split('/<br\s*\/?>/i', self::inner_html($node))
		);

		return trim(implode("\n", $lines));
	}

	private static function same_text(string $a, string $b): bool
	{
		$normalize = fn($s) => trim(preg_replace('/\s+/u', ' ', $s));

		return $normalize($a) === $normalize($b);
	}
}

WP_CLI::add_command('msk import-gallery', new MSK_Gallery_Import(), [
	'shortdesc' => 'Переносит проекты из статической галереи в WordPress.',
	'synopsis' => [
		['type' => 'positional', 'name' => 'src', 'description' => 'Папка src из репозитория', 'optional' => false],
		['type' => 'flag', 'name' => 'dry-run', 'description' => 'Только показать, что будет перенесено', 'optional' => true],
	],
]);
