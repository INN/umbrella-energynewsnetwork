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
		$maybe_increase_count = false;
		if ( isset( $_GET['digest-search'] ) && ! empty( $_GET['digest-search'] ) ) {
			$query->set( 's', sanitize_title_for_query( $_GET['digest-search'] ) );

			$maybe_increase_count = true;
		}

		if ( isset( $_GET['digest-search-region'] ) && ! empty( $_GET['digest-search-region'] ) ) {
			$region_query_params = $_GET['digest-search-region'];
			if ( is_string( $region_query_params ) ) {
				$regions = array( $region_query_params );
			}
			$region_query_params = array_map( 'sanitize_title_for_query', $region_query_params );

			$tax_query = $query->get( 'tax_query' );
			error_log(var_export( $tax_query, true));
			if ( empty( $tax_query ) ) {
				$tax_query = array();
			}

			foreach( $region_query_params as $region ) {
				error_log(var_export( $region, true));
				$tax_query = array_merge(
					$tax_query,
					array(
						array(
							'taxonomy' => 'region',
							'field' => 'slug',
							'terms' => $region,
						),
					)
				);
			}
			
			if ( 1 < count( $tax_query, COUNT_NORMAL ) ) {
				$tax_query['relation'] = 'OR';
			}

			$query->set( 'tax_query', $tax_query );

			$maybe_increase_count = true;
		}

		if ( true === $maybe_increase_count ) {
			// as a temporary thing because LMP isn't working at this time
			$query->set( 'count', 20 );
		}
	}
	return $query;
}
add_action( 'pre_get_posts', 'mwen_region_search_query', 10, 1 );
