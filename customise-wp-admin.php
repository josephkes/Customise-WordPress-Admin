<?php
/*
 * Plugin Name: Customise WP Admin
 * Plugin URI: https://github.com/josephkes/Customise-WordPress-Admin
 * Description: This plugin implements several modications to the WordPress admin in order to remove unnecessary features from the user interface.
 * Version: 1.0
 * Author: Joseph Kesisoglou
 * Author URI: http://josephkesisoglou.co.uk
 * License: GPL2
 */

/*  Copyright 2015  Joseph Kesisoglou  ( info[at]josephkesisoglou.co.uk)
 *
 *  This plugin is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License, version 2, as 
 *  published by the Free Software Foundation.
 *
 *  This plugin is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this plugin; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/*   --- CONTENTS ---
 *
 *   --- REMOVE ADMINISTRATION MENU ITEMS
 *   --- REMOVE ADMINISTRATION TOOLBAR ITEMS
 *   --- CHANGE DEFAULT WP GREATING ON ADMIN FOOTER
 *   --- ADDS A DATE/TIME DASHBOARD WIDGET
 *   --- REPLACE ACTIVITY WIDGET - INCLUDE RECENT CPTs
 */

// Remove menu items
function jk_remove_menus(){
  
    // Posts
    remove_menu_page( 'edit.php' );
    // Comments
    remove_menu_page( 'edit-comments.php' );
    // Projects
    remove_menu_page( 'edit.php?post_type=project' );
    // Testimonials
    remove_menu_page( 'edit.php?post_type=testimonial' );
    // Profile
    remove_menu_page( 'profile.php' );
    // Remove for all users below Administrator
    $user = wp_get_current_user();
    if ( ! $user->has_cap( 'manage_options' ) ) {
        // Tools
        remove_menu_page( 'tools.php' );
        // My Sites
        remove_menu_page( 'my-sites.php' );
    }
    // Remove for all users below Super-Admin
    $user = wp_get_current_user();
    if ( ! $user->has_cap( 'manage_network' ) ) {
        // Plugins
        remove_menu_page( 'plugins.php' );
        // Widgets submenu
        $page = remove_submenu_page( 'themes.php', 'widgets.php' );
    }
}
add_action( 'admin_menu', 'jk_remove_menus' );

//Remove toolbar items
function remove_wp_logo( $wp_admin_bar ) {
    //WordPress logo
	$wp_admin_bar->remove_node( 'wp-logo' );
    // Remove for all users below Administrator
    $user = wp_get_current_user();
    if ( ! $user->has_cap( 'manage_options' ) ) {
    // My Sites
    $wp_admin_bar->remove_node( 'my-sites' );
    }
    // Comments Moderation counter
    $wp_admin_bar->remove_node( 'comments' );
    // Add New Post Submenu
    $wp_admin_bar->remove_node( 'new-post' );
    // Add New Media Submenu
    $wp_admin_bar->remove_node( 'new-media' );
    // Add New Project Submenu
    $wp_admin_bar->remove_node( 'new-project' );
    // Add New Testimonial Submenu
    $wp_admin_bar->remove_node( 'new-testimonial' );
}
add_action( 'admin_bar_menu', 'remove_wp_logo', 999 );

// Change default WordPress greating on admin footer
if (! function_exists(' footer_thankyou') ){
function footer_thankyou () {
echo 'Running on Wordpress, modified by <a href="http://josephkesisoglou.co.uk" title="Joseph Kesisoglou" target="_blank">Joseph Kesisogou</a>';
    }
}
add_filter('admin_footer_text', 'footer_thankyou');

// Add a date/time dashboard widget
function jk_add_dashboard_widgets() {

	wp_add_dashboard_widget(
        // Widget slug
        'date_dashboard_widget',
        // Title
        'Date',
        // Display function
        'date_display_dashboard_widget'
        );	
}
add_action( 'wp_dashboard_setup', 'jk_add_dashboard_widgets' );

function date_display_dashboard_widget() {

	// Print the date and time
date_default_timezone_set("Europe/Athens");
echo '<h2>' . date("D, d M Y H:i:s") . '</h2>';
    
}

// Replace Activity widget to include recent custom post types
// unregister the default activity widget
add_action('wp_dashboard_setup', 'remove_dashboard_widgets' );
function remove_dashboard_widgets() {

    global $wp_meta_boxes;
    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);

}

// register your custom activity widget
add_action('wp_dashboard_setup', 'add_custom_dashboard_activity' );
function add_custom_dashboard_activity() {
    wp_add_dashboard_widget('custom_dashboard_activity', 'Activity', 'custom_wp_dashboard_site_activity');
}

// the new function based on wp_dashboard_recent_posts (in wp-admin/includes/dashboard.php)
function wp_dashboard_recent_post_types( $args ) {

// Changed from here
	if ( ! $args['post_type'] ) {
		$args['post_type'] = 'any';
	}

	$query_args = array(
		'post_type'      => $args['post_type'],

/* to here */

		'post_status'    => $args['status'],
		'orderby'        => 'date',
		'order'          => $args['order'],
		'posts_per_page' => intval( $args['max'] ),
		'no_found_rows'  => true,
		'cache_results'  => false
	);
	$posts = new WP_Query( $query_args );

	if ( $posts->have_posts() ) {

		echo '<div id="' . $args['id'] . '" class="activity-block">';

		if ( $posts->post_count > $args['display'] ) {
			echo '<small class="show-more hide-if-no-js"><a href="#">' . sprintf( __( 'See %s more…'), $posts->post_count - intval( $args['display'] ) ) . '</a></small>';
		}

		echo '<h4>' . $args['title'] . '</h4>';

		echo '<ul>';

		$i = 0;
		$today    = date( 'Y-m-d', current_time( 'timestamp' ) );
		$tomorrow = date( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) );

		while ( $posts->have_posts() ) {
			$posts->the_post();

			$time = get_the_time( 'U' );
			if ( date( 'Y-m-d', $time ) == $today ) {
				$relative = __( 'Today' );
			} elseif ( date( 'Y-m-d', $time ) == $tomorrow ) {
				$relative = __( 'Tomorrow' );
			} else {
				/* translators: date and time format for recent posts on the dashboard, see http://php.net/date */
				$relative = date_i18n( __( 'M jS' ), $time );
			}

 			$text = sprintf(
				/* translators: 1: relative date, 2: time, 4: post title */
 				__( '<span>%1$s, %2$s</span> <a href="%3$s">%4$s</a>' ),
  				$relative,
  				get_the_time(),
  				get_edit_post_link(),
  				_draft_or_post_title()
  			);

 			$hidden = $i >= $args['display'] ? ' class="hidden"' : '';
 			echo "<li{$hidden}>$text</li>";
			$i++;
		}

		echo '</ul>';
		echo '</div>';

	} else {
		return false;
	}

	wp_reset_postdata();

	return true;
}

// The replacement widget
function custom_wp_dashboard_site_activity() {

    echo '<div id="activity-widget">';

    $future_posts = wp_dashboard_recent_post_types( array(
        'post_type'  => 'any',
        'display' => 3,
        'max'     => 5,
        'status'  => 'future',
        'order'   => 'ASC',
        'title'   => __( 'Publishing Soon' ),
        'id'      => 'future-posts',
    ) );

    $recent_posts = wp_dashboard_recent_post_types( array(
        'post_type'  => 'any',
        'display' => 5,
        'max'     => 5,
        'status'  => 'publish',
        'order'   => 'DESC',
        'title'   => __( 'Recently Published' ),
        'id'      => 'published-posts',
    ) );

    $recent_comments = wp_dashboard_recent_comments( 10 );

    if ( !$future_posts && !$recent_posts && !$recent_comments ) {
        echo '<div class="no-activity">';
        echo '<p class="smiley"></p>';
        echo '<p>' . __( 'No activity yet!' ) . '</p>';
        echo '</div>';
    }

    echo '</div>';
}

?>
