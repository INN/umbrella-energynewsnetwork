<?php
/**
 * Modify the Term Debt Consolidator to allow for merging of certain terms
 */

/**
 * Add Largo's default taxonomies to the Term Debt Consolidator allowed taxonomies
 *
 * @param Array $taxonomies An array of taxonomies
 * @return Array
 */
function mwen_migrator_tdc_enabled_taxonomies( $taxonomies ) {
	$taxonomies[] = 'prominence';
	$taxonomies[] = 'lr-tags';
	$taxonomies[] = 'series';

	return $taxonomies;
}
add_filter( 'tdc_enabled_taxonomies', 'mwen_tdc_enabled_taxonomies' );
