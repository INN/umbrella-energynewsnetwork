<?php
/**
 * The template for displaying content in the single.php template
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'hnews item' ); ?> itemscope itemtype="http://schema.org/Article">

	<?php do_action('largo_before_post_header'); ?>

	<header>
		
		<?php mwen_top_term(); ?>
 		<h1 class="entry-title" itemprop="headline"><?php the_title(); ?></h1>

 		<?php largo_post_metadata( $post->ID ); ?>

	</header><!-- / entry header -->

	<?php do_action('largo_after_post_header'); ?>

	<div class="row story-row">
		<div class="span3">
			<h5 class="byline"><?php largo_byline(); ?></h5>
			<?php
				if ( $thumb_id = get_post_thumbnail_id( $post->ID ) ) {
					$thumb_custom = get_post_custom( $thumb_id );
					
					if ( !empty( $thumb_custom['_media_credit'][0] ) ) { ?>
						<h5 class="byline photo-credit visible-desktop">
							<div class="by">Photo By</div>
							<?php 
								if ( !empty ( $thumb_custom['_media_credit_url'][0] ) ) { ?>
									<a href="<?php echo $thumb_custom['_media_credit_url'][0]; ?>"><?php echo $thumb_custom['_media_credit'][0]; ?></a>
							<?php
								} else {
									echo $thumb_custom['_media_credit'][0];
								}
								if ( !empty( $thumb_custom['_navis_media_credit_org'][0] ) ) {
									echo ' / ' . $thumb_custom['_navis_media_credit_org'][0];
								}
							?>
						</h5>
					<?php 
					}
				} 
			?>
			
			<?php
				if ( !of_get_option( 'single_social_icons' ) == false ) {
					mwen_post_social_links();
				}
			?>
			
			
		</div>

		<div class="entry-content span9 mwen-article">
			<?php
				do_action('largo_before_hero');

				largo_hero( null,'' );

				do_action('largo_after_hero');
			?>
			<div itemprop="articleBody">
				<?php do_action('mwen_pre_largo_entry_content'); ?>
				<?php largo_entry_content( $post ); ?>
			</div>

			<?php if ( is_active_sidebar( 'article-bottom' ) ) {
				do_action( 'largo_before_post_bottom_widget_area' ); ?>
				<div class="article-bottom"><?php dynamic_sidebar( 'article-bottom' ); ?></div>
				<?php do_action( 'largo_after_post_bottom_widget_area' );
			}

			do_action('largo_before_comments');
			comments_template( '', true );
			do_action('largo_after_comments'); ?>
		</div><!-- .entry-content -->

		<?php do_action('largo_after_post_content'); ?>
	</div><!-- .row -->

	<footer class="post-meta bottom-meta">

		<?php if ( of_get_option( 'clean_read' ) === 'footer' ) : ?>
			<div class="clean-read-container clearfix">
				<a href="#" class="clean-read"><?php _e("View as 'Clean Read'", 'largo') ?></a>
			</div>
		<?php endif; ?>

	</footer><!-- /.post-meta -->

	<?php do_action('largo_after_post_footer'); ?>

</article><!-- #post-<?php the_ID(); ?> -->
