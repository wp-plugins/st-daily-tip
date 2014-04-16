<?php
/**
 * Add function to widgets_init that'll load our widget.
 */
 
 add_action('widgets_init','st_daily_tip_load_widget');
 
 
 /**
 * Register our widget.
 * 'st_daily_tip_load_widget' is the widget class used below.
 */
 function st_daily_tip_load_widget()
 {
	register_widget('st_daily_tip_widget'); 
 }
 
 class st_daily_tip_widget extends WP_Widget
 {
 
	/**
	 * Widget setup.
	 */
	 
	 function st_daily_tip_widget()
	 {
		/* Widget settings. */
		$widget_ops=array('classname'=>'daily_tip','description'=>__('An Widget that display Daily Tip','daily_tip'));
	
		/* Widget control settings. */
		$control_ops=array('width'=>300,'Height'=>350,'id_base'=>'st-daily-tip-widget');
		
		/* Create the widget. */
		$this->WP_Widget('st-daily-tip-widget',__('Daily Tip Widget','daily_tip'),$widget_ops,$control_ops);
	}	
	
	/**
	 * How to display the widget on the screen.
	 */
	 
	 function widget($args,$instance)
	 {
		extract($args);
		
		/* Our variables from the widget settings. */
		$title=apply_filters('widget_title',$instance['title']);
		if ( $title )
		{
			echo $before_title . $title . $after_title;
		}
		$group=apply_filters('group_name',$instance['group']);
		if($group==null)
		{
			$group="Tip";
		}
		
		$date=apply_filters('date',$instance['date']);
		
		if( $date AND $date == '1' )
		{
			$date="show";
			echo '<div class="st_tip">';
			$today_tip = select_today_tip($group,$date);
			$today_tip=explode("Last Shown Date:",$today_tip);
			echo $today_tip[0]."</br> Last Shown Date:".$today_tip[1];
			echo '</div>';
		}
		else
		{
			$date="Not Show";
			echo '<div class="st_tip">';
			$today_tip = select_today_tip($group,$date);
			echo $today_tip;
			echo '</div>';
		}
		
		
	 }
	 
	 function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['group'] = strip_tags( $new_instance['group'] );
		$instance['date'] = strip_tags($new_instance['date']);
		return $instance;
	}
	
	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	 
	function form( $instance ) 
	{
		/* Set up some default widget settings. */
		$defaults = array( 'title' => __('Daily Tip', 'Daily Tip') );
		$defaults = array( 'group' => __('Tip', 'Tip') );
		$date = esc_attr($instance['date']);
		$instance = wp_parse_args( (array) $instance, $defaults );
	?>
		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'group' ); ?>"><?php _e('Group Name:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'group' ); ?>" name="<?php echo $this->get_field_name( 'group' ); ?>" value="<?php echo $instance['group']; ?>" style="width:100%;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'date' ); ?>"><?php _e('Show last date:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id('date'); ?>" name="<?php echo $this->get_field_name('date'); ?>" type="checkbox" value="1" <?php checked( '1', $date); ?> />
		</p>
	<?php
	}
 }
 ?>