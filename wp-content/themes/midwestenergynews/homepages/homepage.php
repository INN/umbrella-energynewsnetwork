<?php

include_once get_template_directory() . '/homepages/homepage-class.php';
include_once get_stylesheet_directory() . '/homepages/zones/mwen-zones.php';


/**
 * Define the MWEN homepage Layout
 * @since 0.1
 */
class MWENHomepageLayout extends Homepage {

	function __construct( $options=array() ) {
		$suffix = (LARGO_DEBUG) ? '' : '.min';

		$defaults = array(
			'name' => __( 'Midwest Energy Homepage', 'mwen' ),
			'type' => 'mwen',
			'description' => __( 'Homepage layout for Midwest Energy News', 'mwen' ),
			'template' => get_stylesheet_directory() . '/homepages/templates/mwen_template.php',
			'assets' => array(
				array( 'mwen-homepage', get_stylesheet_directory_uri().'/homepages/assets/css/mwen_homepage' . $suffix . '.css' )
			),
			'prominenceTerms' => array(
				array(
					'name' 			=> __( 'Top Story', 'largo' ),
					'description' 	=> __( 'Add this label to a post to make it the Top Story on the homepage', 'largo' ),
					'slug' 			=> 'top-story'
				),
				array(
					'name' 			=> __( 'Homepage Featured', 'largo' ),
					'description' 	=> __( 'The featured stories in the bottom section of the homepage (empty slots will fill with most recent posts in the news category)', 'largo' ),
					'slug' 			=> 'homepage-featured'
				)
			)
		);

		$options = array_merge( $defaults, $options );
		$this->init();
		$this->load($options);
	}

	function homepage_top() {
		return zone_homepage_top();
	}

	function homepage_bottom() {
		return zone_homepage_bottom();
	}
}


/**
 * Register the widget areas used on the homepage
 *
 * @since 0.1
 */
function mwen_add_widget_areas() {
	$sidebars = array(
		array(
			'name' => 'Homepage Featured Ad Position',
			'id' => 'homepage-featured-advert',
			'description' => __('The Daily Digest List and Signup Widget".', 'midwestenergynews'),
			'before_widget' => '<div class="digest">',
			'after_widget' => '</div>',
			'before_title' => '<h3 class="widgettitle">',
			'after_title' => '</h3>',
		)
	);

	foreach ( $sidebars as $sidebar ) {
		register_sidebar( $sidebar );
	}

	unregister_sidebar( 'homepage-alert' );
}
add_action( 'widgets_init', 'mwen_add_widget_areas' );


/**
 * Register the MWEN homepage layout, unregister Largo ones.
 *
 * @since 0.1
 * @actoin init
 */
function mwen_custom_homepage_layouts() {
	$unregister = array(
		'HomepageSingle',
		'HomepageSingleWithFeatured',
		'HomepageSingleWithSeriesStories',
		'TopStories',
		'Slider',
		'LegacyThreeColumn'
	);

	foreach ( $unregister as $layout )
		unregister_homepage_layout( $layout );

	register_homepage_layout( 'MWENHomepageLayout' );
}
add_action( 'init', 'mwen_custom_homepage_layouts', 10 );

/**
 * Prints the tile/grid markup for the homepage
 *
 * The grid setup is this:
 *
 * div.row
 *     div.span6
 *     div.span6
 * div.row
 *     div.span4
 *     div.span4
 *     div.span4
 *
 * Inside each div is the following markup:
 *
 * article.hg-cell
 *     div.hg-cell-inner
 *         optional thumbnail
 *         headline
 *         byline
 *
 */
function mwen_print_homepage_posts($query) {
	global $shown_ids;
	$count = 0;

	ob_start();
	while ( $query->have_posts() ) {
		$query->the_post();
		$shown_ids[] = get_the_ID();
		$count++;

		$span = ( $count <= 3 ) ? 'span4' : 'span6';

		if ( $count === 1 || $count === 4 ) {
			echo '<div class="hg-row">';
		}
		$image_size =  (( $count >= 4 ) ? 'large' : 'medium' );
	?>

	<div class="<?php echo $span; ?>">
		<article class="hg-cell">
			<div class="hg-cell-inner">
				<!--<h5 class="top-tag"><?php largo_top_term();?></h5>-->
				<?php
					if ( has_post_thumbnail() ) {
						echo '<a href="' . get_permalink() . '" >' . get_the_post_thumbnail( $post->ID, $image_size ) . '</a>';
						echo '<h2 class="has-photo"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
					} else {
						echo '<h2><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
						largo_excerpt( $post->ID, 2 );
					}

					echo '<span class="hg-authors-byline">' . largo_byline() . '</span>';
				?>
			</div>
		</article>
	</div>
	<?php
		if ( $count === 3 || $count === 5 ) {
			echo '</div>'; //end of row;
		}
	} // end loop
	$ret = ob_get_clean();
	return $ret;
}

/**
 * Load More Posts function that is only used on the homepage
 *
 * @uses mwen_print_homepage_posts
 * @since 0.1
 */
function mwen_homepage_load_more_posts() {
	$context = (isset($_POST['query']))? $_POST['query'] : array();
	$is_home = true;

	$args = array_merge(array(
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'post__not_in'        => $context['post__not_in']
	), $context);

	$query = new WP_Query($args);

	if ( count($query->posts) < 5) {
		$posts_needed = 5 - count( $query->posts );

		$args = array(
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'posts_per_page'      => $posts_needed,
			'post_type'           => 'post',
			'category_name'       => 'news',
			'post__not_in'        => $context['post__not_in']
		);

		$backupposts = new WP_Query( $args );
		$query->posts = array_merge( $query->posts, $backupposts->posts );
		$query->post_count = count( $query->posts );
	}

	// "shown" ids to be sent back to the front end
	$ids = array_map(function($x) { return $x->ID; }, $query->posts);

	echo json_encode(array(
		'html' => mwen_print_homepage_posts( $query ),
		'post__not_in' => array_merge($context['post__not_in'], $ids)
	));

	wp_die();
}
add_action( 'wp_ajax_mwen_homepage_load_more_posts', 'mwen_homepage_load_more_posts' );
add_action( 'wp_ajax_nopriv_mwen_homepage_load_more_posts', 'mwen_homepage_load_more_posts');
