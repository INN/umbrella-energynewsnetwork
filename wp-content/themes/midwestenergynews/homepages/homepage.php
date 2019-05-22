<?php

include_once get_template_directory() . '/homepages/homepage-class.php';

/**
 * Define the MWEN homepage Layout
 * @since 0.1
 */
class MWENHomepageLayout extends Homepage {

	function __construct( $options=array() ) {
		$suffix = (LARGO_DEBUG) ? '' : '.min';

		$defaults = array(
			'name' => __( 'Energy News Network Homepage', 'mwen' ),
			'type' => 'mwen',
			'description' => __( 'Homepage layout for Energy News Network', 'mwen' ),
			'template' => get_stylesheet_directory() . '/homepages/templates/mwen_template.php',
			'assets' => array(
				array( 'mwen-homepage', get_stylesheet_directory_uri().'/homepages/assets/css/mwen_homepage' . $suffix . '.css' )
			),
			'prominenceTerms' => array(
				array(
					'name' 			=> __( 'Homepage Top Story', 'largo' ),
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

	function homepage_featured() {
		return zone_homepage_featured();
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
			'description' => __('The Daily Digest List and Signup Widget.', 'midwestenergynews'),
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
 * Prints the post layout for the homepage or the series page
 */
function mwen_print_homepage_posts($query) {
	global $shown_ids;
	$count = 0;

	ob_start();
	while ( $query->have_posts() ) {
		$query->the_post();
		$shown_ids[] = get_the_ID();
		$count++;

		get_template_part( 'partials/content', 'region' );
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

/**
 * The first homepage top story
 *
 * @todo: need to have a fallback here for when a top story is not set
 */
function zone_homepage_top() {
	global $shown_ids;

	// Display the first featured post.
	$bigStoryPost = largo_home_single_top();
	$shown_ids[] = $bigStoryPost->ID; // Don't repeat the current post

	ob_start();

	if ( has_post_thumbnail( $bigStoryPost->ID ) ) { ?>
		<article class="hero">
			<a class="hero-image" href="<?php echo esc_attr(get_permalink($bigStoryPost->ID)); ?>">
				<?php echo get_the_post_thumbnail($bigStoryPost->ID, 'full'); ?>
			</a>
			<header>
				<?php largo_maybe_top_term( array( 'post' => $bigStoryPost->ID ) ); ?>
				<h2><a href="<?php echo get_permalink($bigStoryPost->ID); ?>" class="has-photo"><?php echo $bigStoryPost->post_title; ?></a></h2>
				<?php
					if ( ! empty( $bigStoryPost->post_excerpt ) ) {
						printf(
							'<p class="excerpt">%1$s</p>',
							wp_kses_post( $bigStoryPost->post_excerpt )
						);
					}
				?>
			</header>
		</article>
	<?php } else { ?>
		<article>
			<?php largo_maybe_top_term( array( 'post' => $bigStoryPost->ID ) ); ?>
			<h2><a href="<?php echo get_permalink($bigStoryPost->ID); ?>"><?php echo $bigStoryPost->post_title; ?></a></h2>
			<?php
				largo_byline( true, false, $bigStoryPost->ID );
				if ( ! empty( $bigStoryPost->post_excerpt ) ) {
					printf(
						'<p class="excerpt">%1$s</p>',
						wp_kses_post( $bigStoryPost->post_excerpt )
					);
				}
			?>
		</article>

	<?php }

	dynamic_sidebar('homepage-featured-advert');

	return ob_get_clean();
}

/**
 * The featured template on the homepage
 */
function zone_homepage_featured() {
	global $shown_ids;
	$stories = largo_home_featured_stories( 2 );

	ob_start();

	echo '<div id="featured">';

	foreach ( $stories as $story ) {
		$shown_ids[] = $story->ID;
		setup_postdata( $story );
		?>
			<article class="featured">
				<?php echo get_the_post_thumbnail( $story->ID, 'rect_thumb_half' ); ?>
				<header>
					<?php largo_maybe_top_term( array( 'post' => $story->ID ) ); ?>
					<h2><a href="<?php echo get_permalink( $story->ID ); ?>" class="has-photo"><?php echo $story->post_title; ?></a></h2>
				</header>
			</article>
		<?php
	}
	wp_reset_postdata();

	echo '</div>';
	return ob_get_clean();
}

/**
 * Then the bottom grid on the homepage
 */
function zone_homepage_bottom() {
	global $shown_ids;

	$args = array (
		'posts_per_page'	=> '5',
		'post_type'			=> 'post',
		'tax_query' => array(
			array(
				'taxonomy'	=> 'prominence',
				'field'		=> 'slug',
				'terms'		=> 'homepage-featured'
			),
		),
		'post__not_in'		=> $shown_ids,
	);
	$homepageposts = new WP_Query( $args );

	if ( count( $homepageposts->posts ) < 5 ) {
		$posts_needed = 5 - count( $homepageposts->posts );

		$args = array (
			'posts_per_page'	=> $posts_needed,
			'post_type'			=> 'post',
			'category_name'		=> 'news',
			'post__not_in'		=> $shown_ids,
		);

		$backupposts = new WP_Query( $args );
		$homepageposts->posts = array_merge( $homepageposts->posts, $backupposts->posts );
		$homepageposts->post_count = count( $homepageposts->posts );
		LoadMorePostsHelper::setQuery($backupposts->query);
	} else
		LoadMorePostsHelper::setQuery($homepageposts->query);

	add_action('wp_footer', array('LoadMorePostsHelper', 'printJSON'));

	ob_start();
	print mwen_print_homepage_posts( $homepageposts );
	return ob_get_clean();
}

/**
 * A simple class to help with printing the required JSON object to the page
 * complete with $shown_ids after the markup for the homepage tiles has been
 * printed.
 */
class LoadMorePostsHelper {

	private static $query;

	public static function setQuery($query) {
		self::$query = $query;
	}

	public static function printJSON() {
		global $shown_ids;

		// Account for posts that have already been printed to homepage tiles
		self::$query = array_merge(self::$query, array('post__not_in' => $shown_ids));

		$HPP = array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'paged' => (!empty($wp_query->query_vars['paged']))? $wp_query->query_vars['paged'] : 0,
			'query' => self::$query,
			'is_home' => true
		);
?>
		<script type="text/javascript">
			var HPP = <?php echo json_encode($HPP); ?>;
		</script>
<?php
	}
}
