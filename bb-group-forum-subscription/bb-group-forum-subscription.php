<?php

/**
 * Plugin Name: Group Forum Subscripton for BuddyPress (bbPress trigger)
 * Plugin Description: Allows users to subscribe to BP group forums. This is the bbPress trigger for the main BuddyPress plugin - necessary only if your members use bbPress rather than BuddyPress as their posting interface. Install in bbPress, NOT WordPress! If you are running BP 1.1+, you can safely delete this file bb-group-forum-subscription.php from your plugins directory
 * Author: Boone Gorges
 * Author URI: http://teleogistic.net
 * Plugin URI: http://dev.commons.gc.cuny.edu
 * Version: 1.4 bleeding
 */

// Subscribes group members when a new discussion topic is created in bbPress
function gfs_group_new_topic_subscribe($topic_id) {
	global $bb;
		
	$groupmeta_table = $bb->wp_table_prefix . 'bp_groups_groupmeta';	
	$groupmembers_table = $bb->wp_table_prefix . 'bp_groups_members';
	
	$topic = get_topic( $topic_id );
	$forum_id = $topic->forum_id;
	
	$con = mysql_connect("localhost",$bb->user_bbdb_user,$bb->user_bbdb_password);
	
	mysql_select_db($bb->user_bbdb_name, $con) or die(mysql_error());
	
	$result = mysql_query( "SELECT `group_id` FROM $groupmeta_table WHERE ( `meta_key` = 'forum_id' AND `meta_value` = '$forum_id' )", $con );
	
	$group_id_array = mysql_fetch_array($result);
	//print_r($group_id_array); die(); 
	$group_id = $group_id_array[0];
	$group_stub = 'gfsub_' . $group_id;
	
	$result = mysql_query( "SELECT `user_id` FROM $groupmembers_table WHERE `group_id` = '$group_id' AND `is_confirmed` = 1 AND `is_banned` = 0");
	
	
	while($row=mysql_fetch_array($result)){
		if (bb_get_usermeta($row[0], 'bb_favorites')) {
			$user_faves = explode(",", bb_get_usermeta($row[0], 'bb_favorites'));
		} else {
			$user_faves = array();
		}
		
		if (!in_array( $topic_id, $user_faves ) && bb_get_usermeta($row[0], $group_stub) == 'yes') {
				$user_faves[] = $topic_id;
		}
		
		$user_faves = implode(",", $user_faves);
		bb_update_usermeta($row[0], 'bb_favorites', $user_faves);	
		
	}	
}
add_action( 'bb_insert_topic', 'gfs_group_new_topic_subscribe', 10, 1);


// Sends an email notification to subscribed members when a new item is posted
function gfs_notification_new_post($post_id=0) {
	global $bb, $bbdb, $bb_table_prefix, $topic_id, $bb_current_user;
	
	$topic = get_topic( $topic_id );
	$forum_id = $topic->forum_id;
	$forum = get_forum( $forum_id );
	
	$all_users = gfs_notification_select_all_users();
	foreach ($all_users as $userdata) :
		if ( $bb_current_user->ID != $userdata->ID ) {
		
		
		//print_r($userdata);
		//print(" ". is_user_favorite( $userdata->ID, $topic_id) . "<br />");
			if ( is_user_favorite( $userdata->ID, $topic_id ) ) :
			/*	$message = __( 
'A new message has been posted in the group "%s".
Title: %s
Author: %s

%s

To view the entire discussion, visit: %s


---------------------
');*/

		//$message .= sprintf( __( 'To unsubscribe from this discussion, visit %s. To manage your email notification preferences, visit %s.', 'buddypress' ), $topic_uri, $settings_link );
				
				
				$topic = get_topic($topic_id);
				//print_r ($topic); die();
				$message = __("A new message has been posted in the group \"%6\$s\"\nTitle: %7\$s\nAuthor: %2\$s \n\n %3\$s \n\n%4\$s\n%5\$s ");
					mail( $userdata->user_email, bb_get_option('name') . ': ' . __('New discussion board activity'), 
						sprintf( $message, stripslashes($forum->forum_name), get_user_name($bb_current_user->ID), strip_tags(get_post_text($post_id)), 'To read or unsubscribe from this discussion, visit '.get_topic_link($topic_id), 'To manage your subscription settings, visit '. $bb->wp_siteurl . '/members/'.get_user_name($userdata->ID ) . '/settings/notifications', get_forum_name( $topic->forum_id), $topic->topic_title, $topic->topic_title  ), 
						'From: '.bb_get_option('name').' <'.bb_get_option('from_email').'>'
					);
			endif; }
	endforeach; 
}
add_action('bb_new_post', 'gfs_notification_new_post');


// Helper function to list all users
function gfs_notification_select_all_users() {
	global $bbdb;
	
	$all_users = $bbdb->get_results("SELECT ID, user_email FROM $bbdb->users WHERE user_status=0");
	
	return $all_users;
}


// Changes the text of the "Add to favorites" link on individual bbPress topic pages to Subscribe/Unsubscribe (for the sake of clarity). Comment out the remove_filter and add_filter lines if you'd prefer to keep the default link text
function gfs_bb_just_in_time_script_localization() {
	wp_localize_script( 'topic', 'bbTopicJS', array(
		'currentUserId' => bb_get_current_user_info( 'id' ),
		'topicId' => get_topic_id(),
		'favoritesLink' => get_favorites_link(),
		'isFav' => (int) is_user_favorite( bb_get_current_user_info( 'id' ) ),
		'confirmPostDelete' => __("Are you sure you wanna delete this post?"),
		'confirmPostUnDelete' => __("Are you sure you wanna undelete this post?"),
		'favLinkYes' => __( 'favorites' ),
		'favLinkNo' => __( '?' ),
		'favYes' => __( '%favDel%' ),
		'favNo' => __( '%favAdd%' ),
		'favDel' => __( 'Unsubscribe from this discussion' ),
		'favAdd' => __( 'Subscribe to this discussion' )
	));
}
remove_filter( 'wp_print_scripts', 'bb_just_in_time_script_localization' );
add_filter( 'wp_print_scripts', 'gfs_bb_just_in_time_script_localization' );

?>