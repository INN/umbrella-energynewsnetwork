<?php
/**
 * Template for category archive pages
 *
 * @package Largo
 * @since 0.4
 * @filter largo_partial_by_post_type
 */
get_header();

global $tags, $paged, $post, $shown_ids;

$title = single_cat_title( '', false );
$description = category_description();
$rss_link = get_category_feed_link( get_queried_object_id() );
$posts_term = of_get_option( 'posts_term_plural', 'Stories' );
$queried_object = get_queried_object();

/**
 * Get posts marked as "Featured in category" for a given category name.
 *
 * @param string $category_name the category to retrieve featured posts for.
 * @param integer $number total number of posts to return, backfilling with regular posts as necessary.
 * @since 0.5
 */
function mwen_get_featured_posts_in_category( $category_name, $region, $number = 5 ) {
	$args = array(
		'category_name' => $category_name,
		'numberposts' => $number,
		'post_status' => 'publish',
	);


	$tax_query = array(
		'tax_query' => array(
			array(
				'taxonomy' => 'prominence',
				'field' => 'slug',
				'terms' => 'category-featured',
			)
		)
	);

	$region_query = array(
		'tax_query' => array(array(
				'taxonomy' => 'region',
				'field' => 'slug',
				'terms' => $region,
			)
		)
	);

	// Get the featured posts
	$featured_posts = get_posts( array_merge( $args, $tax_query, $region_query ) );
	//$featured_posts = get_posts( $args );

	// Backfill with regular posts if necessary
	if ( count( $featured_posts ) < (int) $number ) {
		$needed = (int) $number - count( $featured_posts );
		$regular_posts = get_posts( array_merge( $args, array(
			'numberposts' => $needed,
			'post__not_in' => array_map( function( $x ) { return $x->ID; }, $featured_posts )
		)));
		$featured_posts = array_merge( $featured_posts, $regular_posts );
	}

	return $featured_posts;
}
?>

<?php $region = get_query_var( 'region', '' ); ?>

<div class="clearfix">
	<header class="archive-background clearfix">
		<a class="rss-link rss-subscribe-link" href="<?php echo $rss_link; ?>"><?php echo __( 'Subscribe', 'largo' ); ?> <i class="icon-rss"></i></a>
		<?php
			$post_id = largo_get_term_meta_post( $queried_object->taxonomy, $queried_object->term_id );
			largo_hero( $post_id );
		?>
		<h1 class="page-title"><?php echo $title . ': ' . ucfirst($region); ?></h1>
		<div class="archive-description"><?php echo $description; ?></div>
		<?php do_action( 'largo_category_after_description_in_header' ); ?>
		<?php get_template_part( 'partials/archive', 'category-related' ); ?>
	</header>

	<?php if ( $paged < 2 && of_get_option( 'hide_category_featured' ) == '0' ) {
		$featured_posts = mwen_get_featured_posts_in_category( $wp_query->query_vars['category_name'], $region );
		if ( count( $featured_posts ) > 0 ) {
			$top_featured = $featured_posts[0];
			$shown_ids[] = $top_featured->ID; ?>

			<div class="primary-featured-post">
				<?php largo_render_template(
					'partials/archive',
					'category-primary-feature',
					array( 'featured_post' => $top_featured )
				); ?>
			</div>

			<?php $secondary_featured = array_slice( $featured_posts, 1 );
			if ( count( $secondary_featured ) > 0 ) { ?>
				<div class="secondary-featured-post">
					<div class="row-fluid clearfix"><?php
						foreach ( $secondary_featured as $idx => $featured_post ) {
								$shown_ids[] = $featured_post->ID;
								largo_render_template(
									'partials/archive',
									'category-secondary-feature',
									array( 'featured_post' => $featured_post )
								);
						} ?>
					</div>
				</div>
		<?php }
	}
} ?>
</div>

<div class="row-fluid clearfix">
	<div class="stories span8" role="main" id="content">

	<?php 
		do_action( 'largo_before_category_river' );
		if ( have_posts() ) {

			$counter = 1;
			while ( have_posts() ) {
				the_post();
			// 	if ( in_array( the_post()->ID, $featured_posts ) ) {
			// 		echo 'skipped ' . the_post()->ID;
			// 	} else {
					$post_type = get_post_type();
					$partial = largo_get_partial_by_post_type( 'archive', $post_type, 'archive' );
					get_template_part( 'partials/content', $partial );
					do_action( 'largo_loop_after_post_x', $counter, $context = 'archive' );
			// 	}
				$counter++;
			}
			largo_content_nav( 'nav-below' );
		} elseif ( count($featured_posts) > 0 ) {
			// do nothing
			// We have n > 1 posts in the featured header
			// It's not appropriate to display partials/content-not-found here.
		} else {
			get_template_part( 'partials/content', 'not-found' );
		}
		do_action( 'largo_after_category_river' );
	?>
	</div>
	<?php get_sidebar(); ?>
</div>

<?php get_footer();
