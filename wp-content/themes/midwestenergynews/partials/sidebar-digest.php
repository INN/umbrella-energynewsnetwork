<?php
/*
 * For the digest category page
 *
 * Copied from Largo's partials/sidebar-archive.php at 0.6.4
 * @package Largo
 * @since 0.6.4
 * @link https://github.com/INN/umbrella-energynewsnetwork/issues/86
 */
$custom_sidebar = largo_get_custom_sidebar();
if ( !dynamic_sidebar( 'digest-sidebar' ) ) { // try custom sidebar registered in functions.php
	// do nothing.
}
