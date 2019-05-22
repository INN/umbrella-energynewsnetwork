<?php
$image_size = 'rect_thumb';

?>
	<article <?php post_class( 'clearfix', $post ); ?> >
		<?php
			if ( has_post_thumbnail() ) {
				echo '<a href="' . get_permalink() . '" >' . get_the_post_thumbnail( $post->ID, $image_size ) . '</a>';
			}
			largo_maybe_top_term( array( 'post' => $post->ID ) );
			echo '<h2 class=""><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';

			largo_excerpt( $post->ID, 2 );
		?>
	</article>
<?php
