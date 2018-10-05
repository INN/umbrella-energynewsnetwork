<?php
/**
 * Template Name: Subscription Landing Page
 * Single Post Template: Subscription Landing Page
 * Description: A full-width template with no sidebars and modified header/footer
 *
 * How's it modified: Here's how:
 * - no links in the footer
 * - no links in the nav
 * - a reduced main navigation
 * - no page title
 * - no sidebar
 *
 * @package Largo
 * @since 2018-10-01
 * @since Largo 0.5.5.4
 * @link https://secure.helpscout.net/conversation/672392044/2538/
 */
?>
<!DOCTYPE html>
<!--[if lt IE 7]> <html <?php language_attributes(); ?> class="no-js ie6"> <![endif]-->
<!--[if IE 7]>    <html <?php language_attributes(); ?> class="no-js ie7"> <![endif]-->
<!--[if IE 8]>    <html <?php language_attributes(); ?> class="no-js ie8"> <![endif]-->
<!--[if IE 9]>    <html <?php language_attributes(); ?> class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html <?php language_attributes(); ?> class="no-js"> <!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
	<title>
		<?php
			global $page, $paged;
			wp_title( '|', true, 'right' );
			bloginfo( 'name' ); // Add the blog name.
			// Add the blog description for the home/front page.
			$site_description = get_bloginfo( 'description', 'display' );
			if ( $site_description && ( is_home() || is_front_page() ) )
				echo " | $site_description";
			// Add a page number if necessary:
			if ( $paged >= 2 || $page >= 2 )
				echo ' | ' . 'Page ' . max( $paged, $page );
		?>
	</title>
	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<?php
		wp_head();
	?>
</head>

<body <?php body_class(); ?>>

	<div id="top"></div>

	<div id="nav-replacement">
		<div class="container">
			Energy News Network
		</div>
	</div>
	<div id="page" class="hfeed clearfix">
		<div id="main" class="row-fluid clearfix">
			<div id="content" class="span12" role="main">
				<?php
					while ( have_posts() ) : the_post();
						get_template_part( 'partials/content', 'subscription-page' );
					endwhile; // end of the loop.
				?>
			</div><!-- #content -->
		</div> <!-- #main -->
	</div><!-- #page -->

	<div class="footer-bg clearfix nocontent">
		<footer id="site-footer">
			<div id="boilerplate" class="footer-credit-padding-inn-logo-missing">
				<div class="row-fluid clearfix">
					<div class="span6">
						<div class="footer-bottom clearfix">
							<p class="footer-credit">The Energy News Network is an editorially independent project of &nbsp;<img src="https://energynews.us/wp-content/uploads/2018/05/FE-logo.png" width="125"><br>
							&copy; Copyright <?php echo date( 'Y' ); ?></p>
							<?php do_action('largo_after_footer_copyright'); ?>
						</div>
					</div>

					<div class="span6 right">
						<p class="footer-credit"><?php echo __('Built with the Largo WordPress Theme from the Institute for Nonprofit News</a>.', 'largo'); ?></p>
					</div>
				</div>

				<p class="back-to-top visuallyhidden"><a href="#top"><?php _e('Back to top', 'largo'); ?> &uarr;</a></p>
			</div>
		</footer>
	</div>

	<?php
		/**
		 * Fires after the Largo footer content.
		 *
		 * @since 0.4
		 */
		do_action( 'largo_after_footer' );

		wp_footer();
	?>

</body>
</html>
