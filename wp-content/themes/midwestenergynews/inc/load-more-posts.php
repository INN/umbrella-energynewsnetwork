<?php
/**
 * Functions duplicating, replacing, or modifying Largo's inc/ajax-functions.php
 *
 * Primarily related to the Load More Posts functionality
 */

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
		&& ! isset ( $query_vars['category'] )
		&& ! isset ( $query_vars['category_name'] )
		&& ! isset ( $query_vars['category_name'] )
		&& ! isset ( $query_vars['tag'] )
	) {
		$partial = 'region';
	}
	return $partial;
}
add_filter( 'largo_lmp_template_partial', 'mwen_largo_lmp_template_partial', 10, 2 );
