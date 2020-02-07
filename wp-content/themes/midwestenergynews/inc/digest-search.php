<?php
/**
 * Functions related to the digest search
 *
 * @see partials/digest-search.php
 * @see category-digest.php
 * @link https://github.com/INN/umbrella-energynewsnetwork/issues/77
 */

/**
 * Filter the query to only return posts matching the search query parameters
 *
 */
function mwen_region_search_query( $query ) {
	if ( ! is_admin() && $query->is_main_query() ) {
		if ( isset( $_GET['digest-search'] ) && ! empty( $_GET['digest-search'] ) ) {
			$query->set( 's', sanitize_title_for_query( $_GET['digest-search'] ) );
		}
	}
	return $query;
}
add_action( 'pre_get_posts', 'mwen_region_search_query', 10, 1 );
