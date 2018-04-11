<?php
/**
 * Creates the homepage grid
 */

/**
 * First the homepage top story
 *
 * @todo: need to have a fallback here for when a top story is not set
 */
function zone_homepage_top() {
	global $shown_ids;

	// Display the first featured post.
	$bigStoryPost = largo_home_single_top();
	$shown_ids[] = $bigStoryPost->ID; // Don't repeat the current post

	ob_start();

    dynamic_sidebar('homepage-featured-advert');

	if ( has_post_thumbnail( $bigStoryPost->ID ) ) { ?>
		<article class="hero">
			<a href="<?php echo esc_attr(get_permalink($bigStoryPost->ID)); ?>"><?php echo get_the_post_thumbnail($bigStoryPost->ID, 'full'); ?></a>
			<header>
				<h2><a href="<?php echo get_permalink($bigStoryPost->ID); ?>" class="has-photo"><?php echo $bigStoryPost->post_title; ?></a></h2>
				<?php
					largo_byline( true, false, $bigStoryPost->ID );
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
			<h5 class="top-tag">Featured</h5>
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

	return ob_get_clean();
}

/**
 * Then the bottom grid
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
