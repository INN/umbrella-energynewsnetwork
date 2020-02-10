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
		}

		if ( isset( $_GET['digest-search-region'] ) && ! empty( $_GET['digest-search-region'] ) ) {
			$region_query_params = $_GET['digest-search-region'];
			if ( is_string( $region_query_params ) ) {
				$regions = array( $region_query_params );
			}
			$region_query_params = array_map( 'sanitize_title_for_query', $region_query_params );

			$tax_query = $query->get( 'tax_query' );
			if ( empty( $tax_query ) ) {
				$tax_query = array();
			}

			foreach( $region_query_params as $region ) {
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
		}

		$date_query = array();

		if ( isset( $_GET['after'] ) && ! empty( $_GET['after'] ) ) {
			/*
			 * browsers with standards-compliant datepickers will submit a value in YYYY-MM-DD, according
			 * to https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date .
			 * @link: https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
			 */
			$maybe_after = sanitize_key( $_GET['after'] );
			if ( ! empty( $maybe_after ) && 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $maybe_after ) ) {
				$date_query['before'] = $maybe_after;
			}
		}

		if ( isset( $_GET['before'] ) && ! empty( $_GET['before'] ) ) {
			/*
			 * browsers with standards-compliant datepickers will submit a value in YYYY-MM-DD, according
			 * to https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date .
			 * @link: https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
			 */
			$maybe_before = sanitize_key( $_GET['before'] );
			if ( ! empty( $maybe_before ) && 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $maybe_before ) ) {
				$date_query['before'] = $maybe_before;
			}
		}

		if ( ! empty( $date_query ) ) {
			$query->set( 'date_query', $date_query );
		}

		if ( $query->is_category( 'digest' ) ) {
			// as a temporary thing because LMP isn't working at this time
			$query->set( 'posts_per_page', 20 );
		}
	}
	return $query;
}
add_action( 'pre_get_posts', 'mwen_region_search_query', 10, 1 );
