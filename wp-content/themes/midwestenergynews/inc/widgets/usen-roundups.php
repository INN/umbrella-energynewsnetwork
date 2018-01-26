<?php
/**
 * The widget's file
 */

/**
 * Energy News Roundups Widget
 * Differs from the standard Link Roundups widget in its opinions: One post each from two different Roundups regions, optionally limited by region.
 */
class USEN_Roundups_Widget extends WP_Widget {

	/**
	 * Constructor; extends WP_Widget's constructor
	 *
	 * @uses parent::__construct
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'usen-roundups-widget',
			'description' => __( 'Show two roundups from two different regions.', 'midwestenergynews' ),
		);
		parent::__construct( 'usen-roundups-widget', 'Energy News Roundups Widget', $widget_ops );
	}

	/**
	 * Output the widget
	 *
	 * @param array $args The sidebar arguments.
	 * @param array $instance This sidebar's instance information.
	 */
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];

		if ( $title ) {
			echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
		}

		/*
		 * Assemble the query
		 */
		$query1_args = array(
			'post__not_in' => get_option( 'sticky_posts' ),
			'excerpt'      => $instance['show_excerpt'],
			'post_type'    => 'roundup',
			'post_status'  => 'publish',
		);

		if ( '' !== $instance['num_posts1'] ) {
			$query1_args['posts_per_page'] = $instance['num_posts1'];
		}
		if ( isset( $instance['cat1'] ) && 0 !== $instance['cat1'] ) {
			$query1_args['tax_query'][] = array(
				array(
					'taxonomy' => 'category',
					'field'    => 'id',
					'terms'    => $instance['cat1'],
				),
			);
		}
		if ( isset( $instance['region1'] ) && 0 !== $instance['region1'] ) {
			$query1_args['tax_query'][] = array(
				array(
					'taxonomy' => 'region',
					'field'    => 'id',
					'terms'    => $instance['region1'],
				),
			);
		}

		$query_args = array( $query1_args );

		/*
		 * For each query in the list of queries, run the Loop and output the posts.
		 * This loop is left over from when this widget was copied from the MWEN Link Roundups Widget.
		 */
		foreach ( $query_args as $qa ) {

			// if the number of posts to display is 0, don't bother running the query or doing output.
			if ( 0 === $qa['posts_per_page'] || ! isset( $qa['posts_per_page'] ) ) {
				continue;
			}

			$output = '';

			$my_query = new WP_Query( $qa );

			if ( $my_query->have_posts() ) {
				while ( $my_query->have_posts() ) {
					$my_query->the_post();
					global $post;
					?>
					<div <?php post_class( 'post-lead clearfix' ); ?> data-id="<?php echo esc_attr( $post->ID ); ?>">
						<h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
						<?php
							$excerpt = get_the_excerpt();
							if ( ! empty ( $excerpt ) ) {
								echo apply_filters( 'the_excerpt', $excerpt );
							}
						?>
					</div> <!-- /.post-lead -->
					<?php
				}
			} else {
				echo wp_kses_post( sprintf(
					'<p class="error"><strong>%1$s</strong></p>',
					__( 'No digests have yet been published for this region.', 'midwestenergynews' )
				) );
			} // end this group's recent link
		} // End foreach of groups

		// output the optional link.
		if ( '' !== $instance['linkurl'] && '' !== $instance['linktext'] ) {
			echo wp_kses_post( sprintf(
				'<p class="morelink"><a href="%1$s">%2$s</a></p>',
				esc_attr( $instance['linkurl'] ),
				wp_kses_post( $instance['linktext'] )
			) ) ;
		}

		echo $args['after_widget'];
	}

	/**
	 * How to save everything
	 *
	 * @param Array $new_instance the new instance arguments.
	 * @param Array $old_instance the previously-saved instance arguments.
	 * @return Array the validated and cleaned new instance arguments
	 */
	public function update( $new_instance, $old_instance ) {
		$instance               = $old_instance;
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['linktext']   = $new_instance['linktext'];
		$instance['linkurl']    = $new_instance['linkurl'];

		$instance['region1']    = intval( $new_instance['region1'] );
		$instance['cat1']       = intval( $new_instance['cat1'] );
		$instance['num_posts1'] = intval( $new_instance['num_posts1'] );

		return $instance;
	}

	/**
	 * Output the widget's form
	 *
	 * @param Array $instance The widget's instance settings
	 */
	public function form( $instance ) {
		$defaults = array(
			'title'    => 'Recent Link Roundups',
			'linktext' => '',
			'linkurl'  => '',
			'cat1'     => 0,
			'region1'  => 0,
			'num_posts1' => 1,
		);

		$instance = wp_parse_args( (array) $instance, $defaults );
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php echo esc_html( 'Title:', 'argo-links' ); ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" style="width:90%;" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'num_posts1' ) ); ?>"><?php esc_html_e( 'Number of posts to show:', 'argo-links' ); ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'num_posts1' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'num_posts1' ) ); ?>" value="<?php echo esc_attr( $instance['num_posts1'] ); ?>" type="number" style="width:100px;" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'region1' ) ); ?>">
			<?php
				esc_html_e( 'Region 1: ', 'midwestenergynews' );
				wp_dropdown_categories( array(
					'name'            => $this->get_field_name( 'region1' ),
					'show_option_all' => __( 'None (all regions)', 'midwestenergynews' ),
					'hide_empty'      => 0,
					'hierarchical'    => 1,
					'taxonomy'        => 'region',
					'selected'        => $instance['region1'],
				) );
			?>
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'cat1' ) ); ?>">
			<?php
				esc_html_e( 'Category 1: ', 'midwestenergynews' );
				wp_dropdown_categories( array(
					'name'            => $this->get_field_name( 'cat1' ),
					'show_option_all' => __( 'None (all categories)', 'largo' ),
					'hide_empty'      => 0,
					'hierarchical'    => 1,
					'selected'        => $instance['cat1'],
				) );
			?>
			</label>
		</p>

		<p><strong><?php esc_html_e( 'More Link:', 'argo-links' ); ?> </strong><?php esc_html_e('If you would like to add a more link at the bottom of the widget, add the link text and url here.', 'argo-links'); ?></p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id('linktext') ); ?>">
			<?php
				esc_html_e('Link text:', 'argo-links');
			?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'linktext' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'linktext' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['linktext'] ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'linkurl' ) ); ?>"><?php esc_html_e( 'URL:', 'argo-links' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id('linkurl') ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'linkurl' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['linkurl'] ); ?>" />
		</p>

	<?php
	}
}
