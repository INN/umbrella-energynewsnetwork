<?php
/**
 * Functions duplicating, replacing, or modifying Largo's inc/ajax-functions.php
 *
 * Primarily related to the Load More Posts functionality
 */

/**
 * Change 'Load more posts' button text to 'More posts'
 */
function mwen_next_posts_link($link) {
	return str_replace('Load more posts', 'More posts', $link);
}
add_filter('largo_next_posts_link', 'mwen_next_posts_link');

/**
 * Filter the Largo LMP query to use a region-specific partial
 *
 * @param string $partial The partial sub-slug, of the format `partials/content-$partial`
 * @param WP_Query $post_query The WP_Query object used to produce the LMP markup.
 * @return string $partial
 */
function mwen_largo_lmp_template_partial( $partial, $post_query ) {
	$query_vars = $post_query->query_vars;
	if (
		isset( $query_vars['region'] )
		&& $query_vars['region'] != ''
		&& ( ! isset ( $query_vars['category'] ) || empty ( $query_vars['category'] ) )
		&& ( ! isset ( $query_vars['category_name'] ) || empty ( $query_vars['category_name'] ) )
		&& ( ! isset ( $query_vars['cat'] ) || empty ( $query_vars['cat'] ) )
		&& ( ! isset ( $query_vars['tag'] ) || empty ( $query_vars['tag'] ) )
	) {
		$partial = 'region';
	}
	return $partial;
}
add_filter( 'largo_lmp_template_partial', 'mwen_largo_lmp_template_partial', 10, 2 );


/**
 * exclude Roundups from Regions loop
 *
 * This should _only_ run on the regions page, and not anywhere else
 *
 * @param query WP_Query the query that may be about to be run
 * @return WP_Query the query
 * @since Largo 0.5.5.4
 * @since WordPress 4.9.2
 */
function lmp_exclude_roundups( $query ) {

	/*
	 * make it happen when loading the page
	 */
	if ( ! is_admin() && $query->is_tax('region') && $query->is_main_query() ) {
		$query->set( 'post_type', array('post') );
	}

	/*
	 * make it happen for Load More Posts
	 *
	 * Note that is_admin may be true while running LMP
	 * and is_admin is true when updating a post or updating a term meta
	 * so we cannot simply allow or disallow based on is_admin
	 */
	if ( isset( $_POST ) && isset( $_post['action'] ) && 'load_more_posts' === $_POST['action'] && $query->is_tax('region') ) {
		$query->set( 'post_type', array('post') );
	}
	return $query;
}
add_action( 'pre_get_posts', 'lmp_exclude_roundups' );
