<?php
/*
	Plugin Name: WP Video Embed Widget
	Plugin URI: http://justintallant.com
	Description: Adds a video embed widget that uses the native WordPress embed shortcode
	Author: Justin Tallant
	Author URI: http://justintallant.com

	Version: 1.0

	License: GNU General Public License v2.0 (or later)
	License URI: http://www.opensource.org/licenses/gpl-license.php
*/


/**
 * Creates the embed widget
 *
 * This widget simply builds the native wordpress embed
 * shortcode out of the widget input fields and echos
 * them.
 *
 * @author Justin Tallant
 */
class Jt_Wp_Video_Embed_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
	 		'wp_video_embed_widget', // Base ID
			'WP Video Embed Widget', // Name
			array( 'description' => __( 'Display an embedded video', 'vew' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		extract( $args );
		$title  = apply_filters( 'widget_title', $instance['title'] );
		$width  = $instance['width'];
		$height = $instance['height'];
		$url    = $instance['url'];
		$rel    = $instance['disable_rel'];

		echo $before_widget;

		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		$content = '[embed';
		$content .= ( ! empty($width) ) ? ' width="' . $width . '"' : '';
		$content .= ( ! empty($height) ) ? ' height="' . $height . '"' : '';
		$content .= ']' . $url . '[/embed]';

		$embed = new Jt_Widget_WP_Embed($rel, $widget['widget_id'] . $width . $height);
		echo $embed->run_shortcode($content);
		echo $after_widget;
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['url']         = esc_url_raw( $new_instance['url'] );
		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['width']       = strip_tags( $new_instance['width'] );
		$instance['height']      = strip_tags( $new_instance['height'] );
		$instance['disable_rel'] = (int) $new_instance['disable_rel'];

		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		$defaults = array(
			'title'       => '',
			'url'         => '',
			'width'       => '',
			'height'      => '',
			'disable_rel' => 1
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'vew' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php esc_attr_e( $instance['title'] ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'url' ); ?>"><?php _e( 'Video URL', 'vew' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>" type="text" value="<?php esc_attr_e( $instance['url'] ) ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'width' ); ?>"><?php _e( 'Width - optional', 'vew' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'width' ); ?>" name="<?php echo $this->get_field_name( 'width' ); ?>" type="text" value="<?php esc_attr_e( $instance['width'] ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'height' ); ?>"><?php _e( 'Height - optional', 'vew' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'height' ); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" type="text" value="<?php esc_attr_e( $instance['height'] ); ?>" />
		</p>

		<p>
			<input type="checkbox" name="<?php echo $this->get_field_name( 'disable_rel' ); ?>" id="<?php echo $this->get_field_id( 'disable_rel' ); ?>" value="1" <?php checked( $instance['disable_rel'], 1); ?> />
			<label for="<?php echo $this->get_field_id( 'disable_rel' ); ?>"><?php _e( 'Disable related videos? (only applies to youtube urls)', 'vew' ); ?></label>
		</p>
		<?php
	}

}

/**
 * Extends the WP_Embed class and adds two extra properties
 *
 * This subclass customizes the shortcode method of the
 * WP_Embed class. The WP_Embed class uses the WP_Object_Cache
 * and stores data in the post meta fields using a post id.
 * The widget does not have a post ID, so the subclass mocks
 * the parent class caching functionality but uses transient
 * data instead of the object cache.
 *
 * @see WP_Embed::shortcode()
 * @var bool $disable_related disables related videos if true
 * @var string $widget_id unique id of the widget
 */
class Jt_Widget_WP_Embed extends WP_Embed {

	public $disable_related = false;
	public $widget_id;

	public function __construct($disable_related, $widget_id) {

		$this->disable_related = $disable_related;
		$this->widget_id = $widget_id;
	}

	function shortcode( $attr, $url = '' ) {

		global $post;

		if ( empty($url) )
			return '';

		$rawattr = $attr;
		$attr = wp_parse_args( $attr, wp_embed_defaults() );

		// kses converts & into &amp; and we need to undo this
		// See http://core.trac.wordpress.org/ticket/11311
		$url = str_replace( '&amp;', '&', $url );

		// Look for known internal handlers
		ksort( $this->handlers );
		foreach ( $this->handlers as $priority => $handlers ) {
			foreach ( $handlers as $id => $handler ) {
				if ( preg_match( $handler['regex'], $url, $matches ) && is_callable( $handler['callback'] ) ) {
					if ( false !== $return = call_user_func( $handler['callback'], $matches, $attr, $url, $rawattr ) )
						return apply_filters( 'embed_handler_html', $return, $url, $attr );
				}
			}
		}

		$transient_name = $this->widget_id . '_' . md5($url);

		// Store the value of the disable_related option.
		// If it changes we need to clear transient data containing the html
		// and update the transient containing the disable_related value
		$related_transient = get_transient($transient_name . '_related');

		if ( false !== $related_transient && $related_transient != $this->disable_related ) {

			delete_transient($transient_name);
			set_transient($transient_name . '_related', $this->disable_related, 60*60*12);
		}

		$transient = get_transient($transient_name);

		// return the transient html value if its available
		if ( false !== $transient) {

			return apply_filters( 'embed_oembed_html', $transient, $url, $attr, $post_ID );
		}

		// Use oEmbed to get the HTML
		$attr['discover'] = ( apply_filters('embed_oembed_discover', false) && author_can( $post_ID, 'unfiltered_html' ) );
		$html = wp_oembed_get( $url, $attr );

		// If there was a result, return it
		if ( $html ) {

			// if 'youtube' is found in the html and disable related is true, add rel=0 paramater
			if ( false !== strpos($html, 'youtube') && true == $this->disable_related ) {

				$html = $this->disable_youtube_related($html);
			}

			set_transient($transient_name, $html, 60*60*12);

			// We need to know if the disable_related value of this widget changes
			// and clear transient data when it does
			set_transient($transient_name . '_related', $this->disable_related, 60*60*12);

			return apply_filters( 'embed_oembed_html', $html, $url, $attr, $post_ID );
		}

		return $this->maybe_make_link( $url );
	}

	/**
	 * Returns the html with the rel=0 param added to the youtube url
	 *
	 * Unfornately this cannot be done by appending rel=0 to the
	 * the url inside the embed code to begin with. Oauth will
	 * ignore it so it must be done manually here.
	 *
	 * @param string $html
	 * @return string $html_no_rel
	 */
	function disable_youtube_related($html) {

		// no need to add the rel=0 if it is already there
		if ( false !== strpos($html, '&rel=0') ) {
			return $html;
		}

		$html = explode('&feature=oembed', $html);
		$html[0] .= '&featured=oembed&rel=0';
		$html_no_rel = $html[0] . $html[1];

		return $html_no_rel;
	}
}

add_action('widgets_init', 'jt_register_wp_video_embed_widget');
/**
 * Register the widget with WordPress
 */
function jt_register_wp_video_embed_widget() {
	register_widget('Jt_Wp_Video_Embed_Widget');
}