<?php
/*
Plugin Name: azurecurve Display After Post Content
Plugin URI: http://wordpress.azurecurve.co.uk/plugins/display-after-post-content

Description: Allows insertion of content configured through admin panel to be displayed after the post content; works with shortcodes including Contact Form 7 and is multisite compatible.
Version: 1.0.0

Author: azurecurve
Author URI: http://wordpress.azurecurve.co.uk

Text Domain: azurecurve-display-after-post-content
Domain Path: /languages

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

The full copy of the GNU General Public License is available here: http://www.gnu.org/licenses/gpl.txt

*/

// Load text domain
function azc_dapc_load_plugin_textdomain(){
	$loaded = load_plugin_textdomain( 'azurecurve-display-after-post-content', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action('plugins_loaded', 'azc_dapc_load_plugin_textdomain');

// Load CSS
function azc_dapc_load_css(){
	wp_enqueue_style( 'azurecurve-display-after-post-content', plugins_url( 'style.css', __FILE__ ), '', '1.0.0' );
}
add_action('wp_enqueue_scripts', 'azc_dapc_load_css');

// Set Default Options
register_activation_hook( __FILE__, 'azc_dapc_set_default_options' );

function azc_dapc_set_default_options($networkwide) {
	
	$new_options = array(
				'azc_dapc_options' => ''
			);
	
	// set defaults for multi-site
	if (function_exists('is_multisite') && is_multisite()) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if ($networkwide) {
			global $wpdb;

			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			$original_blog_id = get_current_blog_id();

			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );

				if ( get_option( 'azc_dapc_options' ) === false ) {
					add_option( 'azc_dapc_options', $new_options );
				}
			}

			switch_to_blog( $original_blog_id );
		}else{
			if ( get_option( 'azc_dapc_options' ) === false ) {
				add_option( 'azc_dapc_options', $new_options );
			}
		}
		if ( get_site_option( 'azc_dapc_options' ) === false ) {
			add_site_option( 'azc_dapc_options', $new_options );
		}
	}
	//set defaults for single site
	else{
		if ( get_option( 'azc_dapc_options' ) === false ) {
			add_option( 'azc_dapc_options', $new_options );
		}
	}
}

// Add Action Link
function azc_dapc_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=azurecurve-display-after-post-content">'.__('Settings' ,'azurecurve-display-after-post-content').'</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}
add_filter('plugin_action_links', 'azc_dapc_plugin_action_links', 10, 2);

// Add Options Menu
function azc_dapc_settings_menu() {
	add_options_page( 'azurecurve Display After Post Content',
	'azurecurve Display After Post Content', 'manage_options',
	'azurecurve-display-after-post-content', 'azc_dapc_config_page' );
}
add_action( 'admin_menu', 'azc_dapc_settings_menu' );

// Options Page
function azc_dapc_config_page() {
	if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'azurecurve-display-after-post-content'));
    }
	
	// Retrieve plugin configuration options from database
	$options = get_option( 'azc_dapc_options' );
	?>
	<div id="azc-dapc-general" class="wrap">
		<fieldset>
			<h2>azurecurve Display After Post Content <?php _e('Options', 'azurecurve-display-after-post-content'); ?></h2>
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="azc_dapc_save_options" />
				<input name="page_options" type="hidden" value="display_after_post_content" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field( 'azc_dapc' ); ?>
				<table class="form-table">
				<tr><td>
					<p><?php _e('Enter the content which should be displayed after the post content; if left blank the network setting will be used.', 'azurecurve-display-after-post-content'); ?></p>
				</td></tr>
				<tr><td>
					<textarea name="display_after_post_content" rows="15" cols="80" id="display_after_post_content" class="regular-text code"><?php echo stripslashes($options['display_after_post_content'])?></textarea>
					<p class="description"><?php _e('The use of shortcodes (including those from other azurecurve plugins and Contact Form 7) is supported', 'azurecurve-display-after-post-content'); ?></em>
					</p>
				</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary"/>
			</form>
		</fieldset>
	</div>
<?php }

// Add action to process options
add_action( 'admin_init', 'azc_dapc_admin_init' );

function azc_dapc_admin_init() {
	add_action( 'admin_post_azc_dapc_save_options', 'azc_dapc_process_options' );
}

// Process Options
function azc_dapc_process_options() {
	// Check that user has proper security level
	if ( !current_user_can( 'manage_options' ) ){
		wp_die( __('You do not have permissions for this action', 'azurecurve-display-after-post-content'));
	}
	// Check that nonce field created in configuration form is present
	check_admin_referer( 'azc_dapc' );
	settings_fields('azc_dapc');
	
	// Retrieve original plugin options array
	$options = get_option( 'azc_dapc_options' );
	
	$option_name = 'display_after_post_content';
	if ( isset( $_POST[$option_name] ) ) {
		$options[$option_name] = ($_POST[$option_name]);
	}
	
	// Store updated options array to database
	update_option( 'azc_dapc_options', $options );
	
	// Redirect the page to the configuration form that was processed
	wp_redirect( add_query_arg( 'page', 'azurecurve-display-after-post-content', admin_url( 'options-general.php' ) ) );
	exit;
}

// Add Network Options Page to Menu
function azc_dapc_add_network_settings_page() {
	if (function_exists('is_multisite') && is_multisite()) {
		add_submenu_page(
			'settings.php',
			'azurecurve Display After Post Content Settings',
			'azurecurve Display After Post Content Suffix',
			'manage_network_options',
			'azurecurve-display-after-post-content',
			'azc_dapc_network_settings_page'
			);
	}
}
add_action('network_admin_menu', 'azc_dapc_add_network_settings_page');

// Network Settings Page
function azc_dapc_network_settings_page(){
	$options = get_site_option('azc_dapc_options');

	?>
	<div id="azc-dapc-general" class="wrap">
		<fieldset>
			<h2>azurecurve Display After Post Content Network <?php _e('Options', 'azurecurve-display-after-post-content'); ?></h2>
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="azc_dapc_save_options" />
				<input name="page_options" type="hidden" value="suffix" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field( 'azc_dapc' ); ?>
				<table class="form-table">
				<tr><td>
					<p><?php _e('Enter the content which should be displayed after the post content.', 'azurecurve-display-after-post-content'); ?></p>
				</td></tr>
				<tr><td>
					<textarea name="display_after_post_content" rows="15" cols="50" id="display_after_post_content" class="regular-text code"><?php echo stripslashes($options['display_after_post_content'])?></textarea>
					<p class="description"><?php _e('The use of shortcodes (including those from other azurecurve plugins and Contact Form 7) is supported', 'azurecurve-display-after-post-content'); ?></em>
					</p>
				</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary" />
			</form>
		</fieldset>
	</div>
	<?php
}

// Process Network Options
function process_azc_dapc_network_options(){     
	if(!current_user_can('manage_network_options')) wp_die(__('You do not have permissions to perform this action', 'azurecurve-display-after-post-content'));
	check_admin_referer('azc_dapc');
	
	// Retrieve original plugin options array
	$options = get_site_option( 'azc_dapc_options' );

	$option_name = 'display_after_post_content';
	if ( isset( $_POST[$option_name] ) ) {
		$options[$option_name] = ($_POST[$option_name]);
	}
	
	update_site_option( 'azc_dapc_options', $options );

	wp_redirect(network_admin_url('settings.php?page=azurecurve-display-after-post-content'));
	exit;  
}
add_action('network_admin_edit_update_azc_dapc_network_options', 'process_azc_dapc_network_options');

// Insert content after post content
function azc_dapc_display_after_post_content($content) {
        if(!is_feed() && !is_home() && is_single()) {
				$options = get_option( 'azc_dapc_options' );
				
				$display_after_post_content = '';
				if (strlen($options['display_after_post_content']) > 0){
					$display_after_post_content = stripslashes($options['display_after_post_content']);
				}else{
					$network_options = get_site_option( 'display_after_post_content' );
					if (strlen($network_options['display_after_post_content']) > 0){
						$display_after_post_content = stripslashes($network_options['display_after_post_content']);
					}
				}
				if (strlen($display_after_post_content) > 0){
					$content .= "<div class='azc_dapc'>".$display_after_post_content."</div>";
				}
        }
        return $content;
}
add_filter ('the_content', 'azc_dapc_display_after_post_content');

?>