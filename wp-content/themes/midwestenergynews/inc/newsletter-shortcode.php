<?php
/**
 * newsletter signup form
 */
function mwen_newsletter_signup($attrs=null) {
	( !empty($attrs) && array_search( 'reverse', $attrs ) !== false ) ? $reverse = false : $reverse = true ;
	$attrs = shortcode_atts(
		array(
			'title' => '',
			'reverse' => $reverse,
		),
		$attrs
	);
	ob_start();
	the_widget('mwen_mailchimp_signup_widget', $attrs);
	return ob_get_clean();
}
add_shortcode( 'newsletter_signup', 'mwen_newsletter_signup' );
