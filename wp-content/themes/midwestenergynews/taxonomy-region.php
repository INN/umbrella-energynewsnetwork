<?php
/**
 * Template for various non-category archive pages (tag, term, date, etc.)
 *
 * @package Largo
 * @since 0.1
 * @filter largo_partial_by_post_type
 */
get_header();
$queried_object = get_queried_object();
?>

<div class="clearfix">

	<?php
		if ( have_posts() || largo_have_featured_posts() ) {

			// queue up the first post so we know what type of archive page we're dealing with
			the_post();

			/*
			 * Display some different stuff in the header
			 * depending on what type of archive page we're looking at
			 */

			if ( is_author() ) {
				$rss_link = get_author_feed_link( get_the_author_meta( 'ID' ) );
			} elseif ( is_tag() ) {
				$title = single_tag_title( '', false );
				$description = tag_description();
				$rss_link =  get_tag_feed_link( get_queried_object_id() );
			} elseif ( is_tax() ) {
				$title = single_term_title( '', false );
				$description = term_description();

				// rss links for custom taxonomies are a little tricky
				$term_id = intval( $queried_object->term_id );
				$tax = $queried_object->taxonomy;
				$rss_link = get_term_feed_link( $term_id, $tax );
			} elseif ( is_date() ) {
				$description = __( 'Select a different month:', 'largo' );
				if ( is_month() ) {
					$title = sprintf( __( 'Monthly Archives: <span>%s</span>', 'largo' ), get_the_date( 'F Y' ) );
				} elseif ( is_year() ) {
					$title = sprintf( __( 'Yearly Archives: <span>%s</span>', 'largo' ), get_the_date( 'Y' ) );
				} else {
					$title = _e( 'Blog Archives', 'largo' );
				}
			} elseif ( is_post_type_archive() )  {
				$post_type = $wp_query->query_vars['post_type'];
				/**
				 * Make the title of the post_type archive filterable
				 * @param string $title The title of the archive page
				 * @since 0.5.4
				 */
				$title = apply_filters(
					'largo_archive_' . $post_type . '_title',
					__( post_type_archive_title( '', false ), 'largo' )
				);
				/**
				 * Make the feed url of the post_type archive filterable
				 * @param string $title The title of the archive page
				 * @since 0.5.5
				 */
				$rss_link = apply_filters(
					'largo_archive_' . $post_type . '_feed',
					site_url('/feed/?post_type=' . urlencode($post_type))
				);
			}


		?>

		

		<header class="archive-background clearfix">
			<?php
				if ( isset( $rss_link ) ) {
					printf( '<a class="rss-link rss-subscribe-link" href="%1$s">%2$s <i class="icon-rss"></i></a>', $rss_link, __( 'Subscribe', 'largo' ) );
				}

				$post_id = largo_get_term_meta_post( $queried_object->taxonomy, $queried_object->term_id );
				largo_hero($post_id);

				if ( isset( $title ) ) {
					if ( is_tax( 'region', 'midwest' ) ) {
						echo '<img src="' . get_stylesheet_directory_uri() . '/images/MidwestEnergyNews_Logo.svg' . '" alt="' . $title  . '" class="region-header" />';
					} else if ( is_tax( 'region', 'southeast' ) ) {
						echo '<img src="' . get_stylesheet_directory_uri() . '/images/SoutheastEnergyNews_Logo.svg' . '" alt="' . $title  . '" class="region-header" />';
					} if ( is_tax( 'region', 'southwest' ) ) {
						echo '<img src="' . get_stylesheet_directory_uri() . '/images/SouthwestEnergyNews_Logo.svg' . '" alt="' . $title  . '" class="region-header" />';
					} if ( is_tax( 'region', 'northeast' ) ) {
						echo '<img src="' . get_stylesheet_directory_uri() . '/images/NortheastEnergyNews_Logo.svg' . '" alt="' . $title  . '" class="region-header" />';
					} 

					/* echo '<h1 class="page-title">' . $title . '</h1>'; */
				}

				/* if ( isset( $description ) ) {
					echo '<div class="archive-description">' . $description . '</div>';
				}*/

				if ( is_date() ) {
			?>
					<nav class="archive-dropdown">
						<select name="archive-dropdown" onchange='document.location.href=this.options[this.selectedIndex].value;'><option value=""><?php _e('Select Month', 'largo'); ?></option>
							<?php wp_get_archives( array('type' => 'monthly', 'format' => 'option' ) ); ?>
						</select>
					</nav>
			<?php
				} elseif ( is_author() ) {
					the_widget( 'largo_author_widget', array( 'title' => '' ) );
				}
			?>
		</header>

		<?php dynamic_sidebar('homepage-featured-advert'); ?>

		<div id="homepage-top" class="row-fluid">
			<div class="span8">
				<article class="hero">
					<a href="<?php echo esc_attr(get_permalink()); ?>"><?php echo get_the_post_thumbnail($bigStoryPost->ID, 'full'); ?></a>
					<header>
						<h2><a href="<?php echo get_permalink(); ?>" class="has-photo"><?php echo the_title(); ?></a></h2>
						<?php largo_byline( true, false, $bigStoryPost->ID ); ?>
						<p class="excerpt"><?php echo the_excerpt(); ?></p>
					</header>
				</article>
			</div>
			<div class="span4">
				<?php dynamic_sidebar('sidebar-main'); ?>
			</div>
		</div>

		<div id="homepage-bottom">
			<?php 
				global $shown_ids;
				
				while ( have_posts() ) {
					the_post();
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
			?>
		</div>

		<!-- <nav id="mwen-hp-nav-below">
			<div class="load-more">
				<a class="btn btn-primary" href="#">More posts</a>
			</div>
		</nav> -->

		</div>
		<?php } else {
			get_template_part( 'partials/content', 'not-found' );
		}
	?>
</div>

<?php get_footer();
