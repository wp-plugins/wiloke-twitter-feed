<?php
/*
Plugin Name: Wiloke Twitter Feed
Plugin URI: http://test.wiloke.com/wp-content/uploads/2015/03/twitter-feed.zip
Author: wiloke
Author URI: wiloke.com
Version: 1.0
Description: Integrate twitter in your sidebar
License: Under GPL2

Copyright 2014 wiloke (email : piratesmorefun@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


define('PITWITTERLIBS', plugin_dir_path(__FILE__) . 'lib/');

add_action('widgets_init', 'pi_twitter_feed_widget');

add_action('admin_enqueue_scripts', 'pi_twitter_feed_scripts');
add_action('wp_enqueue_scripts', 'pi_twitter_feed_fe');

function pi_twitter_feed_fe()
{
	wp_register_style('pi_widget', plugin_dir_url(__FILE__) . 'source/css/style.css', array(), '1.0');
	wp_enqueue_style('pi_widget');
}

function pi_twitter_feed_scripts()
{
	global $pagenow;
	if ( $pagenow && $pagenow == "widgets.php" )
	{
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker');

		wp_register_script('pi_widget', plugin_dir_url(__FILE__) . 'source/js/widget.js', array(), '1.0', true);
		wp_enqueue_script('pi_widget');
	}
}

function pi_twitter_feed_widget()
{
	register_widget( 'piTwitterFeed' );
};

class piTwitterFeed extends WP_Widget
{
	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$args = array('classname'=>'widget_twitter_feed',  'description'=>__('Twitter Feed', 'wiloke'));
        parent::__construct("widget_twitter_feed", __( 'Wiloke Twitter Feed', 'wiloke' ),  $args);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) 
	{
	 	extract( $args, EXTR_SKIP );
        $title    = apply_filters('widget_title', $instance['title'] );
     
        $output  = "";
        $output .= $before_widget;
        
        if(!empty($title))
        $output .= $before_title.esc_attr($title).$after_title;
		
    	
        if( empty($instance['consumer_key']) || empty($instance['consumer_secret']) || empty($instance['access_token']) || empty($instance['access_token_secret']) )
        {
            $output .= "[Please config twitter]";
        }else{
	    	$limit    = !empty($instance['limit']) ? $instance['limit'] : 4;
	     	$username = !empty($instance['username']) ? $instance['username'] : 'envato';

	     	$instance['consumer_key'] = trim($instance['consumer_key']);
	     	$instance['consumer_secret'] = trim($instance['consumer_secret']);
	     	$instance['access_token'] = trim($instance['access_token']);
	     	$instance['access_token_secret'] = trim($instance['access_token_secret']);
	     	
	     
	        require_once(PITWITTERLIBS.'twitteroauth.php');
	        $twitter = new TwitterOAuth($instance['consumer_key'], $instance['consumer_secret'], $instance['access_token'], $instance['access_token_secret'], PITWITTERLIBS, $instance['cache_interval']);
	        $twitter->ssl_verifypeer=true;
	        $tweets = $twitter->get('statuses/user_timeline', array('screen_name' => $username, 'exclude_replies' => 'false', 'include_rts' => 'false', 'count' => $limit));
	        
	        if ( !empty($tweets) )
	        {
	            $tweets = json_decode($tweets);

	            if(is_array($tweets) )
	            {
	                $output .= '<ul class="pi_twitter_feed">';
	                
	                if ( isset($tweets->errors) )
	                {	
	                	$output .= "Sorry! That page does not exist!";
	                }else{
		                foreach($tweets as $control)
		                {
		                    $status =   preg_replace('/http:\/\/([^\s]+)/i', '<a style="color: '.$instance['link_color'].'" href="http://$1" target="_blank">$1</a>', $control->text);
		                    $output .= '<li class="item"><p>' . $status . '</p></li>';
		                }
		            }
	                $output .= '</ul>';
	            }
	            
	        }else{
	        	$output .= 'Could not retrieve data from twitter!';
	        }
	    }

	    $output .= $after_widget;
      
		echo $output;
	}

	public function pi_parse_time($a)
    {
        $b = strtotime("now");
        $c = strtotime($a);
        $d = $b - $c;

        $minute = 60;
        $hour = $minute * 60;
        $day = $hour * 24;
        $week = $day * 7;

        if(is_numeric($d) && $d > 0) {
            //if less then 3 seconds
            if($d < 3) return "right now";
            //if less then minute
            if($d < $minute) return floor($d) . " seconds ago";
            //if less then 2 minutes
            if($d < $minute * 2) return "about 1 minute ago";
            //if less then hour
            if($d < $hour) return floor($d / $minute) . " minutes ago";
            //if less then 2 hours
            if($d < $hour * 2) return "about 1 hour ago";
            //if less then day
            if($d < $day) return floor($d / $hour) . " hours ago";
            //if more then day, but less then 2 days
            if($d > $day && $d < $day * 2) return "yesterday";
            //if less then year
            if($d < $day * 365) return floor($d / $day) . " days ago";
            //else return more than a year
            return "over a year ago";
        }
    }


	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance )
	{
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Twitter', 'wiloke' );
		$args  =  array("username"=>"envato", "limit"=>5, "consumer_key"=>"", "consumer_secret"=>"", "access_token"=>"", "access_token_secret"=>"", "cache_interval"=>15, "link_color"=>"#1f0977");
		$instance = wp_parse_args($instance, $args);
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'username' ); ?>"><?php _e( 'Username:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" type="text" value="<?php echo esc_attr( $instance['username'] ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Limit' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo esc_attr( $instance['limit'] ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'consumer_key' ); ?>"><?php _e( 'Consumer Key' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'consumer_key' ); ?>" name="<?php echo $this->get_field_name( 'consumer_key' ); ?>" type="text" value="<?php echo esc_attr( $instance['consumer_key'] ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'consumer_secret' ); ?>"><?php _e( 'Consumer Secret' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'consumer_secret' ); ?>" name="<?php echo $this->get_field_name( 'consumer_secret' ); ?>" type="text" value="<?php echo esc_attr( $instance['consumer_secret'] ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'access_token' ); ?>"><?php _e( 'Access Token' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'access_token' ); ?>" name="<?php echo $this->get_field_name( 'access_token' ); ?>" type="text" value="<?php echo esc_attr( $instance['access_token'] ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'access_token_secret' ); ?>"><?php _e( 'Access Token Secret' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'access_token_secret' ); ?>" name="<?php echo $this->get_field_name( 'access_token_secret' ); ?>" type="text" value="<?php echo esc_attr( $instance['access_token_secret'] ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'cache_interval' ); ?>"><?php _e( 'Cache Interval (minutes)' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'cache_interval' ); ?>" name="<?php echo $this->get_field_name( 'cache_interval' ); ?>" type="text" value="<?php echo esc_attr( $instance['cache_interval'] ); ?>">
		</p>


		<p>
			<label for="<?php echo $this->get_field_id( 'link_color' ); ?>"><?php _e( 'Link Color' ); ?></label> <br>
			<input class="widefat pi_color_picker" id="<?php echo $this->get_field_id( 'link_color' ); ?>" name="<?php echo $this->get_field_name( 'link_color' ); ?>" type="text" value="<?php echo esc_attr( $instance['link_color'] ); ?>">
		</p>

		<p>
			<?php _e("How to get twitter application : <a href='http://test.wiloke.com/creating-twitter-application/' target='_blank'>click here</a>", "wiloke"); ?>
		</p>
		<?php 
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;

		foreach ( $new_instance as $key => $val )
		{
			$instance[$key] = strip_tags($val);
		}
		return $instance;
	}
}
