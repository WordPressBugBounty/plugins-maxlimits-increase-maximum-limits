<?php

if (!defined('ABSPATH')) {
    exit;
}

class MaxLimits_Admin
{

    private $core;
    private $menu_slug;

    public function __construct()
    {
        $this->menu_slug = (defined('MAXLIMITS_IS_PRO') && MAXLIMITS_IS_PRO) ? 'maxlimits-pro' : 'maxlimits-increase-maximum-limits';
        $this->core = MaxLimits_Core::instance();

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_maxlimits_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_maxlimits_get_server_limits', [$this, 'ajax_get_server_limits']);
        add_action('wp_ajax_maxlimits_tracking_consent', [$this, 'ajax_handle_tracking_consent']);

        add_action('wp_dashboard_setup', [$this, 'register_dashboard_widget']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Register REST API routes for Other Plugins proxy.
     */
    public function register_rest_routes()
    {
        register_rest_route('maxlimits/v1', '/other-plugins', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_other_plugins_proxy'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);
    }

    /**
     * Proxy for DominoPress plugins list.
     */
    public function get_other_plugins_proxy()
    {
        $transient_key = 'maxlimits_other_plugins_cache';
        $cached_data   = get_transient($transient_key);

        if (false !== $cached_data) {
            return rest_ensure_response($cached_data);
        }

        $response = wp_remote_get('https://dominopress.com/api/wp-plugins', [
            'timeout'   => 15,
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('fetch_failed', 'Failed to fetch plugins from DominoPress', ['status' => 500]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || !isset($data['success'])) {
            return new WP_Error('invalid_data', 'Invalid response from DominoPress API', ['status' => 500]);
        }

        set_transient($transient_key, $data, 12 * HOUR_IN_SECONDS);
        return rest_ensure_response($data);
    }

    public function add_admin_menu()
    {
        add_menu_page(
            __('MaxLimits Settings', 'maxlimits-increase-maximum-limits'),
            __('MaxLimits', 'maxlimits-increase-maximum-limits'),
            'manage_options',
            $this->menu_slug,
            [$this, 'render_page'],
            'dashicons-performance' // Icon
        );

        add_submenu_page(
            $this->menu_slug,
            __('Increase Limits', 'maxlimits-increase-maximum-limits'),
            __('Increase Limits', 'maxlimits-increase-maximum-limits'),
            'manage_options',
            $this->menu_slug,
            [$this, 'render_page']
        );

        // Recovery page conditionally upsells or shows real feature
        $is_pro = defined('MAXLIMITS_IS_PRO') && MAXLIMITS_IS_PRO;

        add_submenu_page(
            $this->menu_slug,
            __('Emergency Recovery', 'maxlimits-increase-maximum-limits'),
            __('Emergency Recovery', 'maxlimits-increase-maximum-limits'),
            'manage_options',
            'maxlimits-recovery',
            [$this, $is_pro ? 'render_recovery_page' : 'render_recovery_upsell_page']
        );

        add_submenu_page(
            $this->menu_slug,
            __('Crash Analytics', 'maxlimits-increase-maximum-limits'),
            __('Crash Analytics', 'maxlimits-increase-maximum-limits'),
            'manage_options',
            'maxlimits-crash-analytics',
            [$this, 'render_crash_analytics_page']
        );

        add_submenu_page(
            $this->menu_slug,
            __('Other Plugins', 'maxlimits-increase-maximum-limits'),
            __('Other Plugins', 'maxlimits-increase-maximum-limits'),
            'manage_options',
            'maxlimits-other-plugins',
            [$this, 'render_other_plugins_page']
        );
    }

    public function enqueue_assets($hook)
    {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'maxlimits') === false) {
            if ($hook === 'plugins.php') {
                wp_enqueue_script('maxlimits-deactivation', plugins_url('../assets/js/admin-deactivation.js', __FILE__), ['jquery'], MAXLIMITS_VERSION, true);
            }
            return;
        }

        wp_enqueue_style('maxlimits-admin', plugins_url('../assets/css/admin.css', __FILE__), [], MAXLIMITS_VERSION);
        wp_enqueue_script('maxlimits-admin', plugins_url('../assets/js/admin.js', __FILE__), ['jquery'], MAXLIMITS_VERSION, true);

        // Localize params for ALL our pages
        wp_localize_script('maxlimits-admin', 'maxlimitsParams', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('maxlimits-nonce')
        ]);
    }


    /**
     * Renders the unified premium header for all plugin pages.
     */
    private function render_common_header()
    {
        ?>
        <div class="maxlimits-header" style="padding-bottom: 25px; margin-bottom: 2.5rem; border-bottom: 1px solid rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center;">
            <div class="maxlimits-brand" style="display: flex; align-items: center; gap: 18px;">
                <!-- Modern Premium Logo Icon -->
                <div class="maxlimits-logo-modern" style="
                    position: relative;
                    width: 48px; 
                    height: 48px; 
                    background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); 
                    border-radius: 14px; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    color: #fff; 
                    font-weight: 900; 
                    font-size: 26px; 
                    box-shadow: 0 4px 20px rgba(124, 58, 237, 0.4);
                    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
                    flex-shrink: 0;
                    overflow: hidden;
                ">
                    M
                    <div class="maxlimits-shimmer-effect"></div>
                </div>

                
                <div style="display: flex; flex-direction: column; gap: 2px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="
                            font-size: 26px; 
                            font-weight: 900; 
                            color: #0f172a; 
                            letter-spacing: -0.04em; 
                            line-height: 1;
                            background: linear-gradient(90deg, #1e293b, #475569);
                            -webkit-background-clip: text;
                            -webkit-text-fill-color: transparent;
                        ">MaxLimits</span>
                        <span style="
                            font-size: 11px; 
                            font-weight: 800; 
                            background: #f1f5f9; 
                            color: #64748b; 
                            padding: 2px 10px; 
                            border-radius: 100px; 
                            text-transform: uppercase; 
                            letter-spacing: 0.1em;
                            border: 1px solid rgba(0,0,0,0.1);
                        "><?php if (defined('MAXLIMITS_IS_PRO') && MAXLIMITS_IS_PRO): ?>
                            PRO
                        <?php else: ?>
                            Free
                        <?php endif; ?></span>
                    </div>
                    <a href="https://dominopress.com" target="_blank" style="font-size: 13px; color: #94a3b8; text-decoration: none; font-weight: 600; letter-spacing: 0.01em; display: flex; align-items: center; gap: 4px;">
                        <?php _e('by', 'maxlimits-increase-maximum-limits'); ?> 
                        <span style="color: #64748b; font-weight: 700;">DominoPress</span>
                    </a>
                </div>
            </div>
            
            <div class="maxlimits-header-actions" style="display: flex; gap: 12px; align-items: center;">
<?php if (!(defined('MAXLIMITS_IS_PRO') && MAXLIMITS_IS_PRO)): ?>
                <a href="https://dominopress.com/plugin/maxlimits" target="_blank" class="btn-upgrade-pro" style="
                    height: 42px; 
                    padding: 0 20px; 
                    display: inline-flex; 
                    align-items: center; 
                    gap: 8px; 
                    border-radius: 10px; 
                    font-weight: 700; 
                    font-size: 13px; 
                    background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%); 
                    color: #fff; 
                    text-decoration: none;
                    transition: all 0.2s;
                    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
                ">
                    <span class="dashicons dashicons-star-filled" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    <?php _e('Upgrade to PRO', 'maxlimits-increase-maximum-limits'); ?> 
                </a>
                <?php endif; ?>
                <a href="https://dominopress.com/plugins" target="_blank" class="button button-secondary" style="
                    height: 42px; 
                    padding: 0 20px; 
                    display: inline-flex; 
                    align-items: center; 
                    gap: 8px; 
                    border-radius: 10px; 
                    font-weight: 700; 
                    font-size: 13px; 
                    background: #fff; 
                    color: #475569; 
                    border: 1px solid rgba(0,0,0,0.1);
                    transition: all 0.2s;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                ">
                    <?php _e('Our Other Plugins', 'maxlimits-increase-maximum-limits'); ?> 
                    <span class="dashicons dashicons-external" style="font-size: 16px; width: 16px; height: 16px; margin-top: 2px;"></span>
                </a>
            </div>
        </div>
        <?php
    }


    public function render_page()
    {
        $limits = get_option($this->core->limit_option_name, []);
        $advanced = get_option($this->core->advanced_option_name, []);
        $options_def = $this->core->get_limit_options();

        // Generate code snippets for display
        $snippets = $this->core->get_ini_code_snippets($limits);
        ?>
        <div class="wrap maxlimits-wrap">
            <?php $this->render_tracking_notice(); ?>
            <?php $this->render_common_header(); ?>

            <div class="maxlimits-container">

                <!-- Main Settings Column -->
                <div class="maxlimits-main">
                    
                    <?php
                    // Detect conflicts
                    $htaccess = get_home_path() . '.htaccess';
                    $user_ini = ABSPATH . '.user.ini';
                    $has_conflicts = false;
                    foreach ([$htaccess, $user_ini ] as $f) {
                        if (file_exists($f) && is_readable($f)) {
                            $c = file_get_contents($f);
                            if (strpos($c, 'MaxLimits Emergency Recovery') !== false || strpos($c, 'MaxLimits Manual Recovery') !== false) {
                                $has_conflicts = true;
                                break;
                            }
                        }
                    }

                    if ($has_conflicts): ?>
                        <div class="maxlimits-card" style="border-left: 4px solid #f59e0b; background: #fffbeb; margin-bottom: 24px; padding: 20px;">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <span class="dashicons dashicons-warning" style="color: #d97706; font-size: 24px; width: 24px; height: 24px;"></span>
                                <div>
                                    <h4 style="margin: 0; color: #92400e; font-size: 15px; font-weight: 700;"><?php _e('Legacy Settings Conflict Detected', 'maxlimits-increase-maximum-limits'); ?></h4>
                                    <p style="margin: 4px 0 0; color: #b45309; font-size: 13px;">
                                        <?php _e('Some "Emergency Recovery" or legacy rules are currently active in your server configuration (1024MB), which are overriding your dashboard choices. Click "Save Limits" below to purge these conflicts and apply your new settings.', 'maxlimits-increase-maximum-limits'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form id="maxlimits-main-form">

                        <!-- 1-Click Optimizers Card -->
                        <div class="maxlimits-card maxlimits-optimizers-card" style="border-top: 4px solid #10b981;">
                            <div class="maxlimits-card-header">
                                <h2><span class="dashicons dashicons-superhero" style="color: #10b981; margin-right: 8px;"></span><?php _e('1-Click Optimizers', 'maxlimits-increase-maximum-limits'); ?></h2>
                            </div>
                            <div class="maxlimits-card-body">
                                <p class="description" style="margin-top: 0; margin-bottom: 15px;">
                                    <?php _e('Instantly apply the most recommended limits for your specific setup. Click a button below to auto-fill the best values.', 'maxlimits-increase-maximum-limits'); ?>
                                </p>
                                <div class="maxlimits-preset-buttons" style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <button type="button" class="button preset-btn" data-preset="standard" style="display: flex; align-items: center; gap: 5px;">
                                        <span class="dashicons dashicons-wordpress"></span> <?php _e('Standard Site', 'maxlimits-increase-maximum-limits'); ?>
                                    </button>
                                    <button type="button" class="button preset-btn" data-preset="woocommerce" <?php echo (defined('MAXLIMITS_IS_PRO') && MAXLIMITS_IS_PRO) ? '' : 'data-pro="true"'; ?> style="display: flex; align-items: center; gap: 5px; color: #7b2cb8; border-color: #7b2cb8;">
                                        <span class="dashicons dashicons-cart"></span> <?php _e('WooCommerce', 'maxlimits-increase-maximum-limits'); ?><?php echo (defined('MAXLIMITS_IS_PRO') && MAXLIMITS_IS_PRO) ? '' : ' (PRO)'; ?>
                                    </button>
                                    <button type="button" class="button preset-btn" data-preset="pagebuilder" <?php echo (defined('MAXLIMITS_IS_PRO') && MAXLIMITS_IS_PRO) ? '' : 'data-pro="true"'; ?> style="display: flex; align-items: center; gap: 5px; color: #d94f4f; border-color: #d94f4f;">
                                        <span class="dashicons dashicons-grid-view"></span> <?php _e('Elementor / Divi', 'maxlimits-increase-maximum-limits'); ?><?php echo (defined('MAXLIMITS_IS_PRO') && MAXLIMITS_IS_PRO) ? '' : ' (PRO)'; ?>
                                    </button>
                                    <button type="button" class="button preset-btn" data-preset="maximum" <?php echo (defined('MAXLIMITS_IS_PRO') && MAXLIMITS_IS_PRO) ? '' : 'data-pro="true"'; ?> style="display: flex; align-items: center; gap: 5px; color: #b91c1c; border-color: #b91c1c;">
                                        <span class="dashicons dashicons-warning"></span> <?php _e('Maximum Power', 'maxlimits-increase-maximum-limits'); ?><?php echo (defined('MAXLIMITS_IS_PRO') && MAXLIMITS_IS_PRO) ? '' : ' (PRO)'; ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Limits Card -->
                        <div class="maxlimits-card">
                            <div class="maxlimits-card-header">
                                <h2><?php _e('Resource Limits', 'maxlimits-increase-maximum-limits'); ?></h2>
                            </div>
                            <div class="maxlimits-card-body">
                                <?php
                                $fields = [
                                    'upload_max_filesize' => ['label' => __('Max Upload File Size', 'maxlimits-increase-maximum-limits'), 'desc' => __('Limit for files uploaded via Media Library.', 'maxlimits-increase-maximum-limits')],
                                    'post_max_size' => ['label' => __('Max Post Size', 'maxlimits-increase-maximum-limits'), 'desc' => __('Limit for total POST data size.', 'maxlimits-increase-maximum-limits')],
                                    'memory_limit' => ['label' => __('PHP Memory Limit', 'maxlimits-increase-maximum-limits'), 'desc' => __('Max memory a script can allocate.', 'maxlimits-increase-maximum-limits')],
                                    'max_execution_time' => ['label' => __('Max Execution Time', 'maxlimits-increase-maximum-limits'), 'desc' => __('Max time a script can run (seconds).', 'maxlimits-increase-maximum-limits')],
                                    'max_input_time' => ['label' => __('Max Input Time', 'maxlimits-increase-maximum-limits'), 'desc' => __('Max time to parse input data.', 'maxlimits-increase-maximum-limits')],
                                    'max_input_vars' => ['label' => __('Max Input Vars', 'maxlimits-increase-maximum-limits'), 'desc' => __('Max variables accepted.', 'maxlimits-increase-maximum-limits')],
                                ];

                                foreach ($fields as $key => $meta) {
                                    $this->render_field($key, $meta, $limits, $options_def[$key]);
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Advanced Card -->
                        <div class="maxlimits-card">
                            <div class="maxlimits-card-header">
                                <h2><?php _e('Advanced Configuration', 'maxlimits-increase-maximum-limits'); ?></h2>
                            </div>
                            <div class="maxlimits-card-body">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                                    <div>
                                        <strong><?php _e('Direct .htaccess Writing', 'maxlimits-increase-maximum-limits'); ?></strong>
                                        <p class="description" style="margin-top: 5px;"><?php _e('Write rules directly to your .htaccess file. Use this if the default method doesn\'t work.', 'maxlimits-increase-maximum-limits'); ?></p>
                                    </div>
                                    <label class="toggle-switch" style="flex-shrink: 0;">
                                        <input type="checkbox" name="write_to_htaccess" value="1" <?php checked($advanced['write_to_htaccess'] ?? 0, 1); ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                                    <div>
                                        <strong style="display: flex; align-items: center; gap: 8px;">
                                            <?php _e('Smart Limit Allocation', 'maxlimits-increase-maximum-limits'); ?>
                                            <span style="font-size: 10px; font-weight: 800; background: #f1f5f9; color: #7c3aed; padding: 2px 6px; border-radius: 4px; border: 1px solid #e2e8f0;">PRO</span>
                                        </strong>
                                        <p class="description" style="margin-top: 5px;"><?php _e('Only boost memory limits in heavy editors (Admin, Elementor, etc.) while keeping your public front-end extremely lean for maximum stability.', 'maxlimits-increase-maximum-limits'); ?></p>
                                    </div>
                                    <label class="toggle-switch" style="flex-shrink: 0;">
                                        <input type="checkbox" name="smart_allocation" value="1" <?php echo !(defined('MAXLIMITS_IS_PRO') && MAXLIMITS_IS_PRO) ? 'class="btn-upgrade-pro"' : ''; ?> <?php checked($advanced['smart_allocation'] ?? 0, 1); ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                            </div>
                        </div>


                        <div class="action-bar">
                            <button type="submit" class="btn-primary submit-btn">
                                <span class="spinner-icon"></span>
                                <span class="text"><?php _e('Save Changes', 'maxlimits-increase-maximum-limits'); ?></span>
                            </button>
                        </div>

                    </form>

                    <!-- Manual Code Generator -->
                    <div class="maxlimits-card" style="margin-top: 2rem;">
                        <div class="maxlimits-card-header">
                            <h2><?php _e('Manual Configuration', 'maxlimits-increase-maximum-limits'); ?></h2>
                        </div>
                        <div class="tab-nav">
                            <button class="tab-btn active" data-target="tab-user-ini">.user.ini</button>
                            <button class="tab-btn" data-target="tab-htaccess">.htaccess</button>
                        </div>
                        <div id="tab-user-ini" class="tab-content active">
                            <p class="description">
                                <?php _e('Add this to your .user.ini file if settings don\'t apply.', 'maxlimits-increase-maximum-limits'); ?>
                            </p>
                            <textarea class="code-block" readonly
                                onclick="this.select()"><?php echo esc_textarea($snippets['user_ini']); ?></textarea>
                        </div>
                        <div id="tab-htaccess" class="tab-content">
                            <p class="description">
                                <?php _e('Add this to your .htaccess file for Apache servers.', 'maxlimits-increase-maximum-limits'); ?>
                            </p>
                            <textarea class="code-block" readonly
                                onclick="this.select()"><?php echo esc_textarea($snippets['htaccess']); ?></textarea>
                        </div>
                    </div>

                </div>

                <!-- Sidebar -->
                <div class="maxlimits-sidebar">

                    <!-- Rating Widget -->
                    <div class="maxlimits-card" style="background: linear-gradient(135deg, #f0f9ff, #e0f2fe);">
                        <div class="maxlimits-card-body" style="text-align: center;">
                            <h3 style="margin-top:0; color: #0284c7;">
                                <?php _e('Love MaxLimits?', 'maxlimits-increase-maximum-limits'); ?>
                            </h3>
                            <p style="font-size: 13px; color: #334155; margin-bottom: 1rem;">
                                <?php _e('Please rate us 5 stars on WordPress.org to help us grow!', 'maxlimits-increase-maximum-limits'); ?>
                            </p>
                            <a href="https://wordpress.org/support/plugin/maxlimits-increase-maximum-limits/reviews/#new-post"
                                target="_blank" class="btn-secondary"
                                style="width:100%; display:block; text-align:center; box-sizing: border-box;">
                                <?php _e('Rate Plugin ★★★★★', 'maxlimits-increase-maximum-limits'); ?>
                            </a>
                        </div>
                    </div>

                    <!-- Server Values Widget -->
                    <div class="maxlimits-card sidebar-status-card">
                        <div class="maxlimits-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h2 style="display: flex; align-items: center; gap: 8px; margin: 0;">
                                <span class="dashicons dashicons-dashboard" style="color: var(--ml-accent);"></span>
                                <?php _e('Live Server Status', 'maxlimits-increase-maximum-limits'); ?>
                            </h2>
                            <button type="button" id="ml-refresh-status" class="ml-refresh-btn-text">
                                <span class="dashicons dashicons-update"></span>
                                <span><?php _e('Refresh', 'maxlimits-increase-maximum-limits'); ?></span>
                            </button>
                        </div>
                        <div class="maxlimits-card-body">
                            <div id="maxlimits-server-values">
                                <?php echo $this->get_server_limits_html(false); ?>
                            </div>

                            <div class="maxlimits-status-hint">
                                <div class="hint-icon">
                                    <span class="dashicons dashicons-info"></span>
                                </div>
                                <div class="hint-content">
                                    <strong><?php _e('Still seeing old limits?', 'maxlimits-increase-maximum-limits'); ?></strong>
                                    <p><?php _e('Some hosts require <strong>"Direct .htaccess Writing"</strong> (find it in the Advanced tab). If already enabled, wait 5 seconds and click <strong>"Refresh"</strong> to update the display.', 'maxlimits-increase-maximum-limits'); ?>
                                    </p>
                                </div>
                            </div>

                            <p class="description"
                                style="text-align: center; margin-top: 1.5rem; color: #94a3b8; font-size: 11px;">
                                <span class="dashicons dashicons-update"
                                    style="font-size: 12px; width: 12px; height: 12px; margin-right: 4px;"></span>
                                <?php _e('Real-time values from your server environment.', 'maxlimits-increase-maximum-limits'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- DominoPost Promo Widget -->
                    <!-- DominoPost Promo Widget -->
                    <div class="maxlimits-card" style="border-top: 4px solid #7c3aed;">
                        <div class="maxlimits-card-header">
                            <h2 style="display:flex; align-items:center;">
                                <span class="dashicons dashicons-edit" style="margin-right:8px; color:#7c3aed;"></span>
                                <?php _e('FREE AI Writer & Post Editor', 'maxlimits-increase-maximum-limits'); ?>
                            </h2>
                        </div>
                        <div class="maxlimits-card-body">
                            <p style="margin-top:0; color: #50575e; font-size: 13px;">
                                <?php _e('Upgrade your content creation workflow. Get advanced tools directly inside your WordPress editor.', 'maxlimits-increase-maximum-limits'); ?>
                            </p>
                            <ul style="margin: 12px 0; list-style: none; padding: 0;">
                                <li style="margin-bottom: 6px; display: flex; align-items: center; font-size: 13px;">
                                    <span class="dashicons dashicons-welcome-write-blog"
                                        style="color: #7c3aed; margin-right: 6px; font-size: 16px;"></span>
                                    <strong><?php _e('Free Unlimited AI Writer (World\'s First)', 'maxlimits-increase-maximum-limits'); ?></strong>
                                </li>
                                <li style="margin-bottom: 6px; display: flex; align-items: center; font-size: 13px;">
                                    <span class="dashicons dashicons-yes"
                                        style="color: #10b981; margin-right: 6px; font-size: 16px;"></span>
                                    <?php _e('Automatic Table of Contents', 'maxlimits-increase-maximum-limits'); ?>
                                </li>
                                <li style="margin-bottom: 6px; display: flex; align-items: center; font-size: 13px;">
                                    <span class="dashicons dashicons-yes"
                                        style="color: #10b981; margin-right: 6px; font-size: 16px;"></span>
                                    <?php _e('Broken Link Scanner', 'maxlimits-increase-maximum-limits'); ?>
                                </li>
                                <li style="margin-bottom: 6px; display: flex; align-items: center; font-size: 13px;">
                                    <span class="dashicons dashicons-yes"
                                        style="color: #10b981; margin-right: 6px; font-size: 16px;"></span>
                                    <?php _e('Estimated Reading Time', 'maxlimits-increase-maximum-limits'); ?>
                                </li>
                                <li style="margin-bottom: 6px; display: flex; align-items: center; font-size: 13px;">
                                    <span class="dashicons dashicons-yes"
                                        style="color: #10b981; margin-right: 6px; font-size: 16px;"></span>
                                    <?php _e('Title Case Converter', 'maxlimits-increase-maximum-limits'); ?>
                                </li>
                                <li style="margin-bottom: 6px; display: flex; align-items: center; font-size: 13px;">
                                    <span class="dashicons dashicons-yes"
                                        style="color: #10b981; margin-right: 6px; font-size: 16px;"></span>
                                    <?php _e('CTA Buttons, Emoji & Find/Replace', 'maxlimits-increase-maximum-limits'); ?>
                                </li>
                                <li style="margin-bottom: 6px; display: flex; align-items: center; font-size: 13px;">
                                    <span class="dashicons dashicons-plus"
                                        style="color: #7c3aed; margin-right: 6px; font-size: 16px; font-weight: bold;"></span>
                                    <strong><?php _e('Many More Premium Features for FREE', 'maxlimits-increase-maximum-limits'); ?></strong>
                                </li>
                            </ul>
                            <a href="<?php echo esc_url(admin_url('plugin-install.php?s=dominopost&tab=search&type=term')); ?>"
                                target="_blank" class="btn-primary"
                                style="width:100%; display:block; text-align:center; box-sizing: border-box; background: #7c3aed; border-color: #7c3aed;">
                                <?php _e('Get DominoPost Free', 'maxlimits-increase-maximum-limits'); ?>
                            </a>
                        </div>
                    </div>


                </div>

            </div>
        </div>

            });
        </script>
        <?php
    }

    private function render_field($key, $meta, $current_limits, $options)
    {
        $current_val = $current_limits[$key] ?? '';
        $is_custom = !in_array($current_val, $options['values']) && !empty($current_val);
        $select_val = $is_custom ? 'custom' : $current_val;
        ?>
        <div class="form-group">
            <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($meta['label']); ?></label>
            <div class="input-wrapper">
                <select id="<?php echo esc_attr($key); ?>" name="limits[<?php echo esc_attr($key); ?>]"
                    class="maxlimits-select">
                    <option value="" <?php selected($select_val, ''); ?>>
                        <?php _e('Default', 'maxlimits-increase-maximum-limits'); ?>
                    </option>
                    <?php foreach ($options['values'] as $val): ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($select_val, $val); ?>>
                            <?php echo esc_html($val); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="custom" <?php selected($select_val, 'custom'); ?>>
                        <?php _e('Custom', 'maxlimits-increase-maximum-limits'); ?>
                    </option>
                </select>

                <input type="number" name="limits[<?php echo esc_attr($key); ?>_custom]"
                    class="maxlimits-input maxlimits-custom-input"
                    value="<?php echo $is_custom ? esc_attr($current_val) : ''; ?>" placeholder="Value" style="display: none;">

                <span class="unit-label"><?php echo esc_html($options['label']); ?></span>
            </div>
            <p class="description"><?php echo esc_html($meta['desc']); ?></p>
        </div>
        <?php
    }

    /**
     * AJAX Handler: Save Settings
     */
    public function ajax_save_settings()
    {
        check_ajax_referer('maxlimits-nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        // 0. Cleanup any conflicting rules from Recovery/Manual modes
        $this->cleanup_external_rules();

        // 1. Sanitize & Save Limits
        $raw_limits = $_POST['limits'] ?? [];
        $clean_limits = [];
        $limit_defs = $this->core->get_limit_options();

        foreach ($limit_defs as $key => $def) {
            if (isset($raw_limits[$key])) {
                if ($raw_limits[$key] === 'custom') {
                    $custom_val = $raw_limits[$key . '_custom'] ?? '';
                    if (!empty($custom_val)) {
                        $clean_limits[$key] = absint($custom_val);
                    }
                } elseif ($raw_limits[$key] !== '') {
                    $clean_limits[$key] = absint($raw_limits[$key]);
                }
            }
        }

        update_option($this->core->limit_option_name, $clean_limits);

        // 2. Sanitize & Save Advanced
        $write_htaccess = isset($_POST['write_to_htaccess']) ? 1 : 0;
        $smart_allocation = isset($_POST['smart_allocation']) ? 1 : 0;
        $advanced = [
            'write_to_htaccess' => $write_htaccess,
            'smart_allocation' => $smart_allocation
        ];
        update_option($this->core->advanced_option_name, $advanced);

        $warnings = [];

        // 3. Handle .htaccess writing
        if ($write_htaccess) {
            if (!$this->update_htaccess($clean_limits)) {
                $warnings[] = __('Could not write to .htaccess. Please check file permissions.', 'maxlimits-increase-maximum-limits');
            }
        } else {
            $this->remove_htaccess_rules();
        }

        // Flush general cache
        wp_cache_flush();

        $message = __('Settings saved successfully.', 'maxlimits-increase-maximum-limits');
        if (!empty($warnings)) {
            wp_send_json_success([
                'message' => $message,
                'warnings' => $warnings
            ]);
        } else {
            wp_send_json_success(['message' => $message]);
        }
    }

    /**
     * AJAX Handler: Get Server Limits HTML
     */
    public function ajax_get_server_limits()
    {
        check_ajax_referer('maxlimits-nonce', 'security');
        wp_send_json_success(['html' => $this->get_server_limits_html(false)]);
    }

    private function get_server_limits_html($show_footer = true)
    {
        $saved_limits = get_option($this->core->limit_option_name, []);
        
        $limits = [
            __('Upload Max Size', 'maxlimits-increase-maximum-limits') => [
                'key' => 'upload_max_filesize', 
                'target' => $saved_limits['upload_max_filesize'] ?? 0,
                'min' => 64, 
                'icon' => 'dashicons-upload'
            ],
            __('Post Max Size', 'maxlimits-increase-maximum-limits') => [
                'key' => 'post_max_size', 
                'target' => $saved_limits['post_max_size'] ?? 0,
                'min' => 64, 
                'icon' => 'dashicons-cloud-upload'
            ],
            __('Memory Limit', 'maxlimits-increase-maximum-limits') => [
                'key' => 'memory_limit', 
                'target' => $saved_limits['memory_limit'] ?? 0,
                'min' => 256, 
                'icon' => 'dashicons-database'
            ],
            __('Max Execution', 'maxlimits-increase-maximum-limits') => [
                'key' => 'max_execution_time', 
                'target' => $saved_limits['max_execution_time'] ?? 0,
                'min' => 300, 
                'icon' => 'dashicons-clock'
            ],
            __('Max Input Time', 'maxlimits-increase-maximum-limits') => [
                'key' => 'max_input_time', 
                'target' => $saved_limits['max_input_time'] ?? 0,
                'min' => 300, 
                'icon' => 'dashicons-hourglass'
            ],
            __('Max Input Vars', 'maxlimits-increase-maximum-limits') => [
                'key' => 'max_input_vars', 
                'target' => $saved_limits['max_input_vars'] ?? 0,
                'min' => 3000, 
                'icon' => 'dashicons-list-view'
            ],
        ];

        $html = '<div class="maxlimits-dashboard-grid">';
        $byte_keys = ['upload_max_filesize', 'post_max_size', 'memory_limit'];

        foreach ($limits as $label => $data) {
            $raw_val = ini_get($data['key']);
            $is_byte_value = in_array($data['key'], $byte_keys);

            if ($is_byte_value) {
                $bytes = wp_convert_hr_to_bytes($raw_val);
                if ($bytes === -1) {
                    $val_compare = PHP_INT_MAX;
                    $display_val = __('Unlimited', 'maxlimits-increase-maximum-limits');
                } else {
                    $val_compare = $bytes / 1048576;
                    $display_val = round($val_compare) . 'M';
                }
            } else {
                $val_compare = intval($raw_val);
                if (($data['key'] === 'max_execution_time' || $data['key'] === 'max_input_time') && ($val_compare === 0 || $val_compare === -1)) {
                    $val_compare = PHP_INT_MAX;
                    $display_val = __('Unlimited', 'maxlimits-increase-maximum-limits');
                } else {
                    $display_val = $raw_val;
                }
            }

            // Logic:
            // 1. Success (Green): Actual >= Target (Target > 0)
            // 2. Default (Neutral): Target == 0, but Actual >= Min
            // 3. Mismatch (Yellow): Actual < Target
            // 4. Critical (Red): Actual < Min

            if ($data['target'] > 0 && $val_compare >= $data['target']) {
                $class = 'ok';
            } elseif ($data['target'] > 0 && $val_compare < $data['target']) {
                $class = 'warning';
            } elseif ($val_compare >= $data['min']) {
                $class = 'ok';
            } else {
                $class = 'warning'; // Using warning as red is too aggressive for defaults
            }
            
            // If it's truly dangerously low, use a specific red indicator (logic can be expanded here)
            if ($val_compare < ($data['min'] / 2)) {
                $class = 'warning critical'; // we'll use CSS to handle critical color
            }

            $html .= sprintf(
                '<div class="maxlimits-grid-item %s">
                    <span class="grid-icon dashicons %s"></span>
                    <div class="grid-content">
                        <span class="grid-label">%s</span>
                        <span class="grid-value %s">%s</span>
                    </div>
                </div>',
                esc_attr($class),
                esc_attr($data['icon']),
                esc_html($label),
                esc_attr($class),
                esc_html($display_val)
            );
        }
        $html .= '</div>';
        if ($show_footer) {
            $html .= sprintf(
                '<div class="maxlimits-dashboard-footer" style="padding: 12px; border-top: 1px solid #f0f0f1; text-align: center;">
                    <a href="%s" class="button button-link" style="font-size: 13px; font-weight: 500; text-decoration: none;">%s →</a>
                </div>',
                esc_url(admin_url('admin.php?page=' . $this->menu_slug)),
                __('Increase Limits', 'maxlimits-increase-maximum-limits')
            );
        }
        return $html;
    }

    private function update_htaccess($limits)
    {
        $htaccess_file = get_home_path() . '.htaccess';
        if (!file_exists($htaccess_file)) {
            @touch($htaccess_file);
        }
        
        if (!is_writable($htaccess_file)) {
            return false;
        }

        if (!function_exists('insert_with_markers')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $snippets = $this->core->get_ini_code_snippets($limits);
        // We only need the inner content, insert_with_markers adds the BEGIN/END tags
        // But my get_ini_code_snippets adds them too. Let me strip them or just use a raw generator.
        // Actually, insert_with_markers takes an array or string. It wraps it with the markers. 
        // My get_ini_code_snippets returns the full block including markers.
        // So I should clean it up or rewrite the logic slightly.

        // Let's just regenerate the lines here for safety/clarity without markers
        $lines = [];
        $map = [
            'upload_max_filesize' => 'php_value upload_max_filesize',
            'post_max_size' => 'php_value post_max_size',
            'memory_limit' => 'php_value memory_limit',
            'max_execution_time' => 'php_value max_execution_time',
            'max_input_time' => 'php_value max_input_time',
            'max_input_vars' => 'php_value max_input_vars',
        ];

        foreach ($map as $key => $directive) {
            if (!empty($limits[$key])) {
                $suffix = in_array($key, ['upload_max_filesize', 'post_max_size', 'memory_limit']) ? 'M' : '';
                $lines[] = $directive . ' ' . $limits[$key] . $suffix;
            }
        }

        return insert_with_markers($htaccess_file, 'MaxLimits', $lines);
    }

    private function cleanup_external_rules()
    {
        $htaccess = get_home_path() . '.htaccess';
        $user_ini = ABSPATH . '.user.ini';

        $files = [$htaccess, $user_ini];
        foreach ($files as $file) {
            if (!file_exists($file) || !is_writable($file)) continue;
            
            $content = file_get_contents($file);
            $modified = false;

            // 1. Remove markers with END tags (# BEGIN MaxLimits Emergency Recovery ... # END ...)
            $markers = ['MaxLimits Emergency Recovery', 'MaxLimits Manual Recovery'];
            foreach ($markers as $marker) {
                $count = 0;
                $pattern = "/\n*# BEGIN {$marker}.*?# END {$marker}\n*/s";
                $content = preg_replace($pattern, "\n", $content, -1, $count);
                if ($count > 0) $modified = true;
            }

            // 2. Remove loose blocks from .user.ini or failed .htaccess writes
            // Looks for the comment and then any lines that look like "key = value" or "php_value key value"
            foreach ($markers as $marker) {
                $count = 0;
                // Matches the comment (either # or ;) and subsequent lines of limit-related directives
                $pattern = "/(?:\n|^)[#;]\s*{$marker}\s*\n(?:(?:php_value|[\w_]+)\s+[^\n]+\n?)+/i";
                $content = preg_replace($pattern, "\n", $content, -1, $count);
                if ($count > 0) $modified = true;
            }

            if ($modified) {
                file_put_contents($file, trim($content) . "\n");
            }
        }
    }

    private function remove_htaccess_rules()
    {
        $htaccess_file = get_home_path() . '.htaccess';
        if (!is_writable($htaccess_file))
            return;

        if (!function_exists('insert_with_markers')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        insert_with_markers($htaccess_file, 'MaxLimits', []);
    }


    public function register_dashboard_widget()
    {
        if (current_user_can('manage_options')) {
            wp_add_dashboard_widget(
                'maxlimits_dashboard',
                __('MaxLimits Server Status', 'maxlimits-increase-maximum-limits'),
                function () {
                    echo '<div class="maxlimits-wrap" style="padding:0; min-height:auto;">' . $this->get_server_limits_html() . '</div>';
                    ?>
                <style>
                    #maxlimits_dashboard .inside {
                        padding: 0;
                        margin: 0;
                    }

                    .maxlimits-dashboard-grid {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 1px;
                        background: #e2e8f0;
                        border-bottom: 1px solid #e2e8f0;
                    }

                    .maxlimits-grid-item {
                        background: #fff;
                        padding: 16px 12px;
                        display: flex;
                        align-items: center;
                        transition: all 0.2s ease;
                    }

                    .maxlimits-grid-item:hover {
                        background: #f8fafc;
                        z-index: 1;
                        box-shadow: inset 0 0 0 1px #cbd5e1;
                    }

                    .maxlimits-grid-item .grid-icon {
                        font-size: 18px;
                        width: 18px;
                        height: 18px;
                        margin-right: 14px;
                        color: #64748b;
                        opacity: 0.7;
                    }

                    .maxlimits-grid-item .grid-content {
                        display: flex;
                        flex-direction: column;
                        flex: 1;
                        overflow: hidden;
                    }

                    .maxlimits-grid-item .grid-label {
                        font-size: 10px;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        color: #64748b;
                        font-weight: 700;
                        line-height: 1.4;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    .maxlimits-grid-item .grid-value {
                        font-size: 14px;
                        font-weight: 700;
                        color: #0f172a;
                        line-height: 1.2;
                        margin-top: 1px;
                    }

                    .maxlimits-grid-item.warning .grid-value {
                        color: #d97706;
                    }

                    .maxlimits-grid-item.ok .grid-value {
                        color: #059669;
                    }

                    .maxlimits-dashboard-footer .button-link {
                        color: #7c3aed !important;
                        text-decoration: none !important;
                        font-weight: 600 !important;
                        transition: color 0.1s ease-in-out;
                    }

                    .maxlimits-dashboard-footer .button-link:hover {
                        color: #4f46e5 !important;
                    }

                    @media (max-width: 480px) {
                        .maxlimits-dashboard-grid {
                            grid-template-columns: 1fr;
                        }
                    }
                </style>
                <?php
                }
            );
        }
    }

    /**
     * Render the tracking consent notice
     */
    public function render_tracking_notice()
    {
        // Only show if pending
        if (!get_option('maxlimits_tracking_pending')) {
            return;
        }

        // Output the notice
        ?>
        <div class="maxlimits-tracking-optin-container" style="display: block; width: 100%; padding: 0 0 25px 0; clear: both;">
            <div class="maxlimits-tracking-notice"
                style="border-left: 2px solid #7c3aed; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: relative; display: flex; gap: 24px; align-items: flex-start;">
                <div style="background: rgba(124, 58, 237, 0.08); width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <span class="dashicons dashicons-info" style="color: #7c3aed; font-size: 24px; width: 24px; height: 24px;"></span>
                </div>
                <div style="flex: 1;">
                    <p style="margin: 0 0 6px; font-weight: 800; font-size: 16px; color: #1e293b;">
                        <?php _e('Help us improve MaxLimits', 'maxlimits-increase-maximum-limits'); ?>
                    </p>
                    <p style="margin: 0 0 16px; color: #64748b; font-size: 14px; line-height: 1.6; max-width: 700px;">
                        <?php _e('Would you like to help us make MaxLimits better? By sharing non-sensitive usage data, you allow us to understand how the plugin is used and prioritize new features.', 'maxlimits-increase-maximum-limits'); ?>
                    </p>
                    <button class="ml-btn-primary maxlimits-track-allow" style="padding: 10px 24px;">
                        <?php _e('Allow & Continue', 'maxlimits-increase-maximum-limits'); ?>
                    </button>
                    <button class="maxlimits-track-dismiss" style="background: none; border: none; color: #94a3b8; font-size: 13px; font-weight: 600; cursor: pointer; margin-left: 15px;"><?php _e('No thanks', 'maxlimits-increase-maximum-limits'); ?></button>
                </div>
            </div>
            
            <script>
                jQuery(document).ready(function ($) {
                    var notice = $('.maxlimits-tracking-notice');
                    notice.on('click', '.maxlimits-track-allow, .maxlimits-track-dismiss', function (e) {
                        e.preventDefault();
                        var consent = $(this).hasClass('maxlimits-track-allow') ? 1 : 0;
                        notice.slideUp(200);
                        $.post(ajaxurl, { 
                            action: 'maxlimits_tracking_consent', 
                            consent: consent,
                            nonce: '<?php echo wp_create_nonce('maxlimits-tracking-nonce'); ?>' 
                        });
                    });
                });
            </script>
        </div>
        </div>
        <?php
    }

    /**
     * AJAX Handler: Tracking Consent
     */
    public function ajax_handle_tracking_consent()
    {
        check_ajax_referer('maxlimits-tracking-nonce', 'nonce');

        $consent = !empty($_POST['consent']) ? 1 : 0;

        delete_option('maxlimits_tracking_pending');
        update_option('maxlimits_allow_tracking', $consent);

        if ($consent) {
            $this->send_tracking_data('activate');
        }

        wp_send_json_success();
    }

    public function send_tracking_data($status = 'active')
    {
        $insights = new MaxLimits_Insights();
        $insights->send_event($status);
    }

    /**
     * Renders a persuasive upsell page for the Emergency Recovery feature.
     */
    public function render_recovery_upsell_page()
    {
    ?>
        <div class="wrap maxlimits-wrap">
            <?php $this->render_common_header(); ?>

            <div class="maxlimits-container" style="max-width: 800px; margin: 0 auto; display: block;">
                <div class="maxlimits-card" style="border-top: 4px solid #7c3aed; padding: 40px; text-align: center;">
                    <div style="background: #f5f3ff; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                        <span class="dashicons dashicons-shield-alt" style="font-size: 40px; width: 40px; height: 40px; color: #7c3aed;"></span>
                    </div>

                    <h1 style="font-size: 32px; font-weight: 800; color: #1e293b; margin-bottom: 16px;">
                        <?php _e('Emergency Recovery is a PRO Feature', 'maxlimits-increase-maximum-limits'); ?>
                    </h1>

                    <p style="font-size: 18px; color: #64748b; line-height: 1.6; margin-bottom: 32px; max-width: 600px; margin-left: auto; margin-right: auto;">
                        <?php _e('What happens if your site crashes due to low memory or PHP timeouts? Without access to wp-admin, you cannot fix it. Emergency Recovery gives you a secret "Rescue Link" to fix your site in seconds.', 'maxlimits-increase-maximum-limits'); ?>
                    </p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; text-align: left; margin-bottom: 40px; background: #fafafa; padding: 30px; border-radius: 12px; border: 1px solid #eee;">
                        <div>
                            <h3 style="margin-top:0; display:flex; align-items:center; gap:8px;">
                                <span class="dashicons dashicons-yes" style="color:#10b981;"></span>
                                <?php _e('Works Outside WP-Admin', 'maxlimits-increase-maximum-limits'); ?>
                            </h3>
                            <p style="font-size:13px; color:#64748b;"><?php _e('Access your site recovery panel even if the entire site is down with a Critical Error.', 'maxlimits-increase-maximum-limits'); ?></p>
                        </div>
                        <div>
                            <h3 style="margin-top:0; display:flex; align-items:center; gap:8px;">
                                <span class="dashicons dashicons-yes" style="color:#10b981;"></span>
                                <?php _e('1-Click Auto Fix', 'maxlimits-increase-maximum-limits'); ?>
                            </h3>
                            <p style="font-size:13px; color:#64748b;"><?php _e('Instantly boost limits to "Maximum Power" to bypass memory and time crashing issues.', 'maxlimits-increase-maximum-limits'); ?></p>
                        </div>
                        <div>
                            <h3 style="margin-top:0; display:flex; align-items:center; gap:8px;">
                                <span class="dashicons dashicons-yes" style="color:#10b981;"></span>
                                <?php _e('PIN Protected', 'maxlimits-increase-maximum-limits'); ?>
                            </h3>
                            <p style="font-size:13px; color:#64748b;"><?php _e('Your recovery link is secured with a private PIN that only you know.', 'maxlimits-increase-maximum-limits'); ?></p>
                        </div>
                        <div>
                            <h3 style="margin-top:0; display:flex; align-items:center; gap:8px;">
                                <span class="dashicons dashicons-yes" style="color:#10b981;"></span>
                                <?php _e('Zero Configuration', 'maxlimits-increase-maximum-limits'); ?>
                            </h3>
                            <p style="font-size:13px; color:#64748b;"><?php _e('No manual coding needed. Just generate your link and keep it safe.', 'maxlimits-increase-maximum-limits'); ?></p>
                        </div>
                    </div>

                    <a href="https://dominopress.com/plugin/maxlimits" target="_blank" class="btn-primary" style="padding: 18px 40px; font-size: 18px; border-radius: 14px; text-decoration: none;">
                        <?php _e('Get Emergency Recovery with MaxLimits PRO', 'maxlimits-increase-maximum-limits'); ?>
                    </a>

                    <p style="margin-top: 24px; font-size: 13px; color: #94a3b8;">
                        <?php _e('Join 1,000+ users who trust MaxLimits to keep their sites running smoothly.', 'maxlimits-increase-maximum-limits'); ?>
                    </p>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Render the Emergency Recovery page.
     */
    public function render_recovery_page()
    {
        ?>
        <div class="wrap maxlimits-wrap">
            <?php $this->render_tracking_notice(); ?>
            <?php $this->render_common_header(); ?>

            <div class="maxlimits-recovery-container" style="max-width: 900px; margin: 0 auto; padding-top: 20px;">
                
                <!-- Page Title Section -->
                <div style="margin-bottom: 3rem; text-align: left;">
                    <h2 style="font-size: 32px; font-weight: 900; color: #0f172a; margin: 0 0 12px; letter-spacing: -0.03em;"><?php _e('Emergency Recovery Mode', 'maxlimits-increase-maximum-limits'); ?></h2>
                    <p style="font-size: 17px; color: #64748b; margin: 0; line-height: 1.6; max-width: 700px;">
                        <?php _e('Create a secure, standalone gateway that bypasses WordPress completely. If your site ever crashes or locks you out, use this link to fix it in one click.', 'maxlimits-increase-maximum-limits'); ?>
                    </p>
                </div>

                <!-- Main Management Card -->
                <div class="maxlimits-card" style="border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-radius: 20px; overflow: hidden; background: #fff;">
                    <div style="background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%); height: 6px;"></div>
                    
                    <div class="maxlimits-card-header" style="padding: 35px 40px 10px; border: none;">
                        <h2 style="font-size: 22px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 12px;">
                            <span class="dashicons dashicons-shield-alt" style="color: #f59e0b; font-size: 24px; width: 24px; height: 24px;"></span>
                            <?php _e('Recovery Link Management', 'maxlimits-increase-maximum-limits'); ?>
                        </h2>
                    </div>

                    <div class="maxlimits-card-body" style="padding: 30px 40px 45px;">
                        <?php $recovery_file = get_option('maxlimits_recovery_file'); ?>
                        <?php if ($recovery_file && file_exists(ABSPATH . $recovery_file)): ?>
                            <!-- ACTIVE STATE -->
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px;">
                                <div style="display: flex; align-items: flex-start; gap: 18px; margin-bottom: 30px;">
                                    <div style="background: #10b981; color: #fff; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);">
                                        <span class="dashicons dashicons-yes-alt" style="font-size: 24px; width: 24px; height: 24px;"></span>
                                    </div>
                                    <div>
                                        <h3 style="margin: 0 0 6px; font-size: 19px; color: #064e3b; font-weight: 800;"><?php _e('Your Link is Ready & Active', 'maxlimits-increase-maximum-limits'); ?></h3>
                                        <p style="margin: 0; color: #854d0e; font-size: 14px; font-weight: 600; background: #fef3c7; padding: 4px 12px; border-radius: 6px; display: inline-block;">
                                            <?php _e('⚠️ Bookmark this link now. It works even if your site is dead.', 'maxlimits-increase-maximum-limits'); ?>
                                        </p>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 12px; align-items: stretch; margin-bottom: 35px;">
                                    <input type="text" id="maxlimits-recovery-url" readonly value="<?php echo esc_url(site_url('/' . $recovery_file)); ?>" onclick="this.select()" 
                                        style="flex: 1; font-family: 'Fira Code', monospace; font-size: 14px; background: #fff; height: 56px; padding: 0 20px; border-radius: 12px; border: 2px solid #e2e8f0; color: #475569; transition: all 0.2s; outline: none; margin: 0;">
                                    <button type="button" class="btn-primary" id="maxlimits-copy-link"
                                        style="height: 56px; padding: 0 32px; border-radius: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; white-space: nowrap;">
                                        <?php _e('Copy Link', 'maxlimits-increase-maximum-limits'); ?>
                                    </button>
                                </div>

                                <!-- Security Instructions -->
                                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 15px 20px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px;">
                                    <span class="dashicons dashicons-clipboard" style="color: #2563eb; font-size: 20px; width: 20px; height: 20px;"></span>
                                    <p style="margin: 0; font-size: 13.5px; color: #1e3a8a; line-height: 1.5;">
                                        <strong><?php _e('Action Required:', 'maxlimits-increase-maximum-limits'); ?></strong> 
                                        <?php _e('Save this URL and your PIN in a password manager or a private note. If your site crashes, you won\'t be able to access this page to find them.', 'maxlimits-increase-maximum-limits'); ?>
                                    </p>
                                </div>
                                
                                <div style="background: #fff; border: 1px solid #fee2e2; border-radius: 12px; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h4 style="margin: 0; font-size: 14px; color: #b91c1c;"><?php _e('Reset Recovery Options', 'maxlimits-increase-maximum-limits'); ?></h4>
                                        <p style="margin: 4px 0 0; font-size: 12px; color: #94a3b8;"><?php _e('Need to change your PIN or reset the file?', 'maxlimits-increase-maximum-limits'); ?></p>
                                    </div>
                                    <button type="button" class="ml-btn-danger" id="maxlimits-delete-recovery" 
                                        data-nonce="<?php echo wp_create_nonce('maxlimits-nonce'); ?>"
                                        style="height: 42px; padding: 0 24px; font-size: 13px;">
                                        <?php _e('Delete Link', 'maxlimits-increase-maximum-limits'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- INACTIVE STATE -->
                            <div style="text-align: center; max-width: 550px; margin: 0 auto; padding: 20px 0;">
                                <div style="background: #fff8f1; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 10px 20px rgba(245, 158, 11, 0.1);">
                                    <span class="dashicons dashicons-lock" style="font-size: 32px; width: 32px; height: 32px; color: #f59e0b;"></span>
                                </div>
                                <h3 style="margin: 0 0 12px; font-size: 22px; font-weight: 800; color: #1e293b;"><?php _e('Setup Your Recovery Lifeline', 'maxlimits-increase-maximum-limits'); ?></h3>
                                <p style="font-size: 15px; color: #64748b; line-height: 1.6; margin-bottom: 30px;">
                                    <?php _e('Generate a custom PHP script and secure it with a PIN. This link will be yours to keep for emergencies when the dashboard is unreachable.', 'maxlimits-increase-maximum-limits'); ?>
                                </p>
                                
                                <div style="display: flex; gap: 12px; align-items: stretch; max-width: 480px; margin: 0 auto;">
                                    <input type="password" id="maxlimits-recovery-pin" placeholder="<?php esc_attr_e('Set a 6-digit PIN', 'maxlimits-increase-maximum-limits'); ?>" 
                                        style="flex: 1; height: 56px; border-radius: 12px; font-size: 16px; border: 2px solid #e2e8f0; padding: 0 20px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); background: #fff; outline: none; margin: 0;">
                                    <button type="button" class="btn-primary" id="maxlimits-generate-recovery" 
                                        data-nonce="<?php echo wp_create_nonce('maxlimits-nonce'); ?>"
                                        style="height: 56px; padding: 0 32px; border-radius: 12px; font-weight: 800; background: #f59e0b; border: none; color: #fff; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2); cursor: pointer; white-space: nowrap;">
                                        <?php _e('Create Script', 'maxlimits-increase-maximum-limits'); ?>
                                    </button>
                                </div>

                                <p style="margin-top: 20px; font-size: 13px; color: #94a3b8; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <span class="dashicons dashicons-shield" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                    <?php _e('Highly Secure: We use industry-standard password hashing (bcrypt).', 'maxlimits-increase-maximum-limits'); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Secondary Info Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-top: 3rem; margin-bottom: 5rem;">
                    <!-- Lifesaver Card -->
                    <div class="maxlimits-card" style="border: none; border-radius: 16px; height: 100%;">
                        <div class="maxlimits-card-header" style="border: none; padding: 25px 30px 0;">
                            <h3 style="font-size: 18px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                                <span class="dashicons dashicons-heart" style="color: #ef4444;"></span>
                                <?php _e('Why this is a Lifesaver', 'maxlimits-increase-maximum-limits'); ?>
                            </h3>
                        </div>
                        <div class="maxlimits-card-body" style="padding: 20px 30px 30px; font-size: 14px; line-height: 1.7; color: #64748b;">
                            <p style="margin-top: 0;"><?php _e('Imagine your site crashes during a busy sale or right after a theme update. Your WordPress Admin is gone, showing only a white screen. You are locked out.', 'maxlimits-increase-maximum-limits'); ?></p>
                            <p><?php _e('This script is your **backdoor**. Because it runs independently of WordPress, it works even when your site is "dead". It gives you the power to:', 'maxlimits-increase-maximum-limits'); ?></p>
                            <ul style="padding-left: 20px; list-style: disc;">
                                <li style="margin-bottom: 10px;"><?php _e('Fix "Memory Exhausted" errors in under 5 seconds.', 'maxlimits-increase-maximum-limits'); ?></li>
                                <li style="margin-bottom: 10px;"><?php _e('Bypass server timeouts that prevent you from logging in.', 'maxlimits-increase-maximum-limits'); ?></li>
                                <li><?php _e('Restore access without needing FTP commands or hosting tech support.', 'maxlimits-increase-maximum-limits'); ?></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Common Errors Card -->
                    <div class="maxlimits-card" style="border: none; border-radius: 16px; height: 100%;">
                        <div class="maxlimits-card-header" style="border: none; padding: 25px 30px 0;">
                            <h3 style="font-size: 18px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                                <span class="dashicons dashicons-warning" style="color: #ef4444;"></span>
                                <?php _e('Critical Errors Fixed', 'maxlimits-increase-maximum-limits'); ?>
                            </h3>
                        </div>
                        <div class="maxlimits-card-body" style="padding: 20px 30px 30px;">
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <div style="padding: 12px 16px; background: #fff1f2; border-radius: 10px; border-left: 4px solid #f43f5e; font-family: monospace; font-size: 12px; color: #9f1239; font-weight: 700;">
                                    <?php _e('Memory size exhausted', 'maxlimits-increase-maximum-limits'); ?>
                                </div>
                                <div style="padding: 12px 16px; background: #fff1f2; border-radius: 10px; border-left: 4px solid #f43f5e; font-family: monospace; font-size: 12px; color: #9f1239; font-weight: 700;">
                                    <?php _e('Max execution time exceeded', 'maxlimits-increase-maximum-limits'); ?>
                                </div>
                                <div style="padding: 12px 16px; background: #fff1f2; border-radius: 10px; border-left: 4px solid #f43f5e; font-family: monospace; font-size: 12px; color: #9f1239; font-weight: 700;">
                                    <?php _e('Gateway Timeout (504 Errors)', 'maxlimits-increase-maximum-limits'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Failsafe Scripts -->
        <script>
            jQuery(document).ready(function($) {
                // Delete Link
                $(document).on('click', '#maxlimits-delete-recovery', function(e) {
                    e.preventDefault();
                    var btn = $(this);
                    if (confirm('<?php echo esc_js(__('Are you sure you want to delete your emergency recovery link?', 'maxlimits-increase-maximum-limits')); ?>')) {
                        var originalText = btn.text();
                        btn.prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'maxlimits-increase-maximum-limits')); ?>');
                        $.post(ajaxurl, {
                            action: 'maxlimits_delete_recovery',
                            security: btn.data('nonce')
                        }, function(response) {
                            if (response.success) { location.reload(); }
                            else { alert(response.data.message || 'Error deleting link.'); btn.prop('disabled', false).text(originalText); }
                        });
                    }
                });

                // Copy Link
                $(document).on('click', '#maxlimits-copy-link', function(e) {
                    var btn = $(this);
                    var urlInput = $('#maxlimits-recovery-url');
                    
                    urlInput.select();
                    document.execCommand('copy');
                    
                    var originalText = btn.html();
                    btn.html('<span class="dashicons dashicons-yes"></span> Copied!').css({
                        'background': '#10b981',
                        'transition': 'all 0.2s'
                    });
                    
                    setTimeout(function() {
                        btn.html(originalText).css('background', '');
                    }, 2000);
                });

                // Generate Link
                $('#maxlimits-generate-recovery').on('click', function(e) {
                    e.preventDefault();
                    var btn = $(this);
                    var pin = $('#maxlimits-recovery-pin').val();
                    if (pin.length < 4) { alert('<?php echo esc_js(__('PIN must be at least 4 characters.', 'maxlimits-increase-maximum-limits')); ?>'); return; }
                    var originalText = btn.text();
                    btn.prop('disabled', true).text('<?php echo esc_js(__('Generating...', 'maxlimits-increase-maximum-limits')); ?>');
                    $.post(ajaxurl, {
                        action: 'maxlimits_generate_recovery',
                        security: btn.data('nonce'),
                        pin: pin
                    }, function(response) {
                        if (response.success) { location.reload(); }
                        else { alert(response.data.message || 'Error creating script.'); btn.prop('disabled', false).text(originalText); }
                    });
                });
            });
        </script>
        <?php
    }


    /**
     * Render the Other Plugins page.
     */
    public function render_other_plugins_page()
    {
        $nonce   = wp_create_nonce('wp_rest');
        $api_url = esc_url(rest_url('maxlimits/v1/other-plugins'));
        ?>
        <div class="wrap" id="maxlimits-other-plugins-root" style="margin-left: -20px; padding: 20px;">
            <?php $this->render_common_header(); ?>
            <div style="max-width: 1200px; margin: 0 auto; padding: 40px 20px; font-family: 'Inter', system-ui, sans-serif;">
                <div style="margin-bottom: 60px; text-align: center;">
                    <h1 style="margin: 0 0 16px 0; font-size: 40px; font-weight: 800; color: #1e293b; letter-spacing: -0.03em; line-height: 1.1;">More from DominoPress</h1>
                    <p style="margin: 0; font-size: 18px; color: #64748b; max-width: 650px; margin-inline: auto; line-height: 1.6; font-weight: 500;">
                        Supercharge your WooCommerce store with our suite of premium tools. Designed for maximum revenue, efficiency, and customer satisfaction.
                    </p>
                </div>

                <div id="ml-plugin-grid" class="ml-plugin-grid">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <div class="ml-skeleton-card">
                            <div class="ml-skeleton-pulse ml-skeleton-img"></div>
                            <div class="ml-skeleton-body">
                                <div class="ml-skeleton-pulse ml-skeleton-title"></div>
                                <div class="ml-skeleton-pulse ml-skeleton-desc" style="width: 100%"></div>
                                <div class="ml-skeleton-pulse ml-skeleton-desc" style="width: 85%"></div>
                                <div class="ml-skeleton-pulse ml-skeleton-desc" style="width: 40%; margin-bottom: 32px;"></div>
                                <div style="display: flex; gap: 12px; margin-top: auto;">
                                    <div class="ml-skeleton-pulse ml-skeleton-action" style="flex: 1"></div>
                                    <div class="ml-skeleton-pulse ml-skeleton-action" style="flex: 1"></div>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const grid = document.getElementById('ml-plugin-grid');
                    const apiUrl = '<?php echo $api_url; ?>';
                    const nonce = '<?php echo $nonce; ?>';

                    fetch(apiUrl, {
                            headers: {
                                'X-WP-Nonce': nonce
                            }
                        })
                        .then(res => {
                            console.log('MaxLimits API Response Status:', res.status);
                            return res.json();
                        })
                        .then(res => {
                            console.log('MaxLimits API Response Data:', res);
                            if (res.success && res.data) {
                                renderPlugins(res.data);
                            } else {
                                console.error('MaxLimits API Error:', res);
                                grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; padding: 40px; color: #ef4444; background: #fee2e2; border-radius: 12px; font-weight: 600;">Failed to load plugins. Please check console for details.</p>';
                            }
                        })
                        .catch(err => {
                            console.error('MaxLimits API Fetch Error:', err);
                            grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; padding: 40px; color: #ef4444; background: #fee2e2; border-radius: 12px; font-weight: 600;">Network error occurred while fetching plugins. Check console.</p>';
                        });

                    function renderPlugins(plugins) {
                        grid.innerHTML = plugins.map(plugin => `
                        <div class="ml-plugin-card anim-fade-in">
                            <img src="${plugin.image}" alt="${plugin.title}" class="ml-plugin-img">
                            <div class="ml-plugin-body">
                                <h2 class="ml-plugin-title">${plugin.title}</h2>
                                <p class="ml-plugin-desc">${plugin.description}</p>
                                <div class="ml-plugin-actions">
                                    ${plugin.button1 ? `
                                        <a href="${plugin.button1.url}" target="_blank" rel="noopener noreferrer" class="ml-btn-primary">
                                            ${plugin.button1.text} <span class="dashicons dashicons-external" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                        </a>
                                    ` : ''}
                                    ${plugin.button2 ? `
                                        <a href="${plugin.button2.url}" target="_blank" rel="noopener noreferrer" class="ml-btn-secondary">
                                            ${plugin.button2.text} <span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                        </a>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `).join('');
                    }
                });
            </script>
        </div>
        <?php
    }

    public function render_crash_analytics_page()
    {
        ?>
        <div class="wrap maxlimits-wrap">
            <?php $this->render_tracking_notice(); ?>
            <?php $this->render_common_header(); ?>
            <div class="maxlimits-container" style="max-width: 800px; margin: 0 auto;">
                <div class="maxlimits-main" style="width: 100%;">
                    <!-- Crash Analytics Logger (PRO) -->
                    <div class="maxlimits-card" style="border-top: 4px solid #ef4444;">
                        <div class="maxlimits-card-header">
                            <h2 style="display: flex; align-items: center; gap: 8px;">
                                <span class="dashicons dashicons-chart-pie" style="color: #ef4444;"></span>
                                <?php _e('Fatal Crash Analytics', 'maxlimits-increase-maximum-limits'); ?>
                                <span style="font-size: 10px; font-weight: 800; background: #fee2e2; color: #ef4444; padding: 2px 6px; border-radius: 4px; border: 1px solid #fecaca; margin-left: auto;">PRO Only</span>
                            </h2>
                        </div>
                        <div class="maxlimits-card-body">
                            <p class="description" style="margin-top: 0; margin-bottom: 15px;">
                                <?php _e('When your site crashes silently due to memory limits, this smart logger catches it. It reveals the exact plugin file and URL that caused the fatal error.', 'maxlimits-increase-maximum-limits'); ?>
                            </p>
                            
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; position: relative; overflow: hidden;">
                                <?php if (!(defined('MAXLIMITS_IS_PRO') && MAXLIMITS_IS_PRO)): ?>
                                <div style="position: absolute; top:0; left:0; right:0; bottom:0; background: rgba(255,255,255,0.7); backdrop-filter: blur(3px); z-index: 2; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                                    <button type="button" class="btn-primary btn-upgrade-pro" style="background: #ef4444; border-color: #ef4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); height: 38px; padding: 0 20px; font-weight: bold; border-radius: 6px; color: #fff; display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                                        <span class="dashicons dashicons-lock" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                        Unlock Crash Analytics (PRO)
                                    </button>
                                </div>
                                <?php endif; ?>
                                <ul style="margin: 0; padding: 0; list-style: none;">
                                    <?php 
                                    $logs = get_option('maxlimits_crash_logs', []);
                                    $display_logs = empty($logs) ? [
                                        ['time' => current_time('mysql'), 'message' => 'Allowed memory size of 268435456 bytes exhausted (tried to allocate 1024000 bytes)', 'file' => '/wp-content/plugins/heavy-plugin/index.php:124'],
                                        ['time' => gmdate('Y-m-d H:i:s', time() - 3600), 'message' => 'Maximum execution time of 30 seconds exceeded', 'file' => '/wp-admin/includes/class-wp-upgrader.php:402'],
                                        ['time' => gmdate('Y-m-d H:i:s', time() - 86400), 'message' => 'Allowed memory size of 134217728 bytes exhausted', 'file' => '/wp-content/plugins/page-builder/core.php:89']
                                    ] : array_slice($logs, 0, 15);
                                    
                                    foreach ($display_logs as $log): ?>
                                        <li style="padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155;">
                                            <strong style="color: #ef4444;">[<?php echo esc_html(wp_date('H:i:s', strtotime($log['time']))); ?>]</strong> 
                                            <?php echo esc_html($log['message']); ?><br>
                                            <code style="background: transparent; color: #64748b; margin-top: 4px; display: inline-block;">File: <?php echo esc_html($log['file']); ?></code>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
