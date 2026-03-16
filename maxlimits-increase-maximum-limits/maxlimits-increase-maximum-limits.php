<?php
/**
 * Plugin Name:       MaxLimits - Increase Maximum Upload, Post & PHP Limits
 * Description:       Easily increase max upload size, post size, and PHP limits. A user-friendly solution for common WordPress limit issues.
 * Version:           1.6.0
 * Author:            DominoPress
 * Author URI:        https://dominopress.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       maxlimits-increase-maximum-limits
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Tested up to:      6.9
 * Stable tag:        1.6.0
 * Tags:              max upload size, php limits, memory limit, execution time, max_input_vars
 */

if (!defined('ABSPATH')) {
	exit;
}

define('MAXLIMITS_VERSION', '1.6.0');
define('MAXLIMITS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAXLIMITS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MAXLIMITS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load Classes
require_once MAXLIMITS_PLUGIN_DIR . 'includes/class-maxlimits-core.php';
require_once MAXLIMITS_PLUGIN_DIR . 'includes/class-maxlimits-admin.php';
require_once MAXLIMITS_PLUGIN_DIR . 'includes/class-maxlimits-notice.php';
require_once MAXLIMITS_PLUGIN_DIR . 'includes/class-maxlimits-insights.php';
require_once MAXLIMITS_PLUGIN_DIR . 'includes/class-maxlimits-recovery.php';

// Initialize Core (Applying Limits)
MaxLimits_Core::instance();

// Initialize Notice Helper
MaxLimits_Notice::instance();

// Initialize Insights (Tracking)
new MaxLimits_Insights();

// Initialize Admin UI
new MaxLimits_Admin();

// Initialize Recovery
new MaxLimits_Recovery();


// --- SUPPORT LINKS ---
add_filter('plugin_action_links_' . MAXLIMITS_PLUGIN_BASENAME, 'maxlimits_add_action_links');
function maxlimits_add_action_links($links)
{
	$settings_link = '<a href="admin.php?page=maxlimits-increase-maximum-limits">' . __('Settings', 'maxlimits-increase-maximum-limits') . '</a>';
	$rate_link = '<a href="https://wordpress.org/support/plugin/maxlimits-increase-maximum-limits/reviews/#new-post" target="_blank" style="font-weight: bold; color: #2271b1;">' . __('Rate Plugin', 'maxlimits-increase-maximum-limits') . '</a>';

	array_unshift($links, $settings_link);
	$links[] = $rate_link;

	return $links;
}


// --- ACTIVATION & TRACKING ---
register_activation_hook(__FILE__, 'maxlimits_activate');
function maxlimits_activate()
{
	// Indicate that the plugin has just been activated (for tracking)
	update_option('maxlimits_just_activated', 'yes');

	// Create an instance of Insights to potentially send 'active' event 
	// (though in WP admin it will be handled by the constructor's admin_init)
}

register_deactivation_hook(__FILE__, 'maxlimits_deactivate');
function maxlimits_deactivate()
{
	// Delete notice related transients
	delete_transient('maxlimits_shop_notice_data');
	delete_transient('maxlimits_insights_notice_dismissed');

	// Only track deactivation if the user had previously allowed it
	if (get_option('maxlimits_insights_consent') === 'yes') {
		$insights = new MaxLimits_Insights();
		$insights->send_event('deactive');
	}
}