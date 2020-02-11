<?php 

/**
 * Add search box to main nav
 */
function largo_component_add_search_box_main_nav() {
	get_template_part( 'partials/largo-component-main-nav-search-form' );
}
add_action( 'largo_after_main_nav_shelf' , 'largo_component_add_search_box_main_nav' );
