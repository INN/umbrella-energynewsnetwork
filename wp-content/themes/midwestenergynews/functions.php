<?php
/**
 * Custom functions for Midwest Energy News
 */


/**
 * Hide the global nav bar
 */
define( 'SHOW_GLOBAL_NAV', FALSE );

/**
 * Includes
 */
$includes = array(
	'/inc/widgets.php',
	'/inc/post-tags.php',
	'/inc/header-footer.php',
	'/inc/load-more-posts.php',
	'/inc/newsletter-shortcode.php',
	'/inc/term-debt-consolidator.php',
	'/inc/regions.php',
	'/homepages/homepage.php',
);
foreach ( $includes as $include ) {
	require_once( get_stylesheet_directory() . $include );
}

/**
 * Include compiled style.css and homepage custom LMP
 *
 * @since 0.1
 * @see mwen_print_homepage_posts
 * @see mwen_homepage_load_more_posts
 */
function mwen_stylesheet() {
	wp_dequeue_style( 'largo-child-styles' );

	$suffix = (LARGO_DEBUG)? '' : '.min';
	wp_enqueue_style(
		'mwen',
		get_stylesheet_directory_uri().'/css/style' . $suffix . '.css',
		array(),
		filemtime( get_stylesheet_directory() . '/css/style.css' )
	);

	if (is_home()) {
		wp_enqueue_script(
			'mwen-homepage',
			get_stylesheet_directory_uri().'/homepages/assets/js/loadmore.js',
			array( 'jquery' )
		);
		wp_dequeue_script('load-more-posts');
	}

	// Typekit Energy News kit
	wp_enqueue_style(
		'typekit',
		'https://use.typekit.net/xre8nwm.css'
	);
}
add_action( 'wp_enqueue_scripts', 'mwen_stylesheet', 20 );


/**
 * Add profile fields to user pages.
 */
function mwen_add_user_profile_fields($context, $slug, $name) {
	if ( $slug == 'partials/author-bio' && $name == 'description' ) {
		$user = $context['author_obj'];

		$context = array_merge( $context, array(
			'job_title' => get_user_meta($user->ID, 'job_title', true)
		) );
	}
	return $context;
}
add_filter( 'largo_render_template_context', 'mwen_add_user_profile_fields', 10, 3 );

/**
 * Remove image size attributes from post thumbnails and in the editor.
 */
function remove_width( $html ) {
   $html = preg_replace( '/(width|height)="\d*"\s/', "", $html );
   return $html;
}
add_filter( 'post_thumbnail_html', 'remove_width', 10 );
add_filter( 'image_send_to_editor', 'remove_width', 10 );

/**
 * oembed is a pain; this fixes fixed widths for responsive output
 *
 * Wraps embeds in a container div.
 */
function mwe_responsive_embed($html, $url, $attr, $post_ID) {
	$return = '<div class="video-container">'.$html.'</div>'; return $return;
}
add_filter( 'embed_oembed_html', 'mwe_responsive_embed', 10, 4 );

/**
 * Change 'Load more posts' button text to 'More posts'
 */
function mwen_next_posts_link($link) {
  return str_replace('Load more posts', 'More posts', $link);
}
add_filter('largo_next_posts_link', 'mwen_next_posts_link');

/**
 * Remove the top image from posts pre-migration.
 *
 * This is similar to `largo_remove_hero`, and derives from it, but is different.
 */
function mwen_remove_feature_img($content) {

	global $post;

	$migrationDate = '07/01/2015';

	// 1: Only worry about this if it's a single template, there's a feature image,
	// the post was migrated and we haven't overridden the post display.
	if( !is_single() )
		return $content;
	if( get_post_time('U',true) > strtotime($migrationDate) )
		return $content;
	if( !has_post_thumbnail() )
		return $content;
	$options = get_post_custom($post->ID);
	if( isset($options['featured-image-display'][0]) )
		return $content;

	$p = explode("\n",$content);

	// 2: Find an image (regex)
	//
	// Creates the array:
	// 		$matches[0] = <img src="..." class="..." id="..." />
	//		$matches[1] = value of src.
	$pattern = '/<img\s+[^>]*src="([^"]*)"[^>]*>/';
	$hasImg = preg_match($pattern,$p[0],$matches);

	// 3: if there's no image, there's nothing to worry about.
	if( !$hasImg )
		return $content;
	$imgDom = $matches[0];
	$src = $matches[1];

	// 4: Else, shift the first paragraph off the content and return.
	array_shift($p);
	$content = implode("\n",$p);

	return $content;

}
add_filter('the_content','mwen_remove_feature_img',1);


/**
 * Add a "Sponsored: " prefix to Saved Links that have the "sponsored" class
 */
function mwen_saved_link_sponsored_prefix($title, $post, $link_class) {
	$classes = explode(' ', trim($link_class));
	if (in_array('sponsored', $classes))
		$title = '<span class="sponsored-label">Sponsored:</span> ' . $title;
	return $title;
}
add_filter('lroundups_link_title', 'mwen_saved_link_sponsored_prefix', 10, 3);

/**
 * Override the default Largo header javascript with a function that returns the header SVG instead of different images
 *
 * @see Largo/inc/enqueue.php
 */
function largo_header_js() {
	$svg = of_get_option('header_svg', get_stylesheet_directory_uri() . '/images/logo-dark.svg' );

	if ( ! empty($svg) ) {
	?>
	<script type="text/javascript">
		function whichHeader() {
			return '<?php echo $svg; ?>';
		}
		var banner_img_src = whichHeader();</script>
	<?php
	} else {
	// The following code is copied outright from Largo/inc/enqueue.php's largo_header_js.
	?>
		<script>
			function whichHeader() {
				var screenWidth = document.documentElement.clientWidth,
				header_img;
				if (screenWidth <= 767) {
					header_img = '<?php echo of_get_option( 'banner_image_sm' ); ?>';
				} else if (screenWidth > 767 && screenWidth <= 979) {
					header_img = '<?php echo of_get_option( 'banner_image_med' ); ?>';
				} else {
					header_img = '<?php echo of_get_option( 'banner_image_lg' ); ?>';
				}
				return header_img;
			}
			var banner_img_src = whichHeader();
		</script>
	<?php

	}
}

/**
 * Add a new page to the theme options
 *
 * @filter mwen_theme_options
 * @see largo_header_js
 * @since 0.1.1
 */
function mwen_theme_options($options) {
	$options[] = array(
		'name' => __('Child Theme Options', 'largo'),
		'type' => 'heading'
	);

	$options[] = array(
		'name' => __('Header Image SVG'),
		'desc' => __('Enter the URL for the SVG that is to be used in the header. This overrides Largo\'s header option if it is set.', 'largo'),
		'id' => 'header_svg',
		'std' => get_stylesheet_directory_uri() . '/images/logo-dark.svg',
		'type' => 'text'
	);

	return $options;
}
add_filter('largo_options', 'mwen_theme_options');

/**
 * close comments for posts of the roundup type
 *
 * @filter close_comments_for_post_types
 */
function mwen_comments_roundups( $value ) {
    if ( ! in_array( 'roundup', $value ) ) {
        array_push( $value, 'roundup' );
    }
    return $value;
}
add_filter( 'close_comments_for_post_types', 'mwen_comments_roundups' );

/**
 * exclude Roundups from Regions loop
 *
 * This should _only_ run on the regions page, and not anywhere else
 *
 * @param query WP_Query the query that may be about to be run
 * @return WP_Query the query
 * @since Largo 0.5.5.4
 * @since WordPress 4.9.2
 */
function lmp_exclude_roundups( $query ) {

	/*
	 * make it happen when loading the page
	 */
	if ( ! is_admin() && $query->is_tax('region') && $query->is_main_query() ) {
		$query->set( 'post_type', array('post') );
	}

	/*
	 * make it happen for Load More Posts
	 *
	 * Note that is_admin may be true while running LMP
	 * and is_admin is true when updating a post or updating a term meta
	 * so we cannot simply allow or disallow based on is_admin
	 */
	if ( isset( $_POST ) && 'load_more_posts' === $_POST['action'] && $query->is_tax('region') ) {
		$query->set( 'post_type', array('post') );
	}
	return $query;
}
add_action( 'pre_get_posts', 'lmp_exclude_roundups' );

/**
 * Add new image size
 *
 * @todo Remove this when https://github.com/INN/largo/pull/1584 has been merged
 */
function mwen_image_sizes() {
	add_image_size( 'rect_thumb_half', 400, 300, true ); // smaller version of rect_thumb
}
add_action( 'after_setup_theme', 'mwen_image_sizes' );
