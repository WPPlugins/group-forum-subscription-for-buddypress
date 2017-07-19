<?php 

/**
 * Plugin Name: Group Forum Subscripton for BuddyPress
 * Plugin Description: Gives users the ability to subscribe to email notifications of discussion activity in their BuddyPress groups
 * Author: Boone Gorges
 * Author URI: http://teleogistic.net
 * Plugin URI: http://dev.commons.gc.cuny.edu
 * Version: 1.4.1
 */


function group_forum_subscription_for_buddypress_init() {
	require( dirname( __FILE__ ) . '/group-forum-subscription-for-buddypress-bp-functions.php' );
}
add_action( 'bp_init', 'group_forum_subscription_for_buddypress_init' );

// Loads translation files

function group_forum_subscription_init () {
	$plugin_dir = basename(dirname(__FILE__));
	$locale = get_locale();
	$mofile = WP_PLUGIN_DIR . "/group-forum-subscription-for-buddypress/languages/group-forum-subscription-$locale.mo";
      
      if ( file_exists( $mofile ) )
      		load_textdomain( 'group_forum_subscription', $mofile );
}

add_action ('plugins_loaded', 'group_forum_subscription_init');

?>