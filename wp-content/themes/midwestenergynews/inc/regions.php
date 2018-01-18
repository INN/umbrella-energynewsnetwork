<?php
/**
 * Helper functions for the regions taxonomy and archive pages.
 */

/**
 * Get posts marked as "Featured in taxonomy" for a given region name, falling back to most-recent
 *
 * @param string $region_name the region to retrieve featured posts for.
 * @param integer $number total number of posts to return, backfilling with regular posts as necessary.
 * @since Largo 0.5.5.4
 * @see mwen_get_featured_posts_in_region_and_category
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
 * @since Largo 0.5.5.4
 */
function mwen_region_archive_posts( $query ) {
	// don't muck with admin, non-archives, combo region/category archives (separate function for that), etc
	if (
		! $query->is_tax( 'region' )
		|| $query->is_tax( 'category' )
		|| ! $query->is_main_query()
		|| is_admin()
	) return;

	// get the featured posts
	$featured_posts = mwen_get_featured_posts_in_region( $query->get( 'region' ) );

	// get the IDs from the featured posts
	$featured_post_ids = array();
	foreach ( $featured_posts as $fpost )
		$featured_post_ids[] = $fpost->ID;

	$query->set( 'post__not_in', $featured_post_ids );
	remove_action( 'pre_get_posts', 'largo_category_archive_posts', 15 );
}
add_action( 'pre_get_posts', 'mwen_region_archive_posts', 14 );

/**
 * Get posts marked as featured for a given region name AND category, falling back to most-recent
 *
 * First searches for posts "featured in region", then for "featured in category".
 *
 * @param string $region_name the region to retrieve featured posts for.
 * @param string $category the region to retrieve featured posts for.
 * @param integer $number total number of posts to return, backfilling with regular posts as necessary.
 * @since Largo 0.5.5.4
 * @see mwen_get_featured_posts_in_region
 */
function mwen_get_featured_posts_in_region_and_category( $region_name, $category = '', $number = 5 ) {
	// get posts that are featured in region
	$featured_posts = get_posts( array(
		'numberposts' => (int) $number,
		'post_status' => 'publish',
		'tax_query' => array(
			array(
				'taxonomy' => 'category',
				'field' => 'slug',
				'terms' => $category,
			),
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
	) );

	// backfill with category-featured posts
	if ( count( $featured_posts ) < (int) $number ) {
		$category_featured_posts = get_posts( array(
			'numberposts' => (int) $number,
			'post_status' => 'publish',
			'post__not_in' => array_map( function( $x ) { return $x->ID; }, $featured_posts ),
			'tax_query' => array(
				array(
					'taxonomy' => 'category',
					'field' => 'slug',
					'terms' => $category,
				),
				array(
					'taxonomy' => 'region',
					'field' => 'slug',
					'terms' => $region_name,
				),
				array(
					'taxonomy' => 'prominence',
					'field' => 'slug',
					'terms' => 'category-featured',
				),
			),
		) );
		$featured_posts = array_merge( $featured_posts, $category_featured_posts );
	}

	// Backfill with non-featured posts if necessary
	if ( count( $featured_posts ) < (int) $number ) {
		$needed = (int) $number - count( $featured_posts );
		$regular_posts = get_posts( array(
			'numberposts' => $needed,
			'post_status' => 'publish',
			'post__not_in' => array_map( function( $x ) { return $x->ID; }, $featured_posts ),
			'tax_query' => array(
				array(
					'taxonomy' => 'category',
					'field' => 'slug',
					'terms' => $category,
				),
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
 * Helper for getting posts in a combined category/region archive, excluding featured posts.
 *
 * @param WP_Query $query
 * @uses mwen_get_featured_posts_in_region
 * @since Largo 0.5.5.4
 */
function mwen_region_and_category_archive_posts( $query ) {
	// don't muck with admin, non-archives, non-region, non-category, etc
	if (
		! ( true == $query->get( 'region' ) )
		|| ! $query->is_tax( 'category' )
		|| ! $query->is_main_query()
		|| is_admin()
	) return;

	// get the featured posts
	$featured_posts = mwen_get_featured_posts_in_region_and_category( $query->get( 'region' ), $query->get( 'category_name' ) );

	// get the IDs from the featured posts
	$featured_post_ids = array();
	foreach ( $featured_posts as $fpost ) {
		$featured_post_ids[] = $fpost->ID;
	}

	$query->set( 'post__not_in', $featured_post_ids );
	remove_action( 'pre_get_posts', 'largo_category_archive_posts', 15 );
}
add_action( 'pre_get_posts', 'mwen_region_and_category_archive_posts', 14 );

/**
 * Helper for getting posts in a category archive, excluding featured posts
 *
 * This is the same as Largo's largo_get_featured_posts_in_category but doesn't run on category+region pages.
 * Note how we remove_action the corresponding Largo filter.
 *
 * @param WP_Query $query
 * @uses largo_get_featured_posts_in_category
 * @since Largo 0.5.5.4
 */
function mwen_category_archive_posts( $query ) {
	// don't muck with admin, non-archives, region, non-category, etc
	if (
		( true == $query->get( 'region' ) )
		|| ! $query->is_tax( 'category' )
		|| ! $query->is_main_query()
		|| is_admin()
	) return;

	// get the featured posts
	$featured_posts = largo_get_featured_posts_in_category( $query->get( 'category' ) );

	// get the IDs from the featured posts
	$featured_post_ids = array();
	foreach ( $featured_posts as $fpost ) {
		$featured_post_ids[] = $fpost->ID;
	}

	$query->set( 'post__not_in', $featured_post_ids );
	remove_action( 'pre_get_posts', 'largo_category_archive_posts', 15 );
}
add_action( 'pre_get_posts', 'mwen_category_archive_posts', 14 );
