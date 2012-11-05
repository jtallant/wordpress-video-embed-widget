<?php

/**
 * Adds Jt_Wp_Video_Embed_Widget widget.
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
	 		'wordpress_video_embed', // Base ID
			'WordPress Video Embed', // Name
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

		echo $before_widget;

		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		$content = '[embed';
		$content .= ( ! empty($width) ) ? ' width="' . $width . '"' : '';
		$content .= ( ! empty($height) ) ? ' height="' . $height . '"' : '';
		$content .= ']' . $url . '[/embed]';

		$embed = new WP_Embed;
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
		$instance['url']    = esc_url_raw( $new_instance['url'] );
		$instance['title']  = strip_tags( $new_instance['title'] );
		$instance['width']  = strip_tags( $new_instance['width'] );
		$instance['height'] = strip_tags( $new_instance['height'] );

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

		$title  = isset($instance['title']) ? $instance['title'] : '';
		$url    = isset($instance['url']) ? $instance['url'] : '';
		$width  = isset($instance['width']) ? $instance['width'] : '';
		$height = isset($instance['height']) ? $instance['height'] : '';

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'vew' ); ?>:</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php esc_attr_e( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'url' ); ?>"><?php _e( 'Video URL', 'vew' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>" type="text" value="<?php esc_attr_e( $url ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'width' ); ?>"><?php _e( 'Width - optional', 'vew' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'width' ); ?>" name="<?php echo $this->get_field_name( 'width' ); ?>" type="text" value="<?php esc_attr_e( $width ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'height' ); ?>"><?php _e( 'Height - optional', 'vew' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'height' ); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" type="text" value="<?php esc_attr_e( $height ); ?>" />
		</p>

		<p>
		<?php 
	}

}

add_action('widgets_init', 'jt_wp_video_embed_widget_register');
function jt_wp_video_embed_widget_register() {
	register_widget('Jt_Wp_Video_Embed_Widget');
}