<?php
/**
 * MaxLimits Uninstall
 *
 * This file is called when the plugin is deleted from the WordPress admin.
 *
 * @package MaxLimits
 * @since 1.2.7
 */

// If uninstall not called from WordPress, die.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user consented to tracking
if (get_option('maxlimits_insights_consent') === 'yes') {
    $api_url = 'https://dominopress.com/api/woocommerce/events';
    global $wp_version;

    $theme = wp_get_theme();
    $data = [
        'site_url' => (string) get_site_url(),
        'date' => (string) current_time('mysql'),
        'plugin_slug' => 'maxlimits',
        'plugin_version' => defined('MAXLIMITS_VERSION') ? MAXLIMITS_VERSION : '1.5.0',
        'event_type' => 'delete',
        'wp_version' => (string) $wp_version,
        'country' => (string) get_option('woocommerce_default_country', ''),
        'meta' => [
            'theme' => $theme->get('Name') . ' ' . $theme->get('Version'),
            'server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '',
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
        ]
    ];

    wp_remote_post($api_url, [
        'method' => 'POST',
        'timeout' => 15,
        'blocking' => true,
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer z1yN6YtKp3dQw8VhR2cU9mlb7XjA4DFMG0He9qPiDoSrTuWvYlZkBnCfDgEhJiY5',
        ],
        'body' => wp_json_encode($data),
    ]);
}

// Cleanup plugin data
delete_option('maxlimits_insights_consent');
delete_option('maxlimits_install_event_sent');
delete_option('maxlimits_version_tracked');
delete_option('maxlimits_just_activated');
delete_transient('maxlimits_insights_notice_dismissed');
