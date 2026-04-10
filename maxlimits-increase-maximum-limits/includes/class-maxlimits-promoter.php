<?php
/**
 * MaxLimits Promoter Class
 * 
 * Rotates DominoPress plugin features every 7 days based on ecommerce status.
 * Optimized with high-conversion copy, coordination, and a 3-day activation delay.
 */
class MaxLimits_Promoter {

    public function __construct() {
        add_action('admin_notices', [$this, 'display_promotion']);
        add_action('wp_ajax_dominopress_dismiss_promoter', [$this, 'dismiss_promoter']);
    }

    public function display_promotion() {
        // 1. Coordination and Dismissal check
        if (defined('DOMINOPRESS_NOTICE_SHOWN') || get_transient('dominopress_promoter_dismissed')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        // 2. DELAY: Don't show for the first 3 days after activation
        $first_activated = get_option('maxlimits_first_activated', 0);
        if ($first_activated && (time() - $first_activated) < (3 * DAY_IN_SECONDS)) {
            return;
        }

        // 3. Filter list to exclude ALREADY INSTALLED plugins
        $plugins = $this->get_filtered_plugin_list();
        if (empty($plugins)) {
            return;
        }

        // Lock the spot for this page load
        if (!defined('DOMINOPRESS_NOTICE_SHOWN')) {
            define('DOMINOPRESS_NOTICE_SHOWN', true);
        }

        // 4. Rotation logic based on Universal Reference Date
        $ref_time = 1704067200; 
        $days_passed = floor((time() - $ref_time) / DAY_IN_SECONDS);
        $weeks_passed = floor($days_passed / 7);
        $index = $weeks_passed % count($plugins);
        $featured = $plugins[$index];

        ?>
        <div id="dominopress-promoter-notice" class="notice notice-info is-dismissible" style="padding: 24px; border-left: 4px solid #6c5ce7; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-top: 25px; border-radius: 6px;">
            <div style="display: flex; align-items: flex-start; gap: 20px;">
                <div style="flex-grow: 1;">
                    <h3 style="margin: 0 0 12px 0; font-size: 22px; color: #6c5ce7; font-weight: 800; letter-spacing: -0.5px;">
                        <?php echo esc_html($featured['name']); ?>
                    </h3>
                    
                    <ul style="margin: 0 0 20px 20px; list-style-type: disc; color: #4b5563; font-size: 15px; line-height: 1.6;">
                        <?php foreach ($featured['features'] as $feature) : ?>
                            <li style="margin-bottom: 8px;"><?php echo esc_html($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <p style="margin: 0 0 20px 0; padding: 15px; background: #f3f4f6; border-radius: 8px; color: #1f2937; font-size: 15px; line-height: 1.5; border-left: 3px solid #6c5ce7;">
                        <span style="font-size: 20px; margin-right: 8px;">🚀</span> <strong style="color: #6c5ce7;">Why this is a lifesaver:</strong> <?php echo esc_html($featured['lifesaver']); ?>
                    </p>

                    <p style="margin: 0; display: flex; align-items: center; gap: 15px;">
                        <a href="<?php echo esc_url($featured['wp_org']); ?>" class="button button-primary button-large" target="_blank" style="background: #6c5ce7; border-color: #6c5ce7; height: 46px; line-height: 44px; padding: 0 32px; font-weight: 700; font-size: 15px; border-radius: 5px; box-shadow: 0 2px 4px rgba(108, 92, 231, 0.3);">Get Started for Free</a>
                        <?php if (isset($featured['url']) && !empty($featured['url'])) : ?>
                            <a href="<?php echo esc_url($featured['url']); ?>" class="button button-link" target="_blank" style="color: #6b7280; font-size: 14px; font-weight: 600; text-decoration: none;">Explore Full Features →</a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('#dominopress-promoter-notice').on('click', '.notice-dismiss', function() {
                        $.post(ajaxurl, { action: 'dominopress_dismiss_promoter' });
                    });
                });
            </script>
        </div>
        <?php
    }

    public function dismiss_promoter() {
        set_transient('dominopress_promoter_dismissed', true, 30 * DAY_IN_SECONDS);
        wp_send_json_success();
    }

    private function get_filtered_plugin_list() {
        $raw_list = $this->get_raw_plugins();
        $filtered = [];

        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        foreach ($raw_list as $plugin) {
            if (!is_plugin_active($plugin['slug'])) {
                $filtered[] = $plugin;
            }
        }

        return $filtered;
    }

    private function get_raw_plugins() {
        if (class_exists('WooCommerce')) {
            return [
                [
                    'name' => 'ShopCentral – Manage All Your Stores Like a Boss',
                    'slug' => 'shopcentral/shopcentral.php',
                    'features' => ['See every order from every store on one single screen', 'Clone products from one shop to another in seconds', 'Change prices across your whole network with one click'],
                    'lifesaver' => 'Managing multiple stores is a nightmare. This tool gives you back hours of your day and ensures you never miss a sale or order across your network.',
                    'url' => 'https://dominopress.com/plugin/shopcentral',
                    'wp_org' => 'https://wordpress.org/plugins/shopcentral/'
                ],
                [
                    'name' => 'InsightPress – The "Money Finder" for Your Store',
                    'slug' => 'insightpress-advanced-analytics-for-woocommerce/insightpress.php',
                    'features' => ['See exactly where customers are dropping off and why', 'Recover lost sales with better cart abandonment data', 'Understand your customers better than they know themselves'],
                    'lifesaver' => 'Most store owners are just guessing. You will know. Stop losing revenue to hidden leaks and start making data-driven decisions today.',
                    'url' => 'https://dominopress.com/plugin/insightpress',
                    'wp_org' => 'https://wordpress.org/plugins/insightpress-advanced-analytics-for-woocommerce/'
                ],
                [
                    'name' => 'PricePress – Smart Pricing That Sells More',
                    'slug' => 'pricepress-dynamic-pricing-for-woocommerce/pricepress.php',
                    'features' => ['Automatic "Buy More, Save More" deals that work', 'Flash sales that start and stop automatically by themselves', 'Psychological triggers like "Free Shipping" threshold alerts'],
                    'lifesaver' => 'Running discounts manually is slow and prone to errors. This tool runs your best sales strategies 24/7 while you focus on other things.',
                    'url' => '',
                    'wp_org' => 'https://wordpress.org/plugins/pricepress-dynamic-pricing-for-woocommerce/'
                ],
                [
                    'name' => 'ClaimPress – Turn Returns into Pure Profit',
                    'slug' => 'claimpress-warranty-refunds-returns-for-woocommerce/claimpress.php',
                    'features' => ['Let customers handle their own returns easily', 'Professional dashboard to solve claims in record time', 'Sell extended warranties just like the big retailers do'],
                    'lifesaver' => 'Returns don\'t have to be a headache. This tool saves your customer service team hours and builds massive trust with every customer.',
                    'url' => 'https://dominopress.com/plugin/claimpress',
                    'wp_org' => 'https://wordpress.org/plugins/claimpress-warranty-refunds-returns-for-woocommerce/'
                ],
                [
                    'name' => 'BundlePress – Boost Your Average Order Value',
                    'slug' => 'bundlepress/bundlepress.php',
                    'features' => ['Amazon-style "Frequently Bought Together" bundles', 'Mix & Match boxes that your customers will love', 'One-click upsells at the exact moment of checkout'],
                    'lifesaver' => 'Why sell one item when you can sell three? This tool is the single easiest way to increase your revenue per customer with zero effort.',
                    'url' => 'https://dominopress.com/plugin/bundlepress',
                    'wp_org' => 'https://wordpress.org/plugins/bundlepress/'
                ],
                [
                    'name' => 'ShippingGuard – Complete Peace of Mind',
                    'slug' => 'shippingguard/shippingguard.php',
                    'features' => ['Customers pay a small fee to protect their own shipments', 'Automated claims handling with zero stress for you', 'Create a new revenue stream for every single checkout'],
                    'lifesaver' => 'Lost shipments used to mean lost money. Now, they are a profitable and stress-free part of your business that customers appreciate.',
                    'url' => 'https://dominopress.com/plugin/shippingguard',
                    'wp_org' => 'https://wordpress.org/plugins/shippingguard/'
                ]
            ];
        } else {
            return [
                [
                    'name' => 'Redirect Master – Save Your Traffic After Migration',
                    'slug' => 'redirect-master/redirect-master.php',
                    'features' => ['Zero-setup mapping from your old site to the new one', 'Fix 404 errors before they hurt your Google ranking', 'Keep every single visitor and link from your old site'],
                    'lifesaver' => 'Moving your site? One wrong step can kill your SEO forever. This is your insurance policy for 100% traffic retention.',
                    'url' => 'https://dominopress.com/plugin/redirect-master',
                    'wp_org' => 'https://wordpress.org/plugins/redirect-master/'
                ],
                [
                    'name' => 'DominoPost – The Ultimate AI Content Machine',
                    'slug' => 'dominopost-advanced-post-editor/dominopost.php',
                    'features' => ['Write high-quality, ranking articles in seconds with AI', 'Automatically build a powerful internal link network', 'Professional SEO blocks like FAQ and Table of Contents'],
                    'lifesaver' => 'Writing is hard. Ranking is harder. This tool does both for you, saving you thousands on writers and SEO consultants every month.',
                    'url' => '',
                    'wp_org' => 'https://wordpress.org/plugins/dominopost-advanced-post-editor/'
                ],
                [
                    'name' => 'Autobute – Stop Doing Manual Image SEO',
                    'slug' => 'autobute-auto-image-attribute-bulk-updater/autobute-auto-image-attribute.php',
                    'features' => ['Automatically fix missing Alt Text and Titles', 'Clean up messy filenames for better Google rank', 'One-click optimization for your entire media library'],
                    'lifesaver' => 'Manually fixing 1000s of images is impossible. This tool does it in a single click, boosting your SEO and accessibility instantly.',
                    'url' => 'https://wordpress.org/plugins/autobute-auto-image-attribute-bulk-updater/',
                    'wp_org' => 'https://wordpress.org/plugins/autobute-auto-image-attribute-bulk-updater/'
                ],
                [
                    'name' => 'Resetify – The "Panic Button" for WordPress',
                    'slug' => 'resetify/resetify.php',
                    'features' => ['Safely reset any part of your site that is broken', 'Hunter-killer for database bloat and orphan tables', 'Automatic safety snapshots before you touch anything'],
                    'lifesaver' => 'Site acting weird? Broken plugin? This is the tool that fixes the "White Screen of Death" when nothing else will. It’s a literal lifesaver.',
                    'url' => 'https://dominopress.com/plugin/resetify',
                    'wp_org' => 'https://wordpress.org/plugins/resetify/'
                ],
                [
                    'name' => 'DominoPilot – Your 24/7 AI Sales Assistant',
                    'slug' => 'dominopilot-ai/dominopilot.php',
                    'features' => ['Answers customer questions instantly and accurately', 'Sells products and checks availability for you', 'Zero-setup—it learns everything from your site content'],
                    'lifesaver' => 'You can\'t be online 24/7. This AI can. It handles the support while you focus on the big picture of growing your business.',
                    'url' => '',
                    'wp_org' => 'https://wordpress.org/plugins/dominopilot-ai/'
                ]
            ];
        }
    }
}
