<?php
/**
 * Energy News Roundups Widget
 * Differs from the standard Link Roundups widget in its opinions: One post each from two different Roundups regions, optionally limited by region.
 */
class usen_roundups_widget extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'classname' => 'usen-roundups-widget',
			'description' => 'Show two roundups from two different regions.', 'midwestenergynews'
		);
		parent::__construct( 'usen-roundups-widget','Energy News Roundups Widget', $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title'] );

		echo $before_widget;

		if ( $title )
			echo $before_title . $title . $after_title;

			echo "<p class='roundup-date'><datetime>" . date("F j, Y") . "</datetime></p>"; ?>

			<?php
			$query1_args = $query2_args = array (
				'post__not_in' 	=> get_option( 'sticky_posts' ),
				'excerpt'	=> $instance['show_excerpt'],
				'post_type' 	=> 'roundup',
				'post_status'	=> 'publish'
			);

			// 1
			if ( $instance['num_posts1'] != '' ) $query1_args['posts_per_page'] = $instance['num_posts1'];
			if ( isset( $instance['cat1'] ) ) $query1_args['tax_query'][] = array(
				array(
					'taxonomy' => 'category',
					'field' => 'id',
					'terms' => $instance['cat1'],
				)
			);
			if ( isset( $instance['region1'] ) ) $query1_args['tax_query'][] = array(
				array(
					'taxonomy' => 'region',
					'field' => 'id',
					'terms' => $instance['region1'],
				)
			);

			// 2
			if ( $instance['num_posts2'] != '' ) $query2_args['posts_per_page'] = $instance['num_posts2'];
			if ( isset( $instance['cat2'] ) ) $query2_args['tax_query'][] = array(
				array(
					'taxonomy' => 'category',
					'field' => 'id',
					'terms' => $instance['cat2'],
				)
			);
			if ( isset( $instance['region2'] ) ) $query2_args['tax_query'][] = array(
				array(
					'taxonomy' => 'region',
					'field' => 'id',
					'terms' => $instance['region2'],
				)
			);

			$query_args = array( $query1_args, $query2_args );

			foreach ( $query_args as $index => $args ) {

				// if the number of posts to display is 0, don't bother running the query or doing output
				if ( 0 === $args['posts_per_page'] || ! isset( $args['posts_per_page'] ) ) {
					continue;
				}

				$output = '';

				$index++; // php arrays start at 0 and the numbers start at 1

				// the title
				echo sprintf(
					'<h4>%1$s</h4>',
					esc_html( $instance['title' . $index] )
				);

				$my_query = new WP_Query( $args );
				if ( $my_query->have_posts() ) {
					while ( $my_query->have_posts() ) {
						$my_query->the_post();
						$custom = get_post_custom($post->ID);
	?>
						<div class="post-lead clearfix">
							<h5 class="top-tag"><a href="<?php echo get_category_link($args['cat']); ?>"><?php echo get_cat_name($args['cat']); ?></a></h5>
							<h4><a href="<?php echo get_permalink(); ?>"><?php echo get_the_title(); ?></a></h4>
						</div> <!-- /.post-lead -->
	<?php
					}
				} else {
					_e('<p class="error"><strong>You don\'t have any recent links or the argo links plugin is not active.</strong></p>', 'argo-links');
				} // end this group's recent link
			} // End foreach of groups

			if ( $instance['linkurl'] !='' ) { ?>
				<p class="morelink"><a href="<?php echo $instance['linkurl']; ?>"><?php echo $instance['linktext']; ?></a></p>
			<?php }
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['linktext'] = $new_instance['linktext'];
		$instance['linkurl'] = $new_instance['linkurl'];

		$instance['title1'] = strip_tags( $new_instance['title1'] );
		$instance['region1'] = intval( $new_instance['region1'] );
		$instance['cat1'] = intval( $new_instance['cat1'] );
		$instance['num_posts1'] = intval( $new_instance['num_posts1'] );

		$instance['title2'] = strip_tags( $new_instance['title2'] );
		$instance['region2'] = intval( $new_instance['region2'] );
		$instance['cat2'] = intval( $new_instance['cat2'] );
		$instance['num_posts2'] = intval( $new_instance['num_posts2'] );

		return $instance;
	}

	function form( $instance ) {
		$defaults = array(
			'title' => 'Recent Link Roundups',
			'linktext' => '',
			'linkurl' => '',
			'cat1' => 0,
			'cat2' => 0,
			'region1' => 0,
			'region2' => 0,
			'title1' => '',
			'title2' => '',
			'num_posts1' => 1,
			'num_posts2' => 1,
		);

		$instance = wp_parse_args( (array) $instance, $defaults );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'argo-links'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:90%;" />
		</p>

		<h4><?php _e( 'First roundups group', 'argo-links' ); ?></h4>
		<p>
			<label for="<?php echo $this->get_field_id( 'title1' ); ?>"><?php _e('Title:', 'argo-links'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title1' ); ?>" name="<?php echo $this->get_field_name( 'title1' ); ?>" value="<?php echo $instance['title1']; ?>" style="width:90%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'num_posts1' ); ?>"><?php _e('Number of posts to show:', 'argo-links'); ?></label>
			<input id="<?php echo $this->get_field_id( 'num_posts1' ); ?>" name="<?php echo $this->get_field_name( 'num_posts1' ); ?>" value="<?php echo $instance['num_posts1']; ?>" type="number" style="width:100px;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('region1'); ?>">
				<?php _e('Region 1: ', 'midwestenergynews'); ?>
				<?php wp_dropdown_categories(array(
					'name' => $this->get_field_name('region1'),
					'show_option_all' => __('None (all regions)', 'midwestenergynews'),
					'hide_empty'=>0,
					'hierarchical'=>1,
					'taxonomy' => 'region',
					'selected'=>$instance['region1']
				)); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('cat1'); ?>"><?php _e('Category 1: ', 'largo'); ?>
			<?php wp_dropdown_categories(array('name' => $this->get_field_name('cat1'), 'show_option_all' => __('None (all categories)', 'largo'), 'hide_empty'=>0, 'hierarchical'=>1, 'selected'=>$instance['cat1'])); ?></label>
		</p>

		<h4><?php _e( 'Second roundups group', 'argo-links' ); ?></h4>
		<p>
			<label for="<?php echo $this->get_field_id( 'title2' ); ?>"><?php _e('Title:', 'argo-links'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title2' ); ?>" name="<?php echo $this->get_field_name( 'title2' ); ?>" value="<?php echo $instance['title2']; ?>" style="width:90%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'num_posts2' ); ?>"><?php _e('Number of posts to show:', 'argo-links'); ?></label>
			<input id="<?php echo $this->get_field_id( 'num_posts2' ); ?>" name="<?php echo $this->get_field_name( 'num_posts2' ); ?>" value="<?php echo $instance['num_posts2']; ?>" type="number" style="width:100px;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('region2'); ?>">
				<?php _e('Region 2: ', 'midwestenergynews'); ?>
				<?php wp_dropdown_categories(array(
					'name' => $this->get_field_name('region2'),
					'show_option_all' => __('None (all regions)', 'midwestenergynews'),
					'hide_empty'=>0,
					'hierarchical'=>1,
					'taxonomy' => 'region',
					'selected'=>$instance['region2']
				)); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('cat2'); ?>"><?php _e('Category 2: ', 'largo'); ?>
			<?php wp_dropdown_categories(array('name' => $this->get_field_name('cat2'), 'show_option_all' => __('None (all categories)', 'largo'), 'hide_empty'=>0, 'hierarchical'=>1, 'selected'=>$instance['cat2'])); ?></label>
		</p>

		<h4><?php _e( 'More Link', 'argo-links' ); ?></h4>
		<p><small><?php _e('If you would like to add a more link at the bottom of the widget, add the link text and url here.', 'argo-links'); ?></small></p>
		<p>
			<label for="<?php echo $this->get_field_id('linktext'); ?>"><?php _e('Link text:', 'argo-links'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('linktext'); ?>" name="<?php echo $this->get_field_name('linktext'); ?>" type="text" value="<?php echo $instance['linktext']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('linkurl'); ?>"><?php _e('URL:', 'argo-links'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('linkurl'); ?>" name="<?php echo $this->get_field_name('linkurl'); ?>" type="text" value="<?php echo $instance['linkurl']; ?>" />
		</p>

	<?php
	}
}
