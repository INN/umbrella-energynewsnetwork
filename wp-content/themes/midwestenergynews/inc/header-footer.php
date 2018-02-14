<?php 

/**
 * Add search box to main nav
 */
function largo_component_add_search_box_main_nav() {
	get_template_part( 'partials/largo-component-main-nav-search-form' );
}
add_action( 'largo_after_main_nav_shelf' , 'largo_component_add_search_box_main_nav' );

/**
 * Add the Get Site Control widget javascript to the footer
 *
 * Because for some reason the GSC plugin isn't adding these.
 *
 * @link https://secure.helpscout.net/conversation/522494268/1745/
 */
function enn_getsitecontrol_script() {
	?>
	<script>
		(function (w,i,d,g,e,t,s) {w[d] = w[d]||[];t= i.createElement(g);
			t.async=1;t.src=e;s=i.getElementsByTagName(g)[0];s.parentNode.insertBefore(t, s);
		})(window, document, '_gscq','script','//widgets.getsitecontrol.com/122526/script.js');
	</script>
	<?php
}
add_action( 'wp_footer', 'enn_getsitecontrol_script', 10 );
