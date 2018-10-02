<?php
/**
 * The template used for displaying page content in page.php
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?>>
	<section class="entry-content">
		<?php do_action('largo_before_page_content'); ?>
		<?php the_content(); ?>
		<?php do_action('largo_after_page_content'); ?>
	</section><!-- .entry-content -->
</article><!-- #post-<?php the_ID(); ?> -->
