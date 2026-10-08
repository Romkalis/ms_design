<?php
/**
 * /gallery — все разделы с карточками проектов.
 */

defined('ABSPATH') || exit;

$sections = msk_sections();

get_header();
?>

<main class="main" id="top">
	<h1 class="visually-hidden">Галерея проектов</h1>

	<section class="gallery" id="gallery">
		<div class="container">

			<?php if ($sections) : ?>
				<nav class="gallery__nav" aria-label="Разделы галереи">
					<ul class="gallery__nav-list">
						<?php foreach ($sections as $section) : ?>
							<li class="gallery__nav-item">
								<a class="gallery__nav-link" href="#<?php echo esc_attr($section->slug); ?>"><?php echo esc_html(msk_section_nav_label($section)); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>

			<?php foreach ($sections as $section) :
				$projects = msk_section_projects($section);
				if (!$projects) {
					continue;
				}
				$show_text = msk_section_shows_card_text($section);
				?>
				<div class="gallery__section" id="<?php echo esc_attr($section->slug); ?>">
					<h2 class="gallery__title title"><?php echo esc_html($section->name); ?></h2>
					<div class="splide gallery__slider">
						<div class="splide__track">
							<ul class="splide__list gallery__list">
								<?php foreach ($projects as $project) :
									$card_title = msk_project_card_title($project->ID);
									$card_text = $show_text ? msk_project_card_text($project->ID) : '';
									$cover = msk_project_cover_id($project->ID);
									?>
									<li class="splide__slide">
										<a href="<?php echo esc_url(get_permalink($project)); ?>" class="gallery__item">
											<?php
											if ($cover) {
												echo wp_get_attachment_image($cover, 'medium_large', false, [
													'class' => 'gallery__img',
													'alt' => msk_image_alt($cover, $card_title),
													'loading' => 'lazy',
													'decoding' => 'async',
												]);
											}
											?>
											<div class="gallery__card-content">
												<p class="gallery__slide-title title-s"><?php echo esc_html($card_title); ?></p>
												<?php if ($card_text !== '') : ?>
													<p class="gallery__slide-description"><?php echo msk_text($card_text); ?></p>
												<?php endif; ?>
											</div>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>
			<?php endforeach; ?>

		</div>
	</section>

	@@include('../../html/blocks/main/main__form.html')
</main>

<?php
get_footer();
