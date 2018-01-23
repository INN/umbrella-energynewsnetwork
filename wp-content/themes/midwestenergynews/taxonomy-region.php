<?php
/**
 * Template for various non-category archive pages (tag, term, date, etc.)
 *
 * @package Largo
 * @since 0.1
 * @filter largo_partial_by_post_type
 */
get_header();
$queried_object = get_queried_object();
$bigStoryPost = mwen_get_featured_posts_in_region( $queried_object->slug, 1 );
$have_featured = empty( $bigStoryPost );
$bigStoryPost = $bigStoryPost[0];
?>

<div class="clearfix">

	<?php
		if ( have_posts() || $have_featured ) {

			// queue up the first post so we know what type of archive page we're dealing with
			the_post();

			/*
			 * Display some different stuff in the header
			 * This is similar to largo's archive.php, but with everything
			 * not related to regions cut out.
			 */
			$title = single_term_title( '', false );
			$description = term_description();

			// rss links for custom taxonomies are a little tricky
			$term_id = intval( $queried_object->term_id );
			$tax = $queried_object->taxonomy;
			$rss_link = get_term_feed_link( $term_id, $tax );


			?>

		<header class="archive-background clearfix">
			<?php
				if ( isset( $rss_link ) ) {
					printf( '<a class="rss-link rss-subscribe-link" href="%1$s">%2$s <i class="icon-rss"></i></a>', $rss_link, __( 'Subscribe', 'largo' ) );
				}

				$post_id = largo_get_term_meta_post( $queried_object->taxonomy, $queried_object->term_id );
				largo_hero($post_id);

				// instead of outputting the title text, output the relevant image
				if ( isset( $title ) ) {
					if ( is_tax( 'region', 'midwest' ) ) {
						echo '<img src="' . get_stylesheet_directory_uri() . '/images/MidwestEnergyNews_Logo.svg' . '" alt="' . $title  . '" class="region-header" />';
					} else if ( is_tax( 'region', 'southeast' ) ) {
						echo '<img src="' . get_stylesheet_directory_uri() . '/images/SoutheastEnergyNews_Logo.svg' . '" alt="' . $title  . '" class="region-header" />';
					} if ( is_tax( 'region', 'southwest' ) ) {
						echo '<img src="' . get_stylesheet_directory_uri() . '/images/SouthwestEnergyNews_Logo.svg' . '" alt="' . $title  . '" class="region-header" />';
					} if ( is_tax( 'region', 'northeast' ) ) {
						echo '<img src="' . get_stylesheet_directory_uri() . '/images/NortheastEnergyNews_Logo.svg' . '" alt="' . $title  . '" class="region-header" />';
					} else {
						echo '<h1 class="page-title">' . $title . '</h1>';
					}
				}

				/* if ( isset( $description ) ) {
					echo '<div class="archive-description">' . $description . '</div>';
				}*/
			?>
		</header>

		<div id="homepage-top" class="row-fluid">
			<div class="span8">
				<article class="hero">
					<a href="<?php echo esc_attr( get_permalink( $bigStoryPost ) ); ?>"><?php echo get_the_post_thumbnail( $bigStoryPost->ID, 'full' ); ?></a>
					<header>
						<h2><a href="<?php echo get_permalink( $bigStoryPost ); ?>" class="has-photo"><?php echo get_the_title( $bigStoryPost ); ?></a></h2>
						<?php largo_byline( true, false, $bigStoryPost->ID ); ?>
						<p class="excerpt"><?php echo get_the_excerpt( $bigStoryPost ); ?></p>
					</header>
				</article>
			</div>
			<div class="span4">
				<?php dynamic_sidebar('sidebar-main'); ?>
			</div>
		</div>

		<div id="homepage-bottom">
			<?php
				global $shown_ids;
				
				rewind_posts();
				while ( have_posts() ) {
					the_post();
					$shown_ids[] = get_the_ID();
					$count++;

					$span = ( $count <= 3 ) ? 'span4' : 'span6';

					if ( $count === 1 || $count === 4 ) {
						echo '<div class="hg-row">';
					}
					$image_size =  (( $count >= 4 ) ? 'large' : 'medium' );
				?>

				<div class="<?php echo $span; ?>">
					<article class="hg-cell">
						<div class="hg-cell-inner">
							<!--<h5 class="top-tag"><?php largo_top_term();?></h5>-->
							<?php
								if ( has_post_thumbnail() ) {
									echo '<a href="' . get_permalink() . '" >' . get_the_post_thumbnail( $post->ID, $image_size ) . '</a>';
									echo '<h2 class="has-photo"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
								} else {
									echo '<h2><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
									largo_excerpt( $post->ID, 2 );
								}

								echo '<span class="hg-authors-byline">' . largo_byline() . '</span>';
							?>
						</div>
					</article>
				</div>
				<?php
					if ( $count === 3 || $count === 5 ) {
						echo '</div>'; //end of row;
					}
				} // end loop
			?>
			<?php largo_content_nav( 'nav-below' ); ?>
		</div><!-- #homepage-bottom -->


		<?php } else {
			// if there are no posts or featured posts:
			get_template_part( 'partials/content', 'not-found' );
		}
	?>
</div><!-- clearfix -->

<?php get_footer();
