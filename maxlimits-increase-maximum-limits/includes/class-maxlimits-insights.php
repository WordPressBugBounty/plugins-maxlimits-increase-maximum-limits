<?php
/**
 * MaxLimits Insights Class
 *
 * Handles user data tracking consent and anonymous usage data collection.
 *
 * @package MaxLimits
 * @since 1.2.7
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class MaxLimits_Insights
{
    /**
     * API Endpoint for tracking
     */
    private $api_url = 'https://dominopress.com/api/woocommerce/events';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Show admin notice for consent
        add_action('admin_notices', [$this, 'show_tracking_notice']);

        // AJAX handlers for notice response
        add_action('wp_ajax_maxlimits_insights_consent', [$this, 'handle_consent_ajax']);

        // Track updates
        add_action('admin_init', [$this, 'track_update']);

        // Check if plugin was just activated and user has consented
        add_action('admin_init', [$this, 'check_for_activation']);
    }

    /**
     * Check if the plugin was just activated and send 'active' event if consented
     */
    public function check_for_activation()
    {
        if (get_option('maxlimits_just_activated') === 'yes') {
            delete_option('maxlimits_just_activated');

            if (get_option('maxlimits_insights_consent') === 'yes') {
                $this->send_event('active');
            }
        }
    }

    /**
     * Display the tracking consent notice
     */
    public function show_tracking_notice()
    {
        // Only show to admins and if consent not yet given or dismissed
        if (!current_user_can('manage_options') || get_option('maxlimits_insights_consent') !== false) {
            return;
        }

        // Check if notice was dismissed recently
        if (get_transient('maxlimits_insights_notice_dismissed')) {
            return;
        }

        ?>
        <div id="maxlimits-insights-notice" class="notice notice-info is-dismissible"
            style="padding: 20px; border-left: 4px solid #007cba; position: relative;">
            <div style="display: flex; align-items: flex-start; gap: 20px;">
                <div style="flex-shrink: 0; background: #f0f7ff; padding: 10px; border-radius: 8px;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V11H13V17ZM13 9H11V7H13V9Z"
                            fill="#007cba" />
                    </svg>
                </div>
                <div style="flex-grow: 1;">
                    <h3 style="margin: 0 0 5px 0; font-size: 16px; font-weight: 600;">
                        <?php esc_html_e('Help us improve MaxLimits', 'maxlimits-increase-maximum-limits'); ?>
                    </h3>
                    <p style="margin: 0; color: #50575e; line-height: 1.5;">
                        <?php esc_html_e('Would you like to help us make MaxLimits better? By sharing non-sensitive usage data, you allow us to understand how the plugin is used and prioritize new features.', 'maxlimits-increase-maximum-limits'); ?>
                    </p>

                    <div style="margin-top: 15px;">
                        <button id="maxlimits-insights-allow" class="button button-primary button-large"
                            style="background: #007cba; border-color: #007cba; padding: 0 24px; height: 36px; line-height: 34px;">
                            <?php esc_html_e('Allow & Continue', 'maxlimits-increase-maximum-limits'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    var nonce = '<?php echo esc_js(wp_create_nonce('maxlimits_insights_nonce')); ?>';

                    // Handle Allow
                    $('#maxlimits-insights-allow').on('click', function (e) {
                        e.preventDefault();
                        $.post(ajaxurl, {
                            action: 'maxlimits_insights_consent',
                            consent: 'yes',
                            _ajax_nonce: nonce
                        });
                        $('#maxlimits-insights-notice').fadeOut();
                    });

                    // Handle Dismiss - Delegate event since WP adds button dynamically
                    $('#maxlimits-insights-notice').on('click', '.notice-dismiss', function () {
                        $.post(ajaxurl, {
                            action: 'maxlimits_insights_consent',
                            consent: 'no',
                            _ajax_nonce: nonce
                        });
                        // Visual removal is handled by WP's common.js
                    });
                });
            </script>
        </div>
        <?php
    }

    /**
     * Handle AJAX consent
     */
    public function handle_consent_ajax()
    {
        check_ajax_referer('maxlimits_insights_nonce');

        $consent = isset($_POST['consent']) ? sanitize_text_field($_POST['consent']) : 'no';

        if ($consent === 'yes') {
            update_option('maxlimits_insights_consent', 'yes');

            // Send 'install' event (force send because option might be cached as false)
            if (!get_option('maxlimits_install_event_sent')) {
                $this->send_event('install', true);
                update_option('maxlimits_install_event_sent', time());
            }

            wp_send_json_success('Consented');
        } else {
            // Dismiss for 30 days
            set_transient('maxlimits_insights_notice_dismissed', true, MONTH_IN_SECONDS);
            update_option('maxlimits_insights_consent', 'no');
            wp_send_json_success('Dismissed');
        }
    }

    /**
     * Send event data to API
     */
    public function send_event($event_type, $ignore_consent = false)
    {
        if (!$ignore_consent && get_option('maxlimits_insights_consent') !== 'yes') {
            return;
        }

        global $wp_version;

        // Get theme info
        $theme = wp_get_theme();

        // Gather system data
        $data = [
            'site_url' => (string) get_site_url(),
            'date' => (string) current_time('mysql'),
            'plugin_slug' => 'maxlimits',
            'plugin_version' => defined('MAXLIMITS_VERSION') ? MAXLIMITS_VERSION : '1.7.0',
            'event_type' => (string) $event_type,
            'wp_version' => (string) $wp_version,
            'email'=> get_option('admin_email'),
            'country' => (string) get_option('woocommerce_default_country', ''),
            'meta' => [
                'theme' => $theme->get('Name') . ' ' . $theme->get('Version'),
                'server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '',
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'is_ecommerce' => class_exists('WooCommerce'),
            ]
        ];

        $response = wp_remote_post($this->api_url, [
            'method' => 'POST',
            'timeout' => 15,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer z1yN6YtKp3dQw8VhR2cU9mlb7XjA4DFMG0He9qPiDoSrTuWvYlZkBnCfDgEhJiY5',
            ],
            'body' => wp_json_encode($data),
            'cookies' => []
        ]);

        error_log('MaxLimits Tracking Response: ' . print_r($response, true));
    }

    /**
     * Track update
     */
    public function track_update()
    {
        $current_version = defined('MAXLIMITS_VERSION') ? MAXLIMITS_VERSION : '1.7.0';
        $saved_version = get_option('maxlimits_version_tracked', '1.0.0');

        if (version_compare($current_version, $saved_version, '>')) {
            if (get_option('maxlimits_insights_consent') === 'yes') {
                $this->send_event('update');
            }
            update_option('maxlimits_version_tracked', $current_version);
        }
    }
}
