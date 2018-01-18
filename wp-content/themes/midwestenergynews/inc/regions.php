<?php
/**
 * Helper functions for the regions taxonomy
 */

/**
 * Get posts marked as "Featured in taxonomy" for a given region name, falling back to most-recent
 *
 * @param string $region_name the region to retrieve featured posts for.
 * @param integer $number total number of posts to return, backfilling with regular posts as necessary.
 * @since 0.5
 */
function mwen_get_featured_posts_in_region( $region_name, $number = 1 ) {
	$args = array(
		'numberposts' => (int) $number,
		'post_status' => 'publish',
		'tax_query' => array(
			array(
				'taxonomy' => 'region',
				'field' => 'slug',
				'terms' => $region_name,
			),
			array(
				'taxonomy' => 'prominence',
				'field' => 'slug',
				'terms' => 'region-featured',
			),
		),
	);

	// Get the featured posts
	$featured_posts = get_posts( $args );

	// Backfill with non-featured posts if necessary
	if ( count( $featured_posts ) < (int) $number ) {
		$needed = (int) $number - count( $featured_posts );
		$regular_posts = get_posts( array(
			'numberposts' => $needed,
			'post_status' => 'publish',
			'post__not_in' => array_map( function( $x ) { return $x->ID; }, $featured_posts ),
			'tax_query' => array(
				array(
					'taxonomy' => 'region',
					'field' => 'slug',
					'terms' => $region_name,
				),
			),
		) );

		$featured_posts = array_merge( $featured_posts, $regular_posts );
	}

	return $featured_posts;
}

/**
 * Helper for getting posts in a region archive, excluding featured posts.
 *
 * @param WP_Query $query
 * @uses mwen_get_featured_posts_in_region
 * @since 0.4
 */
function mwen_region_archive_posts( $query ) {
	// don't muck with admin, non-archives, etc
	if ( ! $query->is_tax( 'region' ) || ! $query->is_main_query() || is_admin() ) return;

	// get the featured posts
	$featured_posts = mwen_get_featured_posts_in_region( $query->get( 'region' ) );

	// get the IDs from the featured posts
	$featured_post_ids = array();
	foreach ( $featured_posts as $fpost )
		$featured_post_ids[] = $fpost->ID;

	$query->set( 'post__not_in', $featured_post_ids );
}
add_action( 'pre_get_posts', 'mwen_region_archive_posts', 15 );
