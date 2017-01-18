<?php

function mwen_widgets() {
	$register = array(
		'mwen_mailchimp_signup_widget'	=> '/inc/widgets/mwen-mailchimp.php',
		'mwen_roundups_widget'	=> '/inc/widgets/mwen-roundups.php',
		'seen_roundups_widget'	=> '/inc/widgets/seen-link-roundups-widget.php'
	);
	foreach ( $register as $key => $val ) {
		require_once( get_stylesheet_directory() . $val );
		register_widget( $key );
	}
}
add_action( 'widgets_init', 'mwen_widgets' );
