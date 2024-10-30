<?php
/*
Plugin Name: Limit Comments Per Day
Plugin URI: 
Description: Limit Comments Per Day limits the number of comments users can make per day. The daily maximum limit is variable and may be specified by the administrator.
Version: 1.0.0
Author: Cryptidcurrency
Author URI: Cryptidcurrency.org
License: GPLv2
*/

define('LC_TEXT_DOMAIN', 'limit_comment');

/**
 * Hook to register menu
 */
function lc_admin_menu() {
	add_options_page(__('Limit Comments', LC_TEXT_DOMAIN), __('Limit Comments', LC_TEXT_DOMAIN), 'manage_options', 'limit-comment', 'lc_settings_page');
}
add_action('admin_menu', 'lc_admin_menu');

/**
 * Render settings page
 */
function lc_settings_page() {

	if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
		if ( isset($_POST['save-settings']) ) {
			$settings = stripslashes_deep($_POST['d']);

			update_option('lc_settings', $settings);
			echo '<div id="message" class="updated fade"><p>Saved Changes</p></div>';
		}
	}

	$d = get_option('lc_settings', array());

	?>
<div class="wrap">
	<h2><?php _e('Limit Comments') ?></h2>

	<form action="" method="post">
		<table class="form-table">
			<tr>
				<th>Number of comments per DAY</th>
				<td><input type="text" name="d[comments_per_day]" class="code short" size="5" value="<?php echo esc_attr($d['comments_per_day']) ?>" /></td>
			</tr>
			<tr>
				<th>Message when user reach the limit</th>
				<td><textarea name="d[comments_per_day_note]" class="widefat" cols="55" rows="4"><?php echo esc_html($d['comments_per_day_note']) ?></textarea></td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" class="button-primary" name="save-settings" value="Save Changes" />
		</p>
	</form>
	
</div>
	<?php
}

function lc_check_limits() {
	global $wpdb;

	$settings = get_option('lc_settings', array());
	$limit = (int) $settings['comments_per_day'];

	if ( $limit > 0 ) {
		$comment_count = $wpdb->get_var($wpdb->prepare("
			SELECT COUNT(*)
			FROM $wpdb->comments
			WHERE user_id = %d
				AND comment_date >= DATE_SUB(NOW(),INTERVAL 1 DAY)"
			, get_current_user_id() )
		);
		
		if ( $comment_count >= $limit ) {
			$GLOBALS['__lc_show_limit_comments_message'] = true;
			return false;
		}
	}

	return true;
}
//add_filter('comments_open', 'lc_check_limits');

function lc_show_limit_note() {
	global $__lc_show_limit_comments_message;

	if ( $__lc_show_limit_comments_message ) {
		$settings = get_option('lc_settings', array());

		echo wpautop($settings['comments_per_day_note']);
	}

}
add_action('comment_form_comments_closed', 'lc_show_limit_note');

function lc_check_limit_on_post($comment_post_ID) {
	if ( !lc_check_limits() ) {
		$settings = get_option('lc_settings', array());

		wp_die( __($settings['comments_per_day_note']) );
	}
}
add_action('pre_comment_on_post', 'lc_check_limit_on_post');
