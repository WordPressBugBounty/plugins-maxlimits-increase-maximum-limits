=== MaxLimits - Increase Maximum Upload, Post & PHP Limits ===
Contributors: dominopress
Tags: max upload size, php limits, memory limit, execution time, max_input_vars
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.7.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: maxlimits-increase-maximum-limits

Easily increase max upload size, post size, PHP memory limit, and execution time directly from your WordPress dashboard.
Fix common limit errors.

== Description ==

Struggling with errors like "upload_max_filesize exceeded" or hitting PHP memory limits?
**MaxLimits** provides a simple, user-friendly interface to increase common WordPress and PHP resource limits without needing to edit server files like `php.ini` or `.htaccess`.

MaxLimits is now a freemium plugin. While the core features remain free, we have introduced a PRO version for power users and mission-critical sites.

**FREE Version Features:**
*   **Increase Limits:** Adjust upload size, memory limit, execution time, and more.
*   **Standard Optimizer:** 1-Click setup for standard WordPress sites.
*   **Live Server Status:** Monitor your server's actual limits in real-time.
*   **Manual Code Generator:** Get `.htaccess` and `.user.ini` snippets for manual patching.
*   **Direct File Writing:** Automate limit increases via `.htaccess`.

**PRO Version Features:**
*   **Emergency Recovery Mode:** A standalone "Rescue Link" to fix your site in seconds even if WordPress crashes. 
*   **Advanced 1-Click Optimizers:** Instant setup for WooCommerce, Elementor, Divi, and "Maximum Power" modes.
*   **Custom Limit Values:** Set any specific value you want for your resource limits.
*   **Export/Import Settings**: Move your high-performance configurations between sites.
*   **Priority Support**: Get direct help from the developers at DominoPress.

MaxLimits allows you to adjust:

* **Max Upload File Size (`upload_max_filesize`):** Upload larger images, videos, and files.
* **Max Post Size (`post_max_size`):** Ensure large form submissions or page builder saves work correctly.
* **PHP Memory Limit (`memory_limit`):** Provide more resources for demanding plugins like WooCommerce or page builders.
* **Max Execution Time (`max_execution_time`):** Allow longer processes like imports, exports, or backups to complete without timing out.
* **Max Input Time (`max_input_time`):** Give scripts more time to parse input data, especially important during large uploads.
* **Max Input Vars (`max_input_vars`):** Increase the limit for complex menus, theme options, or page builder saves.

**Why Choose MaxLimits?**

* **Ultra-Premium UI**: Beautiful, modern dashboard with animated shimmer logo and smooth transitions.
* **Emergency Recovery Mode**: Your site's "Panic Button" for when things go wrong and you're locked out.
* **Two-in-One Solution:** Attempts to set limits at runtime (via `ini_set()`) and provides an optional, automated `.htaccess` file-writing method for restrictive hosts.
* **Automated .htaccess Writing (Opt-in):** If `ini_set()` is blocked, you can enable a feature to write rules directly to your `.htaccess` file, just like a caching plugin.
* **Flexible Value Selection:** Choose recommended values from dropdowns or enter a specific custom value.
* **Context-Aware Recommendations:** Get smarter guidance on limits based on active plugins like WooCommerce.
* **Live Server Status:** See the *actual* limits currently active on your server, confirming if changes took effect.
* **Dashboard Widget:** View your current server limits at a glance right from the WordPress dashboard.
* **Manual Code Generator:** If you don't want to use the automated writer, the plugin still generates `.user.ini` and `.htaccess` code snippets for you to apply manually.
* **Lightweight & Secure:** Built following WordPress best practices. No bloat.
* **Opt-in Usage Tracking:** Help improve the plugin by allowing anonymous, non-sensitive usage data collection (fully optional).
* **Always Free Core Features:** A powerful tool for all WordPress users.

MaxLimits first attempts to modify these settings at runtime using standard PHP functions.
*Please note:* Some hosting providers may restrict this. For those hosts, you can enable the experimental **Direct .htaccess Writing** feature.
The "Current Server Values" panel will always show you the limits your server is actually enforcing.
This plugin is developed by **DominoPress**.

**Check out our other WordPress Plugins:**

*   **ShopCentral** - The ultimate Multi-Store Manager for WooCommerce. Manage products, orders, and analytics across your entire network from one central dashboard. ([Free Version](https://wordpress.org/plugins/shopcentral/) | [PRO Version](https://dominopress.com/plugin/shopcentral))
*   **InsightPress** - Advanced analytics for WooCommerce. Track revenue, product performance, and customer behavior with beautiful, actionable dashboards. ([Free Version](https://wordpress.org/plugins/insightpress-advanced-analytics-for-woocommerce/) | [PRO Version](https://dominopress.com/plugin/insightpress))
*   **Redirect Master** - Redirect Old Domain’s Traffic to New Domain. ([Get it Here](https://dominopress.com/plugin/redirect-master))
*   **Autobute** - Auto image alt tags, title, caption etc. Image SEO Suit. ([Free Version](https://wordpress.org/plugins/autobute-auto-image-attribute-bulk-updater/))
*   **PricePress** - Simple WooCommerce plugin for advanced, quantity-based pricing rules. ([Free Version](https://wordpress.org/plugins/pricepress-dynamic-pricing-for-woocommerce/))
*   **ClaimPress** - Advanced Warranty, Return, Refund & Exchange for WooCommerce. Powerful warranty and claim management system for WooCommerce stores. ([Free Version](https://wordpress.org/plugins/claimpress-warranty-refunds-returns-for-woocommerce/) | [PRO Version](https://dominopress.com/plugin/claimpress))
*   **DominoPost** - Advanced Post Editor & AI Writer. ([Free Version](https://wordpress.org/plugins/dominopost-advanced-post-editor/))

== Installation ==

1.  Upload the `maxlimits-increase-maximum-limits` folder to your `/wp-content/plugins/` directory via FTP or WordPress admin ('Plugins' > 'Add New' > 'Upload Plugin').
2.  Activate the 'MaxLimits - Increase Maximum Upload, Post & PHP Limits' plugin through the 'Plugins' menu in WordPress.
3.  (Optional) When prompted after activation, choose whether to allow anonymous usage tracking to help improve the plugin.
4.  Navigate to `Settings > MaxLimits` in your WordPress admin dashboard.
5.  Select your desired limit values from the dropdowns or choose "Custom" and enter your preferred number.
6.  Click "Save Limits".
7.  Check the "Current Server Values" panel.
If the values didn't change, check the **"Enable Direct .htaccess Writing"** box and click "Save Writing Method".
Then, save your limits again by clicking "Save Limits".
Check the "Current Server Values" panel again.
You may need to refresh the page to see the new values.

== Screenshots ==

1.  The main MaxLimits settings page: Clean interface with limit controls, contextual recommendations, and server status.

== Frequently Asked Questions ==

= I saved new values, but "Current Server Values" didn't change. Why? =

This is common.
It means your web hosting provider has locked these specific PHP settings at the server level, preventing the default `ini_set()` function from working.
**Solution:**
1.  Go to `Settings > MaxLimits`.
2.  In the "Direct File Writing" card, check the box that says **"Enable Direct .htaccess Writing (Experimental)"**.
3.  Click the "Save Writing Method" button.
4.  Now, set your desired limits in the "Limit Settings" card above.
5.  Click the "Save Limits" button. This should now work in one click.
6.  The plugin will write the rules to your `.htaccess` file.
7.  You should see a "Settings saved" notice and a blue notice saying ".htaccess file updated successfully."
8.  **You must refresh the page** to see the new values appear in the "Current Server Values" panel.
This is normal for `.htaccess` changes.

= What are generally good values to set? =

The plugin provides inline, context-aware recommendations.
Generally:
* **Memory Limit:** `256MB` is okay for basic sites, but `512MB` or `1024MB` is often better for sites using WooCommerce, page builders (Elementor, Divi, etc.), or many plugins.
* **Upload/Post Size:** `64MB` or `128MB` is usually sufficient unless you need to upload very large video files.
Ensure `post_max_size` is equal to or greater than `upload_max_filesize`.
* **Execution/Input Time:** `300` or `600` seconds is often needed for tasks like large imports, exports, or backups that take longer to run.
* **Max Input Vars:** `3000` is a good baseline, but page builders or complex menus often require `5000` or more.

= What data is collected if I allow usage tracking? =

We believe in complete transparency. We take your privacy very seriously.
**We do NOT collect any sensitive data.** No personal information, no site content, no passwords, and no specific settings are ever collected.

To provide you with better service and develop more relevant features, we may collect some **anonymous, non-sensitive usage data** if you opt-in.
This data serves a single purpose: **Optimization & Improvement.**

**What is collected (only if you opt-in):**
*   Site URL & Name (to differentiate installs)
*   WordPress & PHP Version (to ensure compatibility with your environment)
*   Plugin Version (to track update adoption)
*   Server Software (to debug server-specific issues like Apache vs Nginx)
*   Admin Email (Optional: Used strictly for critical security alerts or support context if you contact us; not for marketing spam).

This helps us prioritize which features to build next and which old PHP/WP versions we need to support.
You can skip this at any time.

= What is Emergency Recovery Mode? =

If your site crashes due to a "Memory Exhausted" error or a timeout from a heavy plugin update, you often can't reach the WordPress dashboard to fix it. 

**Emergency Recovery Mode** creates a standalone PHP file on your server (e.g., `maxlimits-recovery-xxxx.php`). You save this URL and your PIN somewhere safe. If your site ever goes down, you visit that link directly, enter your PIN, and click "1-Click Fix". It will instantly patch your server's limits to get your site back online without needing to touch FTP or contact tech support.

= Is this plugin safe? =

Yes.
It uses the standard WordPress Settings API and follows security best practices.
The default method only *attempts* to modify PHP settings temporarily at runtime using `ini_set()`.
The optional "Direct .htaccess Writing" feature is **off by default** and only uses the official `insert_with_markers()` WordPress function, which is the safest way to modify the file.

== Changelog ==

= 1.7.0 =
* **Feature:** Added text-based "Refresh" button to Live Server Status.
* **UX:** Replaced icon-only refresh with a clear, text button with automated loading animation.
* **Improvement:** Added specific .htaccess troubleshooting instructions for LiteSpeed hosts.
* **Tracking:** Added WooCommerce and Admin Email tracking for better support.
* **Fix:** Removed deprecated Notice system.
* **Update:** Version bump to 1.7.0.

= 1.6.2 =
* **Feature:** Added browser cache-busting logic for dashboard scripts to resolve Refresh button behavior.
* **Update:** Version bump to 1.6.2.

= 1.6.1 =
* **Feature:** Deactivating or uninstalling the plugin now actively reverts and cleans up the server's limit configurations.
* **Update:** Removed wp-config.php direct modification logic for a cleaner, native WordPress experience.
* **Update:** Added cross-promotion links for DominoPress plugins.
* **Update:** Version bump to 1.6.1.

= 1.6.0 =
* **Feature:** Enhanced logic for .htaccess writing.
* **Feature:** Improved Live Server Status with target-value comparison.
* **Feature:** Added automatic conflict resolution to purge legacy recovery rules.
* **Update:** Version bump to 1.6.0.

= 1.5.0 =
* **Feature:** Launched **Freemium Model**.
* **Limit:** 1-Click Optimizers (WooCommerce, Elementor, Maximum) are now PRO features.
* **Limit:** Emergency Recovery is now a PRO feature.
* **Limit:** Custom limit inputs are now a PRO feature.
* **UI:** Updated branding to "Free" and added PRO upsell modals.
* **Feature:** Added **Emergency Recovery Mode** (PRO) - A secure backdoor to fix your site when wp-admin is unreachable.
* **Update:** Major aesthetic overhaul and performance optimizations.
* **UI:** Major aesthetic overhaul with animated "Shimmer" branding and ultra-premium layout.
* **Security:** Implemented 6-digit Bcrypt PIN protection for the recovery script.
* **Feature:** Changed plugin sub menu name to "Increase Limits".
* **Update:** Version bump to 1.5.0.

= 1.4.0 =
* **Feature:** Enhanced Live Server Status UI with a modern grid layout.
* **Update:** Updated internal version handling for better tracking accuracy.
* **Improvement:** General UI polish and performance tweaks.

= 1.3.2 =
* **Enhancement:** Added new safety checks to prevent accidental configuration loss.
* **Improvement:** Improved admin menu accessibility for easier access.
* **Tweak:** UI updates for a cleaner dashboard experience.
* **Fix:** Minor text and styling corrections.

= 1.3.1 =
* **Fix:** Updated the API endpoint URL for the optional usage tracking to ensure data is correctly received.
* **Improvement:** Added better error logging for API connection issues.
* **Update:** Removed debug code for tracking reset.
* **Update:** Version bump for stability.

= 1.3.0 =
* **Performance Fix:** Fixed a critical issue where the plugin was making blocking remote requests on every page load. Implemented transient caching (12 hours) to ensure zero impact on site speed.
* **Fix:** Fixed an issue where the tracking notice dismissal (X button) was not working reliably.
* **UX Improvement:** Server Status now correctly converts and displays values like "2G" as "2048M" for better readability and consistent comparison.
* **UX Improvement:** Added a helpful tip in the sidebar for users whose values are not updating.
* **UI:** Added "Our Other Plugins" button to the header and improved header layout.
* **Update:** General code cleanup and version bump.

= 1.2.7 =
* **Major UI Overhaul:** Rebuilt the entire settings page with a modern, smooth, "premium" design.
* **New:** AJAX-powered saving for instant updates without page reloads.
* **New:** Real-time server limit checking.
* **Improvement:** Better file structure and code organization.
* **Improvement:** Enhanced "Dark Mode" friendly color scheme.

= 1.2.1 =
* Added "Rate Plugin" link on the Plugins page.
* Enhanced plugin stability with improved notices.
* Added guidance for .htaccess writing mode.
* Updated tracking system to respect permanent dismissal preference immediately.

= 1.1.0 =
* **New Feature:** Added setting for `max_input_vars`.
* **New Feature:** Added a Dashboard Widget.
* **New Feature:** Added a `.user.ini` and `.htaccess` code generator.
* **New Feature:** Implemented context-aware recommendations.

= 1.0.0 =
* Initial public release of MaxLimits!
Includes settings page, custom values, inline recommendations, server status display, and optional anonymous usage tracking.