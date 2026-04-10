<?php
/**
 * Plugin Name:       MaxLimits - Increase Maximum Upload, Post & PHP Limits
 * Description:       Easily increase max upload size, post size, and PHP limits. A user-friendly solution for common WordPress limit issues.
 * Version:           1.8.0
 * Author:            DominoPress
 * Author URI:        https://dominopress.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       maxlimits-increase-maximum-limits
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Tested up to:      6.9
 * Stable tag:        1.8.0
 * Tags:              max upload size, php limits, memory limit, execution time, max_input_vars
 */

if (!defined('ABSPATH')) {
	exit;
}





define('MAXLIMITS_VERSION', '1.8.0');
define('MAXLIMITS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAXLIMITS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MAXLIMITS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load Classes
require_once MAXLIMITS_PLUGIN_DIR . 'includes/class-maxlimits-core.php';
require_once MAXLIMITS_PLUGIN_DIR . 'includes/class-maxlimits-admin.php';
require_once MAXLIMITS_PLUGIN_DIR . 'includes/class-maxlimits-insights.php';


// Initialize Core (Applying Limits)
MaxLimits_Core::instance();

// Initialize Insights (Tracking)
new MaxLimits_Insights();

// Initialize Promoter (Cross-promotion)
if ( file_exists( MAXLIMITS_PLUGIN_DIR . 'includes/class-maxlimits-promoter.php' ) ) {
    require_once MAXLIMITS_PLUGIN_DIR . 'includes/class-maxlimits-promoter.php';
    new MaxLimits_Promoter();
}

// Initialize Admin UI
new MaxLimits_Admin();

// Initialize Recovery logic (PRO)



// --- SUPPORT LINKS ---
add_filter('plugin_action_links_' . MAXLIMITS_PLUGIN_BASENAME, 'maxlimits_add_action_links');
function maxlimits_add_action_links($links)
{
	$slug = (defined('MAXLIMITS_IS_PRO') && MAXLIMITS_IS_PRO) ? 'maxlimits-pro' : 'maxlimits-increase-maximum-limits';
	$settings_link = '<a href="admin.php?page=' . $slug . '">' . __('Settings', 'maxlimits-increase-maximum-limits') . '</a>';
	$rate_link = '<a href="https://wordpress.org/support/plugin/maxlimits-increase-maximum-limits/reviews/#new-post" target="_blank" style="font-weight: bold; color: #2271b1;">' . __('Rate Plugin', 'maxlimits-increase-maximum-limits') . '</a>';

	array_unshift($links, $settings_link);
	$links[] = $rate_link;

	return $links;
}


// --- ACTIVATION & TRACKING ---
/**
 * Activation Hook
 */
register_activation_hook(__FILE__, 'maxlimits_activate');
function maxlimits_activate() {
    // Set first activation time if not set (for promoter rotation)
    if (!get_option('maxlimits_first_activated')) {
        update_option('maxlimits_first_activated', time());
    }

    // Set default settings if they don't exist
    if (!get_option('maxlimits_iml_settings')) {
        update_option('maxlimits_iml_settings', [
            'upload_max_filesize' => '128',
            'post_max_size'       => '128',
            'memory_limit'        => '512',
            'max_execution_time'  => '300',
            'max_input_time'      => '300',
            'max_input_vars'      => '3000',
        ]);
    }
	// Indicate that the plugin has just been activated (for tracking)
	update_option('maxlimits_just_activated', 'yes');

	// Create an instance of Insights to potentially send 'active' event 
	// (though in WP admin it will be handled by the constructor's admin_init)

	// Restore .htaccess rules if they previously enabled direct writing
	$core = MaxLimits_Core::instance();
	$advanced = get_option($core->advanced_option_name, []);
	$limits = get_option($core->limit_option_name, []);
	
	if (!empty($advanced['write_to_htaccess'])) {
		if (!function_exists('get_home_path')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$htaccess_file = get_home_path() . '.htaccess';
		if (file_exists($htaccess_file) && is_writable($htaccess_file)) {
			if (!function_exists('insert_with_markers')) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			$lines = [];
			$map = [
				'upload_max_filesize' => 'php_value upload_max_filesize',
				'post_max_size'       => 'php_value post_max_size',
				'memory_limit'        => 'php_value memory_limit',
				'max_execution_time'  => 'php_value max_execution_time',
				'max_input_time'      => 'php_value max_input_time',
				'max_input_vars'      => 'php_value max_input_vars',
			];
			foreach ($map as $key => $directive) {
				if (!empty($limits[$key])) {
					$suffix = in_array($key, ['upload_max_filesize', 'post_max_size', 'memory_limit']) ? 'M' : '';
					$lines[] = "{$directive} {$limits[$key]}{$suffix}";
				}
			}
			if (!empty($lines)) {
				insert_with_markers($htaccess_file, 'MaxLimits', $lines);
			}
		}
	}
}
// --- ACTIVATION & CONFLICT CHECK ---



register_deactivation_hook(__FILE__, 'maxlimits_deactivate');
function maxlimits_deactivate()
{
	// Delete notice related transients
	delete_transient('maxlimits_insights_notice_dismissed');

	// Only track deactivation if the user had previously allowed it
	if (get_option('maxlimits_insights_consent') === 'yes') {
		$insights = new MaxLimits_Insights();
		$insights->send_event('deactive');
	}

	// clear .htaccess rules on deactivation so the user must keep the plugin active
	if (!function_exists('get_home_path')) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	$htaccess_file = get_home_path() . '.htaccess';
	if (file_exists($htaccess_file) && is_writable($htaccess_file)) {
		insert_with_markers($htaccess_file, 'MaxLimits', []);
	}
}