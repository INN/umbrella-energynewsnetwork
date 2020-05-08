<?php
/**
 * Plugin Name: Energy News Network Regions taxonomy
 * Plugin URI:  https://github.com/INN/umbrella-usenergynews/tree/master/wp-content/plugins/usen-region-taxonomy
 * Description: Creates the "region" taxonomy for Energy News Network
 * Version:     0.1.0
 * Author:      innlabs
 * Author URI:  https://labs.inn.org
 * Donate link: https://inn.org/donate/
 * License:     GPLv2
 * Text Domain: usen-region-taxonomy
 * Domain Path: /languages
 *
 * @link    https://labs.inn.org
 *
 * @package USEN_Regions_Taxonomy
 * @version 0.1.0
 *
 * Built using generator-plugin-wp (https://github.com/WebDevStudios/generator-plugin-wp)
 */

/**
 * Copyright (c) 2018 innlabs (email : labs@inn.org)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Main initiation class.
 *
 * @since  0.1.0
 */
final class USEN_Regions_Taxonomy {

	/**
	 * Current version.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	const VERSION = '0.1.0';

	/**
	 * URL of plugin directory.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $path = '';

	/**
	 * Plugin basename.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $basename = '';

	/**
	 * Singleton instance of plugin.
	 *
	 * @var    0.1.0
	 * @since  USEN_Regions_Taxonomy
	 */
	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since   0.1.0
	 * @return  USEN_Regions_Taxonomy A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}
		return self::$single_instance;
	}

	/**
	 * Sets up our plugin.
	 *
	 * @since  0.1.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );
	}

	/**
	 * Add hooks and filters.
	 * Priority needs to be
	 * < 10 for CPT_Core,
	 * < 5 for Taxonomy_Core,
	 * and 0 for Widgets because widgets_init runs at init priority 1.
	 *
	 * @since  0.1.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( $this, 'register_taxonomy' ), 0 );
		add_filter( 'page_rewrite_rules', array( $this, 'rewrite_verbose_page_rules' ), 10, 1 );
		add_filter( 'do_parse_request', array( $this, 'rewrite_verbose_page_rules' ), 10, 1 );
		add_filter( 'post_link', array( $this, 'region_permalink_filter'), 10, 4 );
		add_filter( 'available_permalink_structure_tags', array( $this, 'region_permalink_tag'), 10, 1 );
		add_action( 'init', array( $this, 'url_to_postid_rewrite_rule'), 10 );
		add_filter( 'url_to_postid', array( $this, 'url_to_postid_hack' ), 10, 1 );
	}

	/**
	 * Set the internal state of WP_Rewrite when looking at pages
	 *
	 * This solves https://github.com/INN/umbrella-usenergynews/issues/6
	 *
	 * Before this function, page permalinks were broken by this plugin.
	 *
	 * Solution thanks to Howdy_McGee on Freenode #wordpress
	 *
	 * @link https://wordpress.stackexchange.com/questions/56769/custom-taxonomy-in-permalink-of-post/162670#162670
	 * @param Array $pass_through the active rewrite rules
	 * @return Array the active rewrite rules, unmodified.
	 */
	public function rewrite_verbose_page_rules( $pass_through = null ) {
		global $wp_rewrite;
		$permastruct = $wp_rewrite->permalink_structure;

		$permastruct = trim( $permastruct, '/%' );

		if ( 0 !== strpos( $permastruct, 'region%' ) ) {
			return $pass_through;
		}

		$wp_rewrite->use_verbose_page_rules = true;
		return $pass_through;
	}

	/**
	 * Init hooks
	 *
	 * @since  0.1.0
	 */
	public function init() {
		// Load translated strings for plugin.
		load_plugin_textdomain( 'usen-region-taxonomy', false, dirname( $this->basename ) . '/languages/' );
	}

	/**
	 * The "region" taxonomy
	 *
	 * @since 0.1.0
	 */
	public function register_taxonomy() {
		$labels = array(
			'name' => __( 'Regions' , 'usen-region-taxonomy' ),
			'singular_name' => __( 'Region' , 'usen-region-taxonomy' ),
			'menu_name' => __( 'Regions' , 'usen-region-taxonomy' ),
			'all_items' => __( 'All Regions' , 'usen-region-taxonomy' ),
			'edit_item' => __( 'Edit Region' , 'usen-region-taxonomy' ),
			'view_item' => __( 'View Region' , 'usen-region-taxonomy' ),
			'update_item' => __( 'Update Region' , 'usen-region-taxonomy' ),
			'add_new_item' => __( 'Add New Region' , 'usen-region-taxonomy' ),
			'new_item_name' => __( 'New Region' , 'usen-region-taxonomy' ),
			'parent_item' => __( 'Parent Region' , 'usen-region-taxonomy' ),
			'parent_item_colon' => __( 'Parent Region:' , 'usen-region-taxonomy' ),
			'search_items' => __( 'Search Regions' , 'usen-region-taxonomy' ),
			'popular_items' => __( 'Popular Regions' , 'usen-region-taxonomy' ),
			'separate_items_with_commas' => __( 'Separate regions with commas' , 'usen-region-taxonomy' ),
			'add_or_remove_items' => __( 'Add or remove regions' , 'usen-region-taxonomy' ),
			'choose_from_most_used' => __( 'Choose from the most used regions' , 'usen-region-taxonomy' ),
			'not_found' => __( 'No regions found.' , 'usen-region-taxonomy' ),
		);
		$args = array(
			'public' => true,
			'pubicly_queryable' => true,
			'show_in_rest' => true,
			'show_ui' => true,
			'show_tagcloud' => false,
			'show_admin_column' => false,
			'description' => __( 'A taxonomy of places where Energy News Network provides coverage of', 'usen-region-taxonomy' ),
			'hierarchical' => true,
			'labels' => $labels,
			'query_var' => true,
			'rewrite' => array(
				'slug' => 'region',
				'with_front' => true,
				'hierarchical' => true,
				'ep_mask' => EP_PERMALINK ,
			),
		);
		register_taxonomy( 'region', array( 'post', 'roundup' ), $args );
	}

	/**
	 * Act upon a %region% tag in the permalink structure to add the post's region to the post permalink when generating that link
	 *
	 * This is a filter upon both post_link and post_type_link
	 *
	 * @filter post_link, post_type_link
	 * @link https://shibashake.com/wordpress-theme/add-custom-taxonomy-tags-to-your-wordpress-permalinks
	 * @link https://wisdmlabs.com/blog/add-taxonomy-term-custom-post-permalinks-wordpress/
	 * @param string $post_link The post URL
	 * @param WP_Post $post The post object
	 * @param bool $leavename Whether to keep the post name
	 * @param bool $sample Whether this is a sample permalink
	 * @return string The post permalink
	 */
	public function region_permalink_filter( $post_link, $post, $leavename = false, $sample = false ) {
		if ( 'post' == $post->post_type && false !== strpos( $post_link, '%region%' ) ) {
			$region = get_the_terms( $post->ID, 'region' );
			if ( ! empty( $region ) && ! is_wp_error( $region ) ) {
				$post_link = str_replace( '%region%', array_pop( $region )->slug, $post_link );
			} else {
				$post_link = str_replace( '%region%', 'us', $post_link );
			}
		}
		return $post_link;
	}

	/**
	 * Add the %region% permalink tag to the list of available permalink structure tags
	 *
	 * @link https://developer.wordpress.org/reference/hooks/available_permalink_structure_tags/
	 * @param Array $tags The list of available structure tags
	 */
	public function region_permalink_tag( $tags ) {
		/* translators: %s: permalink structure tag */
		$tags['region'] = __( '%s (Region slug.)' );
		return $tags;
	}

	/**
	 * Create a rewrite rule that will only ever be triggered by the output of url_to_postid_hack filter
	 *
	 * @link https://github.com/INN/umbrella-energynewsnetwork/issues/38
	 * @link https://codex.wordpress.org/Rewrite_API/add_rewrite_rule
	 * @uses WP_Rewrite->add_rewrite_rule
	 * @see $this->url_to_postid_hack
	 * @see url_to_postid
	 */
	public function url_to_postid_rewrite_rule() {
		add_rewrite_rule(
			'regionpost/(.+?)/([^/]+)/?$',
			'index.php?region=$matches[1]&name=$matches[2]',
			'top'
		);
	}

	/**
	 * Mangle the URL fed to url_to_postid if it's a url for a post within a region
	 *
	 * This copies a chunk of url_to_postid so that we're sure that the $url the
	 * surgery is performed upon is the same as in url_to_postid.
	 *
	 * Then, once we're sure, we check to see if the first part of $url matches
	 * the slug of a region taxonomy term. If it does, we then reassemble the URL
	 * in the format that url_to_postid expects, but with `/regionpost` prepended to $url
	 * so that the _only_ valid regular expression in the rewrite rules will be
	 * the one that we defined in $this->url_to_postid_rewrite_rules.
	 *
	 * This is because of https://github.com/INN/umbrella-energynewsnetwork/issues/38,
	 * where the Chalkbeat MORI plugin's call for url_to_postid was breaking when
	 * the permalink structure was causing a mismatch in url_to_postid.
	 *
	 * If you're gonna put a custom taxonomy term in the URL, you want to do something
	 * like this.
	 *
	 * :sigh:
	 *
	 * @param String $url A URL.
	 * @return String A URL, possibly modified to include a leading /regionpost/
	 * @see $this->url_to_postid_rewrite_rule
	 * @see url_to_postid
	 * @link https://github.com/INN/umbrella-energynewsnetwork/issues/38
	 * @uses WP_Rewrite->add_rewrite_rule
	 * @filter url_to_postid
	 */
	public function url_to_postid_hack( $url ) {
		global $wp_rewrite;
		$orig = $url;

		/*
		 * Copied from url_to_postid, but modified to only return the $orig URL that was passed to url_to_postid the function
		 */
		$url_host      = str_replace( 'www.', '', parse_url( $url, PHP_URL_HOST ) );
		$home_url_host = str_replace( 'www.', '', parse_url( home_url(), PHP_URL_HOST ) );

		// Bail early if the URL does not belong to this site.
		if ( $url_host && $url_host !== $home_url_host ) {
			return 0;
		}

		// First, check to see if there is a 'p=N' or 'page_id=N' to match against
		if ( preg_match('#[?&](p|page_id|attachment_id)=(\d+)#', $url, $values) )	{
			$id = absint($values[2]);
			if ( $id )
				return $orig;
		}

		// Get rid of the #anchor
		$url_split = explode('#', $url);
		$url = $url_split[0];

		// Get rid of URL ?query=string
		$url_split = explode('?', $url);
		$url = $url_split[0];

		// Set the correct URL scheme.
		$scheme = parse_url( home_url(), PHP_URL_SCHEME );
		$url = set_url_scheme( $url, $scheme );

		// Add 'www.' if it is absent and should be there
		if ( false !== strpos(home_url(), '://www.') && false === strpos($url, '://www.') )
			$url = str_replace('://', '://www.', $url);

		// Strip 'www.' if it is present and shouldn't be
		if ( false === strpos(home_url(), '://www.') )
			$url = str_replace('://www.', '://', $url);

		if ( trim( $url, '/' ) === home_url() && 'page' == get_option( 'show_on_front' ) ) {
			$page_on_front = get_option( 'page_on_front' );

			if ( $page_on_front && get_post( $page_on_front ) instanceof WP_Post ) {
				return $orig;
			}
		}

		// Check to see if we are using rewrite rules
		$rewrite = $wp_rewrite->wp_rewrite_rules();

		// Not using rewrite rules, and 'p=N' and 'page_id=N' methods failed, so we're out of options
		if ( empty($rewrite) ) {
			return 0;
		}

		// Strip 'index.php/' if we're not using path info permalinks
		if ( !$wp_rewrite->using_index_permalinks() )
			$url = str_replace( $wp_rewrite->index . '/', '', $url );

		if ( false !== strpos( trailingslashit( $url ), home_url( '/' ) ) ) {
			// Chop off http://domain.com/[path]
			$url = str_replace(home_url(), '', $url);
		} else {
			// Chop off /path/to/blog
			$home_path = parse_url( home_url( '/' ) );
			$home_path = isset( $home_path['path'] ) ? $home_path['path'] : '' ;
			$url = preg_replace( sprintf( '#^%s#', preg_quote( $home_path ) ), '', trailingslashit( $url ) );
		}

		// Trim leading and lagging slashes
		$url = trim($url, '/');

		/*
		 * end copy
		 */

		// Get a list of region slugs
		$terms = get_terms( array(
			'taxonomy' => 'region',
			//'hide_empty' => false, // removed to speed up the query
			'fields' => 'id=>slug', // see https://developer.wordpress.org/reference/classes/wp_term_query/__construct/
		) );

		foreach ( $terms as $id => $slug ) {
			if ( 0 === strpos( $url, $slug ) ) {
				return home_url() . '/regionpost/' . $url;
			}
		}

		// Nope, it wasn't a region post.
		return $orig;
	}
}

/**
 * Grab the USEN_Regions_Taxonomy object and return it.
 * Wrapper for USEN_Regions_Taxonomy::get_instance().
 *
 * @since  0.1.0
 * @return USEN_Regions_Taxonomy  Singleton instance of plugin class.
 */
function usen_region_taxonomy() {
	return USEN_Regions_Taxonomy::get_instance();
}

// Kick it off.
add_action( 'plugins_loaded', array( usen_region_taxonomy(), 'hooks' ) );
