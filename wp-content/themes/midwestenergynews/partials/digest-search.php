<?php
/**
 * Search box for the digest template
 *
 * @link https://github.com/INN/umbrella-energynewsnetwork/issues/77
 */
?>
<form class="digest-search form-search" role="search" method="get" action="<?php echo esc_url( get_term_link( get_queried_object())); ?>">
	<div class="input-append">
		<input type="text" placeholder="<?php _e('Search', 'largo'); ?>" class="searchbox search-query" value="<?php the_search_query(); ?>" name="digest-search" /><button type="submit" name="search submit" class="search-submit btn"><?php _e('Go', 'largo'); ?></button>
	</div>

	<fieldset id="filter-buttons" name="region-selectors">
		<legend>Select a region:</legend>
		<?php
			$regions = get_terms(
				array(
					'taxonomy' => 'region',
					'hide_empty' => 'true',
				),
			);

			// find the submitted query
			$region_query_params = ( isset( $_GET['digest-search-region'] ) ) ? $_GET['digest-search-region'] : array() ;
			if ( is_string( $region_query_params ) ) {
				$region_query_params = array( $region_query_params );
			}
			$region_query_params = array_map( 'sanitize_title_for_query', $region_query_params );

			foreach ( $regions as $region ) {
				$checked = '';
				// doing this because shoehorning in_array() into checked() would be unreadable
				if ( in_array( $region->slug, $region_query_params ) ) {
					$checked = 'checked="checked"';
				}

				printf(
					'<label class="checkbox"><input name="digest-search-region[]" type="checkbox" class="term-%1$s btn" value="%2$s" %4$s>%3$s</label>',
					esc_attr( $region->term_id ),
					esc_attr( $region->slug ),
					esc_html( $region->name ),
					$checked
				);
			}

		?>
	</fieldset>
</form>
