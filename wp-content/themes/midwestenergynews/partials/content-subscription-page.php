<?php
/**
 * The template used for displaying page content in page.php
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('clearfix'); ?>>
	<header>
		<h1 class="entry-title" itemprop="headline"><?php the_title(); ?></h1>
		<?php
			if ( current_user_can( 'edit_post', get_the_id() ) ) {
				echo ' <span class="edit-link"><a href="' . get_edit_post_link( get_the_ID() ) . '">' . __( 'Edit This Post', 'largo' ) . '</a></span>';
			}
		?>
	</header>
	<section class="entry-content">
		<?php do_action('largo_before_page_content'); ?>
		<?php the_content(); ?>
		<?php do_action('largo_after_page_content'); ?>
	</section><!-- .entry-content -->
</article><!-- #post-<?php the_ID(); ?> -->
