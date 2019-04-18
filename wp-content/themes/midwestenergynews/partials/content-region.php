<?php
/**
 * Define a global var and use it to determine the classes on the output on the LMP.
 *
 * We're mimicking the homepage 5-post groupings of three span4 posts followed
 * by two span6 posts, which is set for the homepage in the function
 * mwen_print_homepage_posts, which has access to a counter in the loop.
 *
 * The WP_Query this piece of code runs off of is set in largo_load_more_posts(),
 * and it's not the global $wp_query, so the actual query vars are not accessible.
 * We also can't iterate a variable in the native loop code and have it be accessible
 * here in this file because the scopes are different and because that would
 * require modifying largo_load_more_posts() to pass the variable down, which is
 * kinda overkill for this one child theme, and would require a lot of tests.
 *
 * So for now, this template partial - only used on LMP - will set and increment
 * a counter as a global so that different loops within the LMP loop can pass info
 * to the next loop's template.
 *
 * Note: The "Blog pages show at most" option MUST be set to 5 for this to work.
 *
 * @global int $mwen_lmp_counter Number of iterations of this item.
 * @since Largo 0.5.5.4
 */

// ugh this is such a hack
global $mwen_lmp_counter;

// set to 1 if unset or if overflowing
if ( ! isset( $mwen_lmp_counter ) || 6 === $mwen_lmp_counter  ) {
	$mwen_lmp_counter = 1;
}

$span = ( $mwen_lmp_counter <= 3 ) ? 'span4' : 'span6';
$image_size = 'rect_thumb';

// because each cluster of span4 or span6 should be its own row
if ( $mwen_lmp_counter === 1 || $mwen_lmp_counter === 4 ) {
	echo '<div class="hg-row">';
}

?>
<div id="post-<?php the_ID(); ?>" <?php post_class( $span ); ?>>
	<article class="hg-cell">
		<div class="hg-cell-inner">
			<h5 class="top-tag"><?php largo_top_term();?></h5>
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

if ( $mwen_lmp_counter === 3 || $mwen_lmp_counter === 5 ) {
	echo '</div>'; //end of row;
}

$mwen_lmp_counter++;
