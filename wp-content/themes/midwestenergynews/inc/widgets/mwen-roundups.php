<?php
/**
 * MWEN Roundups Widget
 * Differs from the standard Link Roundups widget in its opinions: One post each from two different Roundups categories.
 */
class mwen_roundups_widget extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'classname' => 'mwen-roundups-widget',
			'description' => 'Show two roundups from two different categories.', 'mwen'
		);
		parent::__construct('mwen-roundups-widget','MWEN Roundups Widget', $widget_ops);
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
				'showposts' 	=> $instance['num_posts'],
				'exceprt'	=> $instance['show_excerpt'],
				'post_type' 	=> 'roundup',
				'post_status'	=> 'publish'
			);

			if ( $instance['cat1'] != '' ) $query1_args['cat'] = $instance['cat1'];
			if ( $instance['cat2'] != '' ) $query2_args['cat'] = $instance['cat2'];

			$query_args = array($query1_args,$query2_args);
			// Largo has a global $shown_ids; we're not using it here. This doesn't access that global.
			$shown_ids = array();

			foreach( $query_args as $args ) {

				// ignore shown ids
				$my_query = new WP_Query( array_merge( $args, array( 'post__not_in' => $shown_ids ) ) );

				if ( $my_query->have_posts() ) {
					while ( $my_query->have_posts() ) {
						$my_query->the_post();
						global $post;
						?>
						<div <?php post_class( 'post-lead clearfix' ); ?> data-id="<?php echo esc_attr( $post->ID ); ?>">
							<h5 class="top-tag"><a href="<?php echo get_category_link($args['cat']); ?>"><?php echo get_cat_name($args['cat']); ?></a></h5>
							<h4><a href="<?php echo get_permalink(); ?>"><?php echo get_the_title(); ?></a></h4>
						</div>
						<?php
						// add the shown ID to the query args
						$shown_ids[] = $post->ID;
					}
					wp_reset_postdata();

				} else {
					_e('<p class="error"><strong>You don\'t have any recent links or the argo links plugin is not active.</strong></p>', 'argo-links');
				} // end this category's recent link
			} // End foreach of categories

			if ( $instance['linkurl'] !='' ) { ?>
				<p class="morelink"><a href="<?php echo $instance['linkurl']; ?>"><?php echo $instance['linktext']; ?></a></p>
			<?php }
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['num_posts'] = strip_tags( $new_instance['num_posts'] );
		$instance['linktext'] = $new_instance['linktext'];
		$instance['linkurl'] = $new_instance['linkurl'];
		$instance['cat1'] = intval( $new_instance['cat1'] );
		$instance['cat2'] = intval( $new_instance['cat2'] );
		return $instance;
	}

	function form( $instance ) {
		$defaults = array(
			'title' => 'Recent Link Roundups',
			'num_posts' => 1,
			'linktext' => '',
			'linkurl' => '',
			'cat1' => 0,
			'cat2' => 0,
		);

		$instance = wp_parse_args( (array) $instance, $defaults );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'argo-links'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:90%;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'num_posts' ); ?>"><?php _e('Number of posts to show:', 'argo-links'); ?></label>
			<input id="<?php echo $this->get_field_id( 'num_posts' ); ?>" name="<?php echo $this->get_field_name( 'num_posts' ); ?>" value="<?php echo $instance['num_posts']; ?>" style="width:90%;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('cat1'); ?>"><?php _e('Category 1: ', 'largo'); ?>
			<?php wp_dropdown_categories(array('name' => $this->get_field_name('cat1'), 'show_option_all' => __('None (all categories)', 'largo'), 'hide_empty'=>0, 'hierarchical'=>1, 'selected'=>$instance['cat1'])); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('cat2'); ?>"><?php _e('Category 2: ', 'largo'); ?>
			<?php wp_dropdown_categories(array('name' => $this->get_field_name('cat2'), 'show_option_all' => __('None (all categories)', 'largo'), 'hide_empty'=>0, 'hierarchical'=>1, 'selected'=>$instance['cat2'])); ?></label>
		</p>

		<p><strong>More Link</strong><br /><small><?php _e('If you would like to add a more link at the bottom of the widget, add the link text and url here.', 'argo-links'); ?></small></p>
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
