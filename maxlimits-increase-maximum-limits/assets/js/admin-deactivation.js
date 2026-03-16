jQuery(document).ready(function ($) {
    var maxLimitsSlug = 'maxlimits-increase-maximum-limits/maxlimits-increase-maximum-limits.php';
    var deactivating = false;

    // Listen for click on the specific deactivate link
    $('tr[data-plugin="' + maxLimitsSlug + '"] .deactivate a').on('click', function (e) {
        if (deactivating) {
            return true;
        }

        e.preventDefault();
        var deactivateUrl = $(this).attr('href');

        // Create Modal HTML
        var modalHtml = '<div id="maxlimits-deactivate-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 99999; display: flex; align-items: center; justify-content: center;">' +
            '<div style="background: #fff; width: 450px; padding: 40px; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); text-align: center; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Ubuntu, Cantarell, \'Helvetica Neue\', sans-serif;">' +
            '<div style="margin-bottom: 20px;">' +
            '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L1 21H23L12 2ZM13 18H11V16H13V18ZM13 14H11V8H13V14Z" fill="#FFA500" stroke="#FFA500" stroke-width="1" stroke-linejoin="round"/></svg>' +
            '</div>' +
            '<h2 style="margin: 0 0 15px; font-size: 22px; color: #1e1e1e; font-weight: 600;">Keep Your Limits Safe?</h2>' +
            '<p style="font-size: 15px; line-height: 1.6; color: #50575e; margin-bottom: 25px;">' +
            'Deactivating <strong>MaxLimits</strong> may revert your Upload Size & Memory Limits to default (e.g., 2MB).' +
            '<br><br>' +
            'Hosting providers often reset configuration files during updates. MaxLimits ensures your limits stay applied.' +
            '<br><br>' +
            '<span style="color: #10b981; font-weight: 500;">✔ MaxLimits is lightweight (0% load) and only runs when you need it.</span>' +
            '</p>' +
            '<div style="display: flex; gap: 15px; justify-content: center; align-items: center;">' +
            '<button id="maxlimits-keep-plugin" style="background: #007cba; color: #fff; border: 1px solid #007cba; padding: 10px 24px; font-size: 15px; border-radius: 4px; cursor: pointer; font-weight: 600; text-decoration: none;">Keep MaxLimits Safe</button>' +
            '<a href="' + deactivateUrl + '" id="maxlimits-confirm-deactivate" style="display: inline-block; padding: 10px 20px; font-size: 14px; color: #d63638; text-decoration: none; border: 1px solid #d63638; border-radius: 4px; font-weight: 500;">No, I want small limits</a>' +
            '</div>' +
            '</div>' +
            '</div>';

        $('body').append(modalHtml);

        // Handle "Keep" button
        $('#maxlimits-keep-plugin').on('click', function (e) {
            e.preventDefault();
            $('#maxlimits-deactivate-modal').remove();
        });

        // Handle "Deactivate" button - no need for special handler as it is a direct link
    });
});
