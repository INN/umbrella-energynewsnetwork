<?php

/**
 * Rewrite byline for vertical / horizontal stuff
 *
 * Note that this completely ignores the Largo_Byline class.
 *
 * This MUST be compatible with Largo's implementation: https://github.com/INN/largo/blob/v0.6.4/inc/post-tags.php#L123
 *
 * @param Boolean $echo Echo the string or return it (default: echo)
 * @param Boolean $exclude_date Whether to exclude the date from byline (default: false)
 * @param WP_Post|Integer $post The post object or ID to get the byline for. Defaults to current post.
 * @return String Byline as formatted html
 */
function largo_byline( $echo = true, $exclude_date = false, $post_id = null ) {
	if (!empty($post_id)) {
		if (is_object($post_id)) {
			$post_id = $post_id->ID;
		} else if (is_numeric($post_id)) {
			$post_id = $post_id;
		}

	} else {
		$post_id = get_the_ID();

		if ( WP_DEBUG || LARGO_DEBUG ) {
			_doing_it_wrong( 'largo_byline', 'largo_byline must be called with a post or post ID specified as the third argument. For more information, see https://github.com/INN/largo/issues/1517 .', '0.6' );
		}
	}

	$values = get_post_custom( $post_id );

	if ( function_exists( 'get_coauthors' ) && !isset( $values['largo_byline_text'] ) ) {
		$coauthors = get_coauthors( $post_id );
		foreach( $coauthors as $author ) {
			$byline_text = $author->display_name;
			if ( $org = $author->organization )
				$byline_text .= ' (' . $org . ')';

			if ( is_single() ) {
				$avatar = coauthors_get_avatar( $author );
			} else {
				$avatar = '';
			}
			$out[] = sprintf(
				'%4$s <a class="url fn n" href="%1$s" title="%2$s" rel="author">%3$s</a>',
				get_author_posts_url( $author->ID, $author->user_nicename ),
				esc_attr( sprintf( __( 'Read All Posts By %s', 'largo' ), $author->display_name ) ),
				esc_html( $byline_text ),
				$avatar
			);

		}

		if ( count($out) > 1 ) {
			end($out);
			$key = key($out);
			reset($out);
			$authors = implode( ', ', array_slice( $out, 0, -1 ) );
			$authors .= ' <span class="and">' . __( 'and', 'largo' ) . '</span> ' . $out[$key];
		} else {
			$authors = $out[0];
		}

	} else {
		$avatar = ( is_single() ) ? get_avatar( get_the_author_meta( 'ID' ) ) : '';
		$authors = $avatar . ' ' . largo_author_link( false, $post_id );
	}

	if ( is_single() ) {
		$teaser = __( 'Written By ', 'largo' );
	} else {
		$teaser = __( 'By ', 'largo' );
	}

	$byline_tease = $teaser;

	$output = sprintf(
		'<span class="by-author"><span class="by">%1$s</span><span class="author vcard" itemprop="author">%2$s</span></span>',
		$byline_tease,
		$authors
	);
	if ( is_single() && ! $exclude_date ) {
		$output .= '<time class="entry-date updated dtstamp pubdate" datetime="' . esc_attr( get_the_date( 'c', $post_id ) ) . '">' . largo_time(false, $post_id) . '</time>';
	// necessary to have date on the digests category, but not elsewhere
	} else if ( is_category( 'digest' ) && ! $exclude_date ) {
		$output .= sprintf(
			'<time class="entry-date updated dtstamp pubdate" datetime="%1$s">%2$s</time>',
			esc_attr( get_the_date( 'c', $post_id ) ),
			largo_time( false, $post_id )
		);
	}

	if ( is_single() && current_user_can( 'edit_post', $post_id ) ) {
		$output .= '<br /><span class="edit-link"><a href="' . get_edit_post_link( $post_id ) . '">' . __( 'Edit This Post', 'largo' ) . '</a></span>';
	}

	if ( $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Create limited social media soup for the span3 left column of the custom two-column layout.
 *
 * @since Largo 0.5
 * @see partials/content-single-classic.php
 */
function mwen_post_social_links( $echo = true ) {
	$utilities = of_get_option( 'article_utilities' );
	$output = '<div id="mwen_post_social_links" class="post-social clearfix"><div class="left">';

	if ( isset( $utilities['facebook'] ) && $utilities['facebook'] === '1' ) {
		$output .= sprintf( '<span data-service="facebook" class="custom-share-button icon-facebook share-button"></span>');
	}

	if ( isset( $utilities['twitter'] ) && $utilities['twitter'] === '1' ) {
		$output .= sprintf( '<span data-service="twitter" class="custom-share-button icon-twitter share-button"></span>');
	}

	if ( isset( $utilites['email'] ) && $utilities['email'] === '1' ) {
		$output .= '<span data-service="email" class="custom-share-button icon-mail"></span>';
	}

	if ( isset( $utilities['print'] ) && $utilities['print'] === '1' ) {
		$output .= '<span><a href="#" onclick="window.print()" title="' . esc_attr( __( 'Print this article', 'largo' ) ) . '" rel="nofollow"><i class="custom-share-button icon-print"></i></a></span>';
	}

	$output .= '</div><div class="right">';

	$output .= '</div></div>';
	if ( $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Add largo_top_term in the post header
 */
function mwen_top_term() {
	$post_type = get_post_type();
	if ( $post_type === 'roundup' ) {
		$categories = get_the_terms( $post->ID, 'category' );
		echo '<h5 class="top-tag"><a href="' . get_category_link( $categories[0]->term_id ) . '">' . $categories[0]->name . '</a></h5>';
	} else {
		echo '<h5 class="top-tag">';
		largo_maybe_top_term(); // The defaults are sane
		echo '</h5>';
	}
}
