<?php
/**
 * /gallery/<проект> — страница проекта с фото.
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
	the_post();
	$title = get_the_title();
	$lead = msk_project_lead(get_the_ID());
	$gallery = msk_project_gallery_ids(get_the_ID());
	$history = trim(get_the_content());
	?>

	<main class="main container main-works" id="top">

		<h1 class="title-m"><?php echo esc_html($title); ?></h1>

		<?php if ($lead !== '') : ?>
			<p class="works__description"><?php echo msk_text($lead); ?></p>
		<?php endif; ?>

		<?php if ($gallery) : ?>
			<div class="splide works__slider">
				<div class="splide__track">
					<ul class="splide__list works__gallery">
						<?php foreach ($gallery as $image_id) : ?>
							<li class="splide__slide works__gallery-item">
								<?php
								echo wp_get_attachment_image($image_id, 'medium_large', false, [
									'class' => 'works__img js-modal-image',
									'alt' => msk_image_alt($image_id, $title),
									'loading' => 'lazy',
									'decoding' => 'async',
									'data-full-src' => wp_get_attachment_image_url($image_id, 'full'),
								]);
								?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		<?php endif; ?>

		<?php if ($history !== '') : ?>
			<div class="work__history"><?php the_content(); ?></div>
		<?php endif; ?>

	</main>

	<?php
endwhile;

get_footer();
