<?php

if (!defined('ABSPATH')) {
    exit;
}

class MaxLimits_Notice
{

    private static $instance = null;
    private $api_base = 'https://dominopress.com/api/woocommerce';
    private $plugin_slug = 'maxlimits';

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('wp_footer', [$this, 'render_notice']);
        add_action('admin_notices', [$this, 'render_notice']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_maxlimits_close_notice', [$this, 'ajax_close_notice']);
        add_action('wp_ajax_nopriv_maxlimits_close_notice', [$this, 'ajax_close_notice']);
        add_action('wp_ajax_maxlimits_notice_interaction', [$this, 'noticeInteraction']);
        add_action('wp_ajax_nopriv_maxlimits_notice_interaction', [$this, 'noticeInteraction']);
    }

    public function enqueue_assets()
    {
        wp_enqueue_script('jquery');
    }

    public function render_notice()
    {
        if (get_transient('maxlimits_shop_notice_data')) {
            delete_transient('maxlimits_shop_notice_data');
        }

        $cached = get_transient('maxlimits_shop_notice_data');
        $shop_url = parse_url(get_site_url(), PHP_URL_HOST);

        if (false === $cached) {
            $api_url = add_query_arg([
                'shop_url'    => $shop_url,
                'plugin_slug' => $this->plugin_slug,
            ], $this->api_base . '/shop/notice');

            $response = wp_remote_get($api_url, [
                'timeout' => 5,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer z1yN6YtKp3dQw8VhR2cU9mlb7XjA4DFMG0He9qPiDoSrTuWvYlZkBnCfDgEhJiY5',
                ],
            ]);

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                // Cache failure for a shorter time to retry later
                set_transient('maxlimits_shop_notice_data', 'empty', 15 * MINUTE_IN_SECONDS);
                return;
            }

            $body_content = wp_remote_retrieve_body($response);
            $data = json_decode($body_content, true);

            // Check for is_enabled at both top level and notice level for robustness
            $api_is_enabled = false;
            if (isset($data['notice']['is_enabled'])) {
                $api_is_enabled = (bool) $data['notice']['is_enabled'];
            } elseif (isset($data['is_enabled'])) {
                $api_is_enabled = (bool) $data['is_enabled'];
            }

            if (!isset($data['notice']) || empty($data['notice']) || !$api_is_enabled) {
                // Cache empty result
                set_transient('maxlimits_shop_notice_data', 'empty', 15 * MINUTE_IN_SECONDS);
                return;
            }

            // Cache valid result for 1 hour
            $cached = $data['notice'];
            set_transient('maxlimits_shop_notice_data', $cached, HOUR_IN_SECONDS);
        }

        if (empty($cached) || !is_array($cached) || $cached === 'empty') {
            return;
        }

        $notice = $cached;
        $notice_id = isset($notice['id']) ? (string) $notice['id'] : '';
        $notice_body = isset($notice['body']) ? (string) $notice['body'] : '';
        $is_enabled = isset($notice['is_enabled']) ? (bool) $notice['is_enabled'] : true;

        if (empty($notice_id) || empty($notice_body) || !$is_enabled) {
            return;
        }
        ?>
        <style>
            #maxlimits-shop-notice {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-left-width: 4px;
                border-left-color: #2271b1;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                padding: 10px 60px 10px 30px;
                position: relative;
                margin: 20px 20px 20px 0;
                display: flex;
                align-items: flex-start;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                border-radius: 4px;
                overflow: hidden;
            }


            /* Frontend specific positioning */
            body:not(.wp-admin) #maxlimits-shop-notice {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 99999;
                max-width: 400px;
                margin: 0;
                animation: maxlimitsNoticePop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }

            @keyframes maxlimitsNoticePop {
                from {
                    transform: translateX(50px);
                    opacity: 0;
                }

                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            #maxlimits-shop-notice .notice-content h1,
            #maxlimits-shop-notice .notice-content h2,
            #maxlimits-shop-notice .notice-content h3,
            #maxlimits-shop-notice .notice-content p strong:first-child {
                font-size: 22px;
                margin-bottom: 12px;
                font-weight: 700;
                letter-spacing: -0.02em;
            }

            #maxlimits-shop-notice .notice-content h2 {
                margin: 0 !important;
            }

            #maxlimits-shop-notice .notice-content p {
                margin: 0 0 16px 0;
            }

            #maxlimits-shop-notice .notice-content a,
            #maxlimits-shop-notice .notice-content a * {
                color: #ffffff !important;
                text-decoration: none !important;
                margin: 0 !important;
            }

            #maxlimits-shop-notice .notice-content a {
                display: inline-flex;
                align-items: center;
                padding: 14px 28px;
                background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
                border-radius: 10px;
                font-weight: 700;
                font-size: 16px;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
                margin-top: 10px;
                cursor: pointer;
                border: none;
            }

            #maxlimits-shop-notice .notice-content a:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
                filter: brightness(1.1);
            }

            #maxlimits-shop-notice .notice-content {
                color: #3c434a !important;
                font-size: 14px;
                line-height: 1.6;
                flex-grow: 1;
            }

            #maxlimits-shop-notice .notice-content p strong {
                font-size: 15px;
                margin-bottom: 4px;
                color: #1d2327;
            }

            #maxlimits-shop-notice .close-notice {
                position: absolute;
                top: 10px;
                right: 10px;
                background: none;
                border: none;
                color: #646970;
                width: 25px;
                height: 25px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: color 0.1s ease-in-out;
                padding: 0;
                font-size: 20px;
            }

            #maxlimits-shop-notice .close-notice:hover {
                color: #d63638;
            }

            /* Hide on mobile for frontend if preferred, but keep for admin */
            @media screen and (max-width: 600px) {
                body:not(.wp-admin) #maxlimits-shop-notice {
                    left: 10px;
                    right: 10px;
                    max-width: none;
                }
            }
        </style>
        <div id="maxlimits-shop-notice">
            <div class="notice-content">
                <?php echo wp_kses_post($notice_body); ?>
            </div>
            <button class="close-notice" aria-label="Close Notice">&times;</button>
        </div>
        <script>
            jQuery(document).ready(function ($) {
                const $notice = $('#maxlimits-shop-notice');
                const noticeId = '<?php echo esc_js($notice_id); ?>';
                const shopUrl = '<?php echo esc_js($shop_url); ?>';
                const pluginSlug = '<?php echo esc_js($this->plugin_slug); ?>';
                const nonce = '<?php echo wp_create_nonce('maxlimits_notice_nonce'); ?>';
                const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';

                function trackInteraction(type) {
                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'maxlimits_notice_interaction',
                            shop_url: shopUrl,
                            plugin_slug: pluginSlug,
                            notice_id: noticeId,
                            type: type,
                            nonce: nonce
                        }
                    });
                }

                // Track View
                trackInteraction('view');

                // Track Click
                $notice.on('click', '.notice-content a', function() {
                    trackInteraction('click');
                });

                // Track Close
                $notice.find('.close-notice').on('click', function () {
                    trackInteraction('close');

                    $notice.fadeOut(300, function () {
                        $(this).remove();
                    });

                    // Send update to API (disable notice)
                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'maxlimits_close_notice',
                            shop_url: shopUrl,
                            plugin_slug: pluginSlug,
                            body: <?php echo json_encode($notice_body); ?>,
                            is_enabled: 0,
                            nonce: nonce
                        }
                    });
                });
            });
        </script>
        <?php
    }

    public function ajax_close_notice()
    {
        check_ajax_referer('maxlimits_notice_nonce', 'nonce');

        $shop_url_raw = isset($_POST['shop_url']) ? sanitize_text_field((string) $_POST['shop_url']) : '';
        // Strip protocol if present, otherwise use as-is
        $shop_url = parse_url($shop_url_raw, PHP_URL_HOST) ?: $shop_url_raw;
        $plugin_slug = isset($_POST['plugin_slug']) ? sanitize_text_field((string) $_POST['plugin_slug']) : '';
        $body = isset($_POST['body']) ? wp_kses_post((string) $_POST['body']) : '';
        $is_enabled = isset($_POST['is_enabled']) ? (int) $_POST['is_enabled'] : 0;

        $api_url = $this->api_base . '/save/notice';

        wp_remote_post($api_url, [
            'body' => json_encode([
                'shop_url' => $shop_url,
                'plugin_slug' => $plugin_slug,
                'body' => $body,
                'is_enabled' => (bool) $is_enabled,
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer z1yN6YtKp3dQw8VhR2cU9mlb7XjA4DFMG0He9qPiDoSrTuWvYlZkBnCfDgEhJiY5',
            ],
            'timeout' => 5,
            'blocking' => false, // No need to wait for response
        ]);

        delete_transient('maxlimits_shop_notice_data');

        wp_send_json_success();
    }

    public function noticeInteraction()
    {
        check_ajax_referer('maxlimits_notice_nonce', 'nonce');

        $shop_url_raw = isset($_POST['shop_url']) ? sanitize_text_field((string) $_POST['shop_url']) : '';
        $shop_url = parse_url($shop_url_raw, PHP_URL_HOST) ?: $shop_url_raw;
        $plugin_slug = isset($_POST['plugin_slug']) ? sanitize_text_field((string) $_POST['plugin_slug']) : '';

        $type = isset($_POST['type']) ? sanitize_text_field((string) $_POST['type']) : '';
        $notice_id = isset($_POST['notice_id']) ? (int) $_POST['notice_id'] : 0;

        $api_url = $this->api_base . '/shop/notice/interaction';

        $payload = [
            'shop_url'    => $shop_url,
            'plugin_slug' => $plugin_slug,
            'notice_id'   => $notice_id,
            'view'        => ($type === 'view' ? 1 : 0),
            'click'       => ($type === 'click' ? 1 : 0),
            'close'       => ($type === 'close' ? 1 : 0),
            'date'        => current_time('Y-m-d'),
        ];

        wp_remote_post($api_url, [
            'body'    => json_encode($payload),
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer z1yN6YtKp3dQw8VhR2cU9mlb7XjA4DFMG0He9qPiDoSrTuWvYlZkBnCfDgEhJiY5',
            ],
            'timeout'  => 5,
            'blocking' => false,
        ]);

        wp_send_json_success();
    }
}
