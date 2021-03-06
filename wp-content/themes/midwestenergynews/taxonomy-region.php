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
$have_featured = ! empty( $bigStoryPost );
$bigStoryPost = $bigStoryPost[0];

// enqueue the homepage CSS file for the region taxonomy archive, since they have the same layout
$suffix = (LARGO_DEBUG) ? '' : '.min';
wp_enqueue_style(
	'mwen-homepage',
	get_stylesheet_directory_uri().'/homepages/assets/css/mwen_homepage' . $suffix . '.css',
	array( 'mwen' )
);

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
			$title = wp_kses_post( single_term_title( '', false ) );
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
				if ( ! empty( $title ) ) {
					if ( is_tax( 'region', 'midwest' ) ) {
						// Midwest
						echo '<img src="' . get_stylesheet_directory_uri() . '/images/MidwestEnergyNews_Logo.svg' . '" alt="' . $title  . '" class="region-header" />';
					} else if ( is_tax( 'region', 'southeast' ) ) {
						// Southeast
						echo '<img src="' . get_stylesheet_directory_uri() . '/images/SoutheastEnergyNews_Logo.svg' . '" alt="' . $title  . '" class="region-header" />';
					} if ( is_tax( 'region', 'southwest' ) ) {
						// Southwest
						echo '<img src="' . get_stylesheet_directory_uri() . '/images/SouthwestEnergyNews_Logo.svg' . '" alt="' . $title  . '" class="region-header" />';
					} if ( is_tax( 'region', 'west' ) ) {
						// though the slug is "west", the name is "Western Energy News."
						echo '<img src="' . get_stylesheet_directory_uri() . '/images/WesternEnergyNews_Logo.svg' . '" alt="' . $title  . '" class="region-header" />';
					} if ( is_tax( 'region', 'northeast' ) ) {
						// Northeast
						echo '<img src="' . get_stylesheet_directory_uri() . '/images/NortheastEnergyNews_Logo.svg' . '" alt="' . $title  . '" class="region-header" />';
					} else {
						// all other cases
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
					<a class="hero-image" href="<?php echo esc_attr( get_permalink( $bigStoryPost ) ); ?>"><?php echo get_the_post_thumbnail( $bigStoryPost->ID, 'full' ); ?></a>
					<header>
						<?php largo_maybe_top_term( array( 'post' => $bigStoryPost->ID ) ); ?>
						<h2><a href="<?php echo get_permalink( $bigStoryPost ); ?>" class="has-photo"><?php echo get_the_title( $bigStoryPost ); ?></a></h2>
						<p class="excerpt"><?php echo get_the_excerpt( $bigStoryPost ); ?></p>
					</header>
				</article>
				<div id="homepage-bottom">
					<?php
						rewind_posts();
						global $wp_query;

						echo mwen_print_homepage_posts( $wp_query );

						largo_content_nav( 'nav-below' ); 
					?>
					<?php ?>
				</div><!-- #homepage-bottom -->
			</div>

			<?php
				get_sidebar();
			?>
		</div>



		<?php } else {
			// if there are no posts or featured posts:
			get_template_part( 'partials/content', 'not-found' );
		}
	?>
</div><!-- clearfix -->

<?php get_footer();
