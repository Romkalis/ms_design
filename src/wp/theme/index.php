<?php
/**
 * Запасной шаблон. Сайт показывает через WordPress только галерею,
 * сюда попадают обычные записи и страницы, если их создадут в админке.
 */

defined('ABSPATH') || exit;

get_header();
?>

<main class="main container main-works" id="top">
	<?php if (have_posts()) : ?>
		<?php while (have_posts()) : the_post(); ?>
			<h1 class="title-m"><?php the_title(); ?></h1>
			<div class="work__history"><?php the_content(); ?></div>
		<?php endwhile; ?>
	<?php else : ?>
		<h1 class="title-m">Страница не найдена</h1>
		<p class="works__description">Возможно, она переехала. Посмотрите <a href="/gallery">наши проекты</a> или вернитесь <a href="/">на главную</a>.</p>
	<?php endif; ?>
</main>

<?php
get_footer();
