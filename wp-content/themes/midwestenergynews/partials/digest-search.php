<?php
/**
 * Search box for the digest template
 */
?>
<h1>Search</h1>

<form class="digest-search form-search" role="search" method="get" action="<?php echo esc_url( get_term_link( get_queried_object())); ?>">
	<div class="input-append">
		<input type="text" placeholder="<?php _e('Search', 'largo'); ?>" class="searchbox search-query" value="<?php the_search_query(); ?>" name="digest-search" /><button type="submit" name="search submit" class="search-submit btn"><?php _e('Go', 'largo'); ?></button>
	</div>

	<fieldset id="filter-buttons">
		<legend>Select a region:</legend>
		<?php
			$regions = get_terms(
				array(
					'taxonomy' => 'region',
					'hide_empty' => 'true',
				),
			);
			foreach ( $regions as $region ) {
				printf(
					'<label class="checkbox"><input name="region" type="checkbox" class="term-%1$s btn" value="%2$s">%3$s</label>',
					esc_attr( $region->term_id ),
					esc_attr( $region->slug ),
					esc_html( $region->name )
				);
			}

		?>
	</fieldset>
</form>
