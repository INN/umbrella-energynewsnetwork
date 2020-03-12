<?php
/**
 * Search box for the digest template
 *
 * @link https://github.com/INN/umbrella-energynewsnetwork/issues/77
 */

$after= '';
$before = '';

if ( isset( $_GET['after'] ) && ! empty( $_GET['after'] ) ) {
	$maybe_after = sanitize_key( $_GET['after'] );
	if ( ! empty( $maybe_after ) && 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $maybe_after ) ) {
		$after = $maybe_after;
	}
}
if ( isset( $_GET['before'] ) && ! empty( $_GET['before'] ) ) {
	$maybe_before = sanitize_key( $_GET['before'] );
	if ( ! empty( $maybe_before ) && 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $maybe_before ) ) {
		$before = $maybe_before;
	}
}


?>
<form class="digest-search form-search" role="search" method="get" action="<?php echo esc_url( get_term_link( get_queried_object())); ?>">
	<div class="input-append">
		<input type="text" placeholder="<?php _e('Search', 'largo'); ?>" class="searchbox search-query" value="<?php the_search_query(); ?>" name="digest-search" />
	</div>
	<fieldset id="date-filter" name="date-selectors">
		<label for="after">
			<?php esc_html_e( 'Digests after:', 'enn' ); ?>
			<input
				type="date"
				name="after"
				pattern="\d{4}-\d{2}-\d{2}"
				value="<?php esc_attr_e( $after ); ?>"
			>
			<span class="invalid"><?php esc_html_e( 'Expected format is YYYY-MM-DD', 'enn' ); ?></span>
		</label>
		<label for="before">
			<?php esc_html_e( 'Digests before:', 'enn' ); ?>
			<input
				type="date"
				name="before"
				pattern="\d{4}-\d{2}-\d{2}"
				value="<?php esc_attr_e( $before ); ?>"
			>
			<span class="invalid"><?php esc_html_e( 'Expected format is YYYY-MM-DD', 'enn' ); ?></span>
		</label>
	</fieldset>

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
	<div class="input-append">
		<button type="submit" name="search submit" class="search-submit btn"><?php _e('Search', 'largo'); ?></button>
	</div>
</form>
