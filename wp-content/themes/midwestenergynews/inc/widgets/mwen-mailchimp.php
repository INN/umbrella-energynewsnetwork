<?php
/**
 * A custom Mailchimp Signup Widget
 *
 * Basically: A hardcoded form, and a bunch of styles.
 */

class mwen_mailchimp_signup_widget extends WP_Widget {

	function __construct() {
		$widget_opts = array(
			'classname' => 'mailchimp-signup-widget',
			'description'=> __('Display a simple mailchimp signup form.', 'largo')
		);
		parent::__construct('mailchimp_signup_widget', __('ENN Mailchimp Signup', 'largo'),$widget_opts);
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters('widget_title', empty( $instance['title'] ) ? __('Subscribe to our newsletter', 'largo') : $instance['title'], $instance, $this->id_base);

		// Because $before_widget is a whole thing of HTML, just copy it all to add the 'rev' class.
		if (isset($instance['reverse']) && $instance['reverse'] == true) {
			$before_widget = '<div class="widget mailchimp-signup-widget rev">';
		}

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		?>

			<div class="newsletter-widget">
				<form action="https://midwestenergynews.us7.list-manage.com/subscribe/post?u=ae5d3a0c6088cad29d71bf0d0&amp;id=724b1f01f5" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
					<div id="mailchimp-input-wrap">
						<label for="mce-EMAIL">Email Address </label>
						<input type="email" placeholder="Your email" name="EMAIL" class="required email" id="mce-EMAIL">

					<?php if( !is_home() && !is_archive() ) { ?>
						<input type="text" placeholder="Zip code" name="ZIPCODE" class="required zipcode" id="mce-ZIPCODE">
					<?php } ?>
					</div>

					<ul>
						<li>
							<input type="checkbox" value="1" name="group[6847][1]" id="mce-group[6847]-6847-0">
							<label for="mce-group[6847]-6847-0">Midwest Energy News</label>
						</li>
						<li>
							<input type="checkbox" value="2" name="group[6847][2]" id="mce-group[6847]-6847-1">
							<label for="mce-group[6847]-6847-1">Southeast Energy News</label>
						</li>
						<li>
							<input type="checkbox" value="4" name="group[6847][4]" id="mce-group[6847]-6847-2">
							<label for="mce-group[6847]-6847-2">Northeast Energy News</label>
						</li>
						<li>
							<input type="checkbox" value="8" name="group[6847][8]" id="mce-group[6847]-6847-3">
							<label for="mce-group[6847]-6847-3">Western Energy News</label>
						</li>
						<li>
							<input type="checkbox" value="16" name="group[6847][16]" id="mce-group[6847]-6847-4">
							<label for="mce-group[6847]-6847-4">U.S. Energy News</label>
						</li>
					</ul>

					<div class="content__gdpr">
						<label for="interestgroup_field">Please select all the ways you would like to hear from Energy News Network:</label>
						<fieldset class="mc_fieldset gdprRequired mc-field-group" name="interestgroup_field">
							<ul>
								<li>
									<input type="checkbox" id="gdpr_87" name="gdpr[87]" value="Y" class="av-checkbox gdpr">
									<label class="subfield" for="gdpr_87">
										<span>Email</span>
									</label>
								</li>
								<li>
									<input type="checkbox" id="gdpr_91" name="gdpr[91]" value="Y" class="av-checkbox gdpr">
									<label class="subfield" for="gdpr_91">
										<span>Direct Mail</span>
									</label>
								</li>
								<li>
									<input type="checkbox" id="gdpr_95" name="gdpr[95]" value="Y" class="av-checkbox gdpr">
									<label class="subfield" for="gdpr_95">
										<span>Customized Online Advertising</span>
									</label>
								</li>
							</ul>
						</fieldset>
						<p>
							You can manage your subscription at any time by clicking the link in the footer of our emails.
							<?php
								$policy = get_privacy_policy_url();
								if ( ! empty( $policy ) ) {
									printf(
										__( 'For information about our privacy practices, please visit <a class="privacy-policy-link" href="%s">our privacy policy</a>.' ),
										esc_url( $privacy_policy_url ),
									);
								}
							?>
						</p>
					</div>

					<div class="content__gdprLegal">
						<p>We use Mailchimp as our marketing platform. By clicking below to subscribe, you acknowledge that your information will be transferred to Mailchimp for processing. <a href="https://mailchimp.com/legal/" target="_blank">Learn more about Mailchimp's privacy practices here.</a></p>
					</div>

					<input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class=" btn">

					<div id="mce-responses" class="clear">
						<div class="response" id="mce-error-response" style="display:none"></div>
						<div class="response" id="mce-success-response" style="display:none"></div>
					</div>
					<!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
					<div class="hidden" aria-hidden="true"><input type="text" name="b_ae5d3a0c6088cad29d71bf0d0_724b1f01f5" tabindex="-1" value=""></div>
				</form>
			</div>
		<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}

	function form( $instance ) {
		$defaults = array(
			'title' 	=> __('Subscribe to our newsletter', 'largo'),
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>">
		</p>
		
		<?php
	}

}
