<?php

function mwen_widgets() {
	$register = array(
		'mwen_mailchimp_signup_widget'	=> '/inc/widgets/mwen-mailchimp.php',
		'mwen_roundups_widget'	=> '/inc/widgets/mwen-roundups.php',
	);

	// this is meaningless if the USEN Regions taxonomy is not available
	if ( class_exists( 'USEN_Regions_Taxonomy' ) && taxonomy_exists( 'region' ) ) {
		$register['USEN_Roundups_Widget'] = '/inc/widgets/usen-roundups.php';
	}

	foreach ( $register as $key => $val ) {
		require_once( get_stylesheet_directory() . $val );
		register_widget( $key );
	}
}
add_action( 'widgets_init', 'mwen_widgets' );
