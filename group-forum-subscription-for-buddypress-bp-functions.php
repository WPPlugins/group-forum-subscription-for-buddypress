<?php


// Builds admin menu

function gfs_group_forum_subscription_options() {
	global $wpdb;
	
	if( $_POST[ 'gfs_setup' ] == '1' ) {
		 $authors = $wpdb->get_results("SELECT ID from $wpdb->users ORDER BY display_name");
 	
 	$author_list = array();
 	foreach ($authors as $a) {
 		$author_list[] = $a->ID;
 	}
 	
 	foreach ($author_list as $a) {
 		
		update_usermeta($a, "favorite_notification", 1);
 		print ("Subscribing user #" . $a . " to groups: ");
 		if ( bp_has_groups(array('user_id' => $a, 'per_page' => 100 )) ) {
 			while ( bp_groups() ) : bp_the_group();
 				$g = bp_get_group_id();
 				gfs_groups_action_join_group_subscribe( $a, $g ); 
 				
 				print ($g);
 				print (" ");
 				
 			endwhile;
 		}
 		print "<br />";
 	}
 	print "Finished";
 	
	}
	
	if( $_POST[ 'gfs_update' ] == '1' ) {
	
        $opt_val = $_POST[ 'automatic-forum-subscription-default' ];
        $opt_val_2 = $_POST[ 'gfs-sender-email' ];
        
        update_option( 'automatic-forum-subscription-default', $opt_val );
        update_option( 'gfs-sender-email', $opt_val_2 );
		?>
		<div class="updated"><p><strong><?php _e('Options saved.', 'group_forum_subscription' ); ?></strong></p></div>
		<?php
	}
	
		$gfs_default = get_option ('automatic-forum-subscription-default');
		$gfs_sender_email = get_option( 'gfs-sender-email' );
	
	?>

          <div class="wrap">
            <h2><?php _e( 'Group Forum Subscription Options', 'group_forum_subscription' ) ?></h2>
             <h3><?php _e( 'Default setting for new users', 'group_forum_subscription' ) ?></h3>
            <form name="afs-options-form" method="post" action="">
                <div class="automatic-forum-subscription-options">
                                
                    <label for="automatic-forum-subscription-default" style="display:block;padding:10px;"><?php _e( 'Members are automatically subscribed to forums when they join a group', 'group_forum_subscription' ) ?>
                        <input name="automatic-forum-subscription-default" id="automatic-forum-subscription-default" type="checkbox" <?php if ( $gfs_default == 'on') { ?>checked="true"<?php } ?> />
                    </label>
                    
                    <label for="gfs-sender-email" style="display:block;padding:10px;"><?php _e( 'Members receive email notification of their own posts', 'group_forum_subscription' ) ?>
                        <input name="gfs-sender-email" id="gfs-sender-email" type="checkbox" <?php if ( $gfs_sender_email == 'on' ) { ?>checked="true"<?php } ?> />
                    </label>
                             
               <p class="submit">
                    <input type="submit" name="Submit" value="<?php _e('Update Options &raquo;','group_forum_subscription') ?>" />
                    <input type="hidden" name="gfs_update" value="1" />
                </p>
            </form>
            
            <h3><?php _e( 'Initial setup', 'group_forum_subscription' ) ?></h3>
            <form name="afs-options-form-2" method="post" action="">
                <div class="automatic-forum-subscription-options">
                    <label for="automatic-forum-subscription-setup" style="display:block;padding:10px;"><?php _e( '<strong>Warning:</strong> Clicking the Setup button will subscribe every member of your BuddyPress installation to every discussion in every group of which they are members. It will overwrite any bbPress favorites that the user has set manually. This step is irreversible, so <em>do not click unless you are certain that you want to subscribe everyone to their forums</em>!', 'group_forum_subscription' ) ?>
               		<br />
               		<br />
               		<?php _e( 'Consider backing up your wp_usermeta table before clicking Submit.', 'group_forum_subscription' ) ?>
                    </label>
     
               <p class="submit">
                    <input type="submit" name="Submit" value="<?php _e( 'Submit', 'group_forum_subscription' ) ?> &raquo;" />
                    <input type="hidden" name="gfs_setup" value="1" />
                </p>
            </form>        
        </div>

<?php
}


// Adds admin menu to WP Dashboard > Settings
function gfs_group_forum_subscription_menu() {
	
	if ( function_exists ( 'bp_forums_get_forum_topics' ) )
		add_submenu_page( 'bp-general-settings', __( 'Group Forum Subscription Options', 'group_forum_subscription' ), __( 'Group Forum Subscription', 'group_forum_subscription' ), 'manage_options', 'group_forum_subscription', 'gfs_group_forum_subscription_options' );
	else
		add_submenu_page( 'bp-core.php', __( 'Group Forum Subscription Options', 'group_forum_subscription' ), __( 'Group Forum Subscription', 'group_forum_subscription' ), 'manage_options', 'group_forum_subscription', 'gfs_group_forum_subscription_options' );
}
add_action('admin_menu', 'gfs_group_forum_subscription_menu', 30); // Thanks Ray!


// Determines global setting for whether new users are automatically subscribed to forums when they join new groups.
function gfs_user_set_default_forum_notification($user_id) {		
	if ( get_option ('automatic-forum-subscription-default') == 'on' )
		update_usermeta($user_id, "favorite_notification", 1);
}
add_action ( 'user_register', 'gfs_user_set_default_forum_notification', 15, 1 );


// Sends email notification to subscribed members on new post. 
function gfs_send_email_notification($post) {
	global $group_obj;
	global $bp;
	
	if ( function_exists ( 'bp_forums_get_forum_topics' ) )
		$group_obj = $bp->groups->current_group; // for bp 1.1+
	
	$topic_id = $post['topic_id'];
	$topic = bp_forums_get_topic_details( $topic_id );
	$text = $post['post_text'];
	
	if ( !function_exists ( 'bp_forums_get_forum_topics' ) ) { // For BP < 1.1
		$forum_id = $topic['forum_id'];
		$last_poster = $topic['topic_last_poster_display_name'];
		$last_poster_id = $topic['topic_last_poster'];
		$topic_uri = $topic['topic_uri'];		
	} else {
		$forum_id = $topic->forum_id;
		$last_poster_id = $topic->topic_last_poster;
		$last_poster = bp_core_get_user_displayname( $last_poster_id );
		$topic_uri = bp_get_forum_permalink() . '/topic/' . $topic->topic_slug;		
	}

	$text = preg_replace('/^p/', '', $text);
	$text = preg_replace('|\/pp|', '  ', $text);
	$text = preg_replace('|\/p|', '    ', $text);	

	$group_link = bp_get_group_permalink( $group_obj );

	$gfs_sender_email = get_option( 'gfs-sender-email' );

	$group_id = $group_obj->id;
	if ( bp_group_has_members( 
		array(
		'per_page' => 1000,
		'max' => false,
		'exclude_admins_mods' => 0,
		)
	) ) {
		while ( bp_group_members() ) {
			bp_group_the_member();
			$user_id = bp_get_group_member_id();
			
	if ( $gfs_sender_email == 'on' || $user_id != $last_poster_id ) {
			
			$user_url = bp_get_group_member_url();
			$user_faves = get_usermeta( $user_id, 'bb_favorites' );
				
			
			$user_faves = explode(',', $user_faves);
			
			if (in_array( $topic_id, $user_faves )) {
				$member = get_userdata($user_id);
				$settings_link = $user_url . 'settings/notifications';
				//$settings_link = site_url() . '/' . BP_MEMBERS_SLUG . '/' . $member->user_login . '/settings/notifications';
				$invited_link = site_url() . '/' . BP_MEMBERS_SLUG . '/' . $member->user_login;
				$invites_link = $invited_link . '/' . $bp->groups->slug . '/invites';

				// Set up and send the message
				$to = $member->user_email;

				$subject = '[' . get_blog_option( 1, 'blogname' ) . '] ' . sprintf( __( 'New discussion activity in the group: "%s"', 'group_forum_subscription' ), stripslashes($group_obj->name) );

				$message = sprintf( __( 
'A new message has been posted in the group "%s".
Author: %s

%s

To view the entire discussion, visit: %s
To view the group visit: %s


---------------------
', 'group_forum_subscription' ), stripslashes($group_obj->name), $last_poster, $text, $topic_uri, $group_link );

		$message .= sprintf( __( 'To unsubscribe from this discussion, visit %s. To manage your email notification preferences, visit %s.', 'group_forum_subscription' ), $topic_uri, $settings_link );

	
		// Send it
		
		wp_mail( $to, $subject, $message );
		}
			}
		}	
	}
}
add_action ( 'bp_forums_new_post', 'gfs_send_email_notification', 10, 1 ); // For BP < 1.1


// New hook for BP 1.1. Now with more arguments!
function gfs_new_topic_prepare_variables( $group_id, $topic ) {
	gfs_new_topic_subscribe($topic);
	gfs_prepare_variables_for_email( $group_id, $topic->topic_last_post_id );	
}
add_action ( 'groups_new_forum_topic', 'gfs_new_topic_prepare_variables', 5, 2 );


function gfs_prepare_variables_for_email( $group_id, $post_id ) {
	$post = bp_forums_get_post($post_id);
	//print_r($post); die();
	
	$post_array = array( 'topic_id' => $post->topic_id, 'post_text' => $post->post_text );

	gfs_send_email_notification($post_array);
}
add_action ( 'groups_new_forum_topic_post', 'gfs_prepare_variables_for_email', 10, 2 );


// Ensures that authors of new topics are subscribed to those topics
function gfs_new_topic_send_email_notification( $topic ) {
	$user_id = bp_core_get_userid( $topic['topic_poster_name'] );
	gfs_bp_add_user_favorite( $user_id, $topic['topic_id'] );	
	$last_post_id = $topic['topic_last_post_id'];
	$last_post = bp_forums_get_post($last_post_id);
	gfs_send_email_notification( $last_post );
}
add_action ( 'bp_forums_new_topic', 'gfs_new_topic_send_email_notification', 10, 1 );


// When a new topic is created, this function subscribes all members of the corresponding group to the topic
function gfs_new_topic_subscribe( $topic ) {
	global $group_obj;
	if ( is_array($topic) )
		$topic_id = $topic['topic_id'];
	else
		$topic_id = $topic->topic_id;
	
	if ( bp_group_has_members( 
		array(
		'per_page' => 1000,
		'max' => false,
		'exclude_admins_mods' => 0,
		)
	) ) {
		while ( bp_group_members() ) {
			bp_group_the_member();
			$id = bp_get_group_member_id();
			gfs_bp_add_user_favorite( $id, $topic_id);
		}
	}
}
add_action ( 'bp_forums_new_topic', 'gfs_new_topic_subscribe', 10, 1 );


// Called when a user clicks "Join group" in a public group. Prepares variables to send to the groups_action_join_group_subscribe
function gfs_groups_action_prepare_variables_for_forums() {
	global $group_obj;
	global $current_user;
	global $bp;

	 		   
	if ( $current_user->id ) { // For BP < 1.1
		$current_user_id = $current_user->id;
	} else { 
		$current_user_data = $current_user->data;
		$current_user_id = $current_user_data->ID;	
	}
	
	if ( $group_obj->id ) { // For BP < 1.1
		$current_group_id = $group_obj->id;
	} else {
		$current_group_id = $bp->groups->current_group->id;
	}

	gfs_groups_action_join_group_subscribe( $current_user_id, $current_group_id );
}
add_action ( 'groups_join_group', 'gfs_groups_action_prepare_variables_for_forums' );


// Called when a user accepts an invitation. Subscribes new group member to each discussion in the group forum
function gfs_groups_action_join_group_subscribe( $user_id, $group_id ) {	
	global $wpdb;
	$group = new BP_Groups_Group( $group_id );
   // global $group_obj;
   // $group = $group_obj;
   
    $noteslug = 'gfsub_' . $group_id;
	
	
    update_usermeta($user_id, $noteslug, 'yes');
//	$fave_note = get_usermeta( $user_id, 'favorite_notification');
//  if ( $fave_note == '1' ) {
	if (get_usermeta($user_id, 'bb_favorites')) {
		$user_faves = explode(",", get_usermeta($user_id, 'bb_favorites'));
	} else {
		$user_faves = array();
	}
	
							
	
	if (bp_group_is_forum_enabled($group) ) {
		$forum_id = (int)groups_get_groupmeta($group_id,'forum_id');
		if (function_exists ('bp_forums_get_topics') ) // For BP < 1.1
			$result = bp_forums_get_topics( $forum_id);
		else if (function_exists ( 'bp_forums_get_forum_topics' ) )
			$result = bp_forums_get_forum_topics ( array( 'forum_id' => $forum_id, 'per_page' => 100 ) );
		
	
		if ($result) {
		foreach ($result as $row) {	
			if ( is_array ($row) ) { // For BP < 1.1
				if (!in_array( $row['topic_id'], $user_faves ) ) {
					$user_faves[] = $row['topic_id'];
				}
			} else {
				if (!in_array( $row->topic_id, $user_faves ) ) {
					$user_faves[] = $row->topic_id;
				}			
			}
		}
		}
				
		gfs_update_user_faves ( $user_faves, $user_id );

	}
//  }
}

add_action ( 'groups_accept_invite', 'gfs_groups_action_join_group_subscribe', 10, 2 );


// Unsubscribes user from all group forums when they leave the group
function gfs_leave_group_mass_unsubscribe($group_id, $user_id ) {
	$group = new BP_Groups_Group( $group_id );
    $noteslug = 'gfsub_' . $group_id;
        
    delete_usermeta($user_id, $noteslug);
    
	if (get_usermeta($user_id, 'bb_favorites')) {
		$user_faves = explode(',', get_usermeta($user_id, 'bb_favorites'));
	} else {
		$user_faves = array();
	}
	
	
	
	if (bp_group_is_forum_enabled($group) ) {
		$forum_id = (int)groups_get_groupmeta($group_id,'forum_id');
		
		if (function_exists ('bp_forums_get_topics') ) // For BP < 1.1
			$result = bp_forums_get_topics( $forum_id);
		else if (function_exists ( 'bp_forums_get_forum_topics' ) )
			$result = bp_forums_get_forum_topics ( array( 'forum_id' => $forum_id, 'per_page' => 100 ) );
			
		if ($result) {
		foreach ($result as $row) {	
			if ( is_array ($row) ) { // For BP < 1.1
				if (in_array( $row['topic_id'], $user_faves ) ) {
					$key = array_search( $row['topic_id'], $user_faves );
					unset($user_faves[$key]);
				}
			} else {
				if (in_array( $row->topic_id, $user_faves ) ) {
					$key = array_search( $row->topic_id, $user_faves );
					unset($user_faves[$key]);
				}			
			}
		}
		}

		gfs_update_user_faves ( $user_faves, $user_id );
	}
	
}
add_action( 'groups_leave_group', 'gfs_leave_group_mass_unsubscribe', 10, 2 );


// Cleans up the results of joining/leaving a group and adds/subtracts those favorites from usermeta
function gfs_update_user_faves ( $uf, $user_id ) {
	$uf = implode(',', $uf);
	update_usermeta( $user_id, 'bb_favorites', $uf );
}


// Adds a section to BP Settings > Notifications for users to subscribe/unsubscribe on group-by-group basis.
function gfs_group_forum_subscription_settings() {
	
	global $current_user; 
	
	global $groups_template, $bp;
	global $group_obj;

	?>

		<table class="notification-settings" id="groups-notification-settings">
		<tr>
			<th class="icon"></th>
			<th class="title"><?php _e( 'Automatically subscribe to group discussions', 'group_forum_subscription' ) ?></th>
			<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
			<th class="no"><?php _e( 'No', 'buddypress' )?></th>
		</tr>

		
<?php if ( bp_has_groups(array('per_page' => 100) ) ) { ?>	
	<?php while ( bp_groups() ) : bp_the_group();
		if ( !$group )
             $group =& $groups_template->group;
             $group_slug = $group->slug;
             $group_id = $group->id;
             $noteslug = 'gfsub_' . $group_id;
             ?>
		<tr>
			<td></td>
			<td><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></td>
			<td class="yes"><input type="radio" name="notifications[<?php echo $noteslug ?>]" value="yes" <?php if ( 'yes' == get_usermeta( $current_user->id, $noteslug) ) { ?>checked="checked" <?php } ?>/></td>
			<td class="no"><input type="radio" name="notifications[<?php echo $noteslug ?>]" value="no" <?php if ( !get_usermeta( $current_user->id, $noteslug ) || 'no' == get_usermeta( $current_user->id, $noteslug) ) { ?>checked="checked" <?php } ?>/></td>
			<!--<input type="hidden" name="gfsgid_<?php echo $group_slug ?>" value="<?php echo $group_id ?>" />-->
			
		</tr>
	<?php endwhile; } ?>
	</table>
		<?php
}
add_action( 'bp_notification_settings', 'gfs_group_forum_subscription_settings' );



function gfs_bp_core_screen_notification_settings() {
	global $current_user, $bp_settings_updated;
	
	$bp_settings_updated = false;
	if ( $_POST['submit']  && check_admin_referer('bp_settings_notifications') ) {
		if ( $_POST['notifications'] ) {
		
			foreach ( $_POST['notifications'] as $key => $value ) {
				$old_setting = get_usermeta( (int)$current_user->id, $key );
				
				if ( $value != $old_setting ) {
					//print("Updating: " . $key . "<br />");
					update_usermeta( (int)$current_user->id, $key, $value );
					$hello = get_usermeta ((int)$current_user->id, $key);
				
					$prefix = substr( $key, 0, 6);
					if ( $prefix == 'gfsub_' ) {
						$group_id = substr($key, 6);
						if (get_usermeta ((int)$current_user->id, $key) == 'yes') {
							update_usermeta((int)$current_user->id, "favorite_notification", 1);
							gfs_groups_action_join_group_subscribe( (int)$current_user->id, $group_id);
						}
						if (get_usermeta ((int)$current_user->id, $key) == 'no')
							gfs_leave_group_mass_unsubscribe( (int)$current_user->id, $group_id );
					}			
				} else {
					//print("Not updating: " . $key . "<br />");
				}
			}
		}
		
		$bp_settings_updated = true;
	}
	add_action( 'bp_template_title', 'bp_core_screen_notification_settings_title' );
	add_action( 'bp_template_content', 'bp_core_screen_notification_settings_content' );
	
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'plugin-template' ) );
}

/*
function gfs_bp_core_add_settings_nav() {
	global $bp;

 if ( !function_exists ( 'bp_core_new_nav_item' ) ) { // For BP < 1.1
	bp_core_add_nav_item( __('Settings', 'buddypress'), 'settings', false, false );
	bp_core_add_nav_default( 'settings', 'bp_core_screen_general_settings', 'general', false );
	
	bp_core_add_subnav_item( 'settings', 'general', __('General', 'buddypress'), $bp->loggedin_user->domain . 'settings/', 'bp_core_screen_general_settings', false, bp_is_home() );
	bp_core_add_subnav_item( 'settings', 'notifications', __('Notifications', 'buddypress'), $bp->loggedin_user->domain . 'settings/', 'gfs_bp_core_screen_notification_settings', false, bp_is_home() );
	
	if ( !is_site_admin() )
		bp_core_add_subnav_item( 'settings', 'delete-account', __('Delete Account', 'buddypress'), $bp->loggedin_user->domain . 'settings/', 'bp_core_screen_delete_account', false, bp_is_home() );
  
  } else {

	bp_core_new_nav_item( array( 'name' => __('Settings', 'buddypress'), 'slug' => 'settings', 'position' => 100, 'show_for_displayed_user' => false, 'screen_function' => 'bp_core_screen_general_settings', 'default_subnav_slug' => 'general' ) );

	$settings_link = $bp->loggedin_user->domain . 'settings/';
	
	bp_core_new_subnav_item( array( 'name' => __( 'General', 'buddypress' ), 'slug' => 'general', 'parent_url' => $settings_link, 'parent_slug' => 'settings', 'screen_function' => 'bp_core_screen_general_settings', 'position' => 10, 'user_has_access' => bp_is_home() ) );
	bp_core_new_subnav_item( array( 'name' => __( 'Notifications', 'buddypress' ), 'slug' => 'notifications', 'parent_url' => $settings_link, 'parent_slug' => 'settings', 'screen_function' => 'gfs_bp_core_screen_notification_settings', 'position' => 20, 'user_has_access' => bp_is_home() ) );
	
	if ( !is_site_admin() )
		bp_core_new_subnav_item( array( 'name' => __( 'Delete Account', 'buddypress' ), 'slug' => 'delete-account', 'parent_url' => $settings_link, 'parent_slug' => 'settings', 'screen_function' => 'bp_core_screen_delete_account', 'position' => 90, 'user_has_access' => bp_is_home() ) );
  }
}

remove_action( 'wp', 'bp_core_add_settings_nav', 2 );
remove_action( 'admin_menu', 'bp_core_add_settings_nav', 2 );
add_action( 'wp', 'gfs_bp_core_add_settings_nav', 2 );
add_action( 'admin_menu', 'gfs_bp_core_add_settings_nav', 2 );
*/


// When a group has discussion board enabled, this function adds a panel to the home page notifying the current user of his subscription status to that group
function gfs_group_home_message() {
	global $current_user;
	global $group_obj;
	
if ($current_user->ID == 0)
	return;

	
	if ( function_exists ( 'bp_forums_get_forum_topics' ) )
		$group_id = bp_get_group_id(); // for bp 1.1
	else
		$group_id = $group_obj->id;
	
	$group = new BP_Groups_Group( $group_id );
//	$group_slug = $group->slug;
//	$group_slug = preg_replace('|[^a-z0-9_]|i', '', $group_slug); // Strips spaces

	$notification_page = site_url() . '/' . BP_MEMBERS_SLUG . '/' . $current_user->user_nicename . '/settings/notifications/';
	$noteslug = 'gfsub_' . $group_id;
	$group_subscribe = get_usermeta( $current_user->id, $noteslug );


	if ( bp_group_is_visible() && bp_group_is_forum_enabled() && function_exists( 'bp_forums_setup') ) { ?>
			<div class="bp-widget">
			<div class="info-group">
				<h4><?php _e( 'Forum subscription', 'group_forum_subscription' ); ?> </h4>
				<?php if ( $group_subscribe == 'yes' ) { ?>
					<p><strong><?php _e( 'You are currently subscribed to all discussions in this group.', 'group_forum_subscription' ) ?></strong></p>
				<?php } else { ?>
					<p><strong><?php _e( 'You are not subscribed to all discussions in this group.', 'group_forum_subscription' ) ?></strong></p>
				<?php } ?>
				<ul>
					<li style="list-style-type: square !important; margin: 5px 40px;">
						<?php if ( $group_subscribe == 'yes' ) { ?>
							<p><?php _e( 'To unsubscribe from specific discussions, click their titles.', 'group_forum_subscription' ) ?></p>
						<?php } else { ?>
							<p><?php _e( 'To subscribe to specific discussions, click their titles.', 'group_forum_subscription' ) ?></p>
						<?php } ?>
					</li>
					<li style="list-style-type: square !important; margin: 5px 40px;">
						<?php if ( bp_group_is_member()) { ?>
							<p><?php _e( 'Manage your group subscriptions at ', 'group_forum_subscription') ?><a href="<?php echo $notification_page; ?>"><?php _e( 'Notification Settings', 'group_forum_subscription') ?></a></p>
						<?php } else { ?>
							<p><?php _e( 'Join this group to subscribe automatically to all of its discussions.', 'group_forum_subscription' ) ?></p>
						<?php } ?>
					</li>
				</ul>
			</div>
			</div>
	<?php } ?>
<?php
}
add_action( 'groups_custom_group_fields', 'gfs_group_home_message' );
add_action( 'bp_before_group_active_topics', 'gfs_group_home_message' );
add_action( 'groups_forum_new_topic_before', 'gfs_group_home_message' );


// Ensures that the creator of a new group is subscribed to the group's forums
function gfs_new_group_subscribe_author($forum, $group_id) {
    global $current_user;
    $group_slug = groups_get_slug($group_id);
    $noteslug = 'gfsub_' . $group_id;
    update_usermeta($current_user->id, $noteslug, 'yes');
}
add_action( 'groups_new_group_forum', 'gfs_new_group_subscribe_author', 10, 2 );


// Subscribes user to topic
function gfs_bp_add_user_favorite ( $user_id, $topic_id ) {
		$user_faves = get_usermeta( $user_id, 'bb_favorites' );
		$user_faves = explode(',', $user_faves);
		$user_faves[] = $topic_id;
		$user_faves = implode(',', $user_faves);
		update_usermeta( $user_id, 'bb_favorites', $user_faves );
}


// Unsubscribes user from topic		
function gfs_bp_remove_user_favorite ( $user_id, $topic_id ) {
		$user_faves = get_usermeta( $user_id, 'bb_favorites' );
		$user_faves = explode(',', $user_faves);
		$key = array_search( $topic_id, $user_faves );
		unset($user_faves[$key]);
		$user_faves = implode(',', $user_faves);
		update_usermeta( $user_id, 'bb_favorites', $user_faves );
}


// Places a subscribed/not subscribed message on BP forum topic page, along with a Subscribe/Unsubscribe button
function gfs_topic_message() {
	global $current_user;
	$topic_id = bp_get_the_topic_id();
	
	if( $_POST[ 'gfs_unsubscribe' ] == '1') {
		gfs_bp_remove_user_favorite ( $current_user->id, $topic_id );
	}
	
	if( $_POST[ 'gfs_subscribe' ] == '1' ) {
		gfs_bp_add_user_favorite ( $current_user->id, $topic_id );
	}
	
	$topic_id = bp_get_the_topic_id();
	$user_faves = get_usermeta( $current_user->id, 'bb_favorites' );
	$user_faves = explode(',', $user_faves);
	if ( in_array ( $topic_id, $user_faves ) ) { ?>
		<p><?php _e( 'You are subscribed to this topic.', 'group_forum_subscription' ) ?>  
            	<input type="submit" name="Submit" value="<?php _e( 'Unsubscribe', 'group_forum_subscription' ) ?> &raquo;" style="width: 150px;" />
                <input type="hidden" name="gfs_unsubscribe" value="1" />
		<br /><br />
		<?php
		
		
	} else { ?>
		<p><?php _e( 'You are not subscribed to this topic.', 'group_forum_subscription' ) ?>
            	<input type="submit" name="Submit" value="<?php _e( 'Subscribe', 'group_forum_subscription' ) ?> &raquo;" style="width: 150px;" />
                <input type="hidden" name="gfs_subscribe" value="1" />
           <br /><br />
           <?php
	}
}
add_action( 'groups_forum_new_reply_before', 'gfs_topic_message' );

?>