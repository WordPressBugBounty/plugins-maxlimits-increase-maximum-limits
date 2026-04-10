jQuery(document).ready(function ($) {

    // --- Tabs Logic ---
    function initTabs() {
        const triggers = $('.tab-btn');
        const contents = $('.tab-content');

        triggers.on('click', function (e) {
            e.preventDefault();
            const target = $(this).data('target');

            triggers.removeClass('active');
            $(this).addClass('active');

            contents.removeClass('active');
            $('#' + target).addClass('active');
        });
    }
    initTabs();

    // --- Custom Input Limits Toggle ---
    function handleCustomInputs() {
        $('.maxlimits-select').each(function () {
            const select = $(this);
            const wrapper = select.closest('.input-wrapper');
            const customInput = wrapper.find('.maxlimits-custom-input');
            let previousValue = select.val();

            function update() {
                previousValue = select.val();

                if (select.val() === 'custom') {
                    customInput.fadeIn(200).focus();
                    select.css('max-width', '140px');
                } else {
                    customInput.hide();
                    select.css('max-width', '100%');
                }
            }

            select.on('change', function() {
                update();
            });
            update(); // Init status
        });
    }
    handleCustomInputs();

    // --- Presets Logic ---
    const presets = {
        'standard': { upload_max_filesize: '64M', post_max_size: '64M', memory_limit: '256M', max_execution_time: '300', max_input_time: '300', max_input_vars: '3000' },
        'woocommerce': { upload_max_filesize: '128M', post_max_size: '128M', memory_limit: '512M', max_execution_time: '600', max_input_time: '600', max_input_vars: '5000' },
        'pagebuilder': { upload_max_filesize: '256M', post_max_size: '256M', memory_limit: '512M', max_execution_time: '600', max_input_time: '600', max_input_vars: '10000' },
        'maximum': { upload_max_filesize: '1024M', post_max_size: '1024M', memory_limit: '1024M', max_execution_time: '3600', max_input_time: '3600', max_input_vars: '20000' }
    };

    $('.preset-btn').on('click', function () {
        const btn = $(this);
        const preset_type = btn.data('preset');
        const is_pro = btn.data('pro');

        if (is_pro) {
            showUpgradeModal('The ' + btn.text().replace('(PRO)', '').trim() + ' optimizer is a PRO feature.');
            return;
        }

        const values = presets[preset_type];

        if (values) {
            $.each(values, function (key, val) {
                const select = $('#' + key);
                if (select.find("option[value='" + val + "']").length) {
                    select.val(val).trigger('change');
                } else {
                    select.val('custom').trigger('change');
                    $('input[name="limits[' + key + '_custom]"]').val(parseInt(val));
                }
            });

            const originalText = btn.html();
            btn.html('<span class="dashicons dashicons-yes" style="color: #10b981;"></span> Applied!').prop('disabled', true);
            setTimeout(() => {
                btn.html(originalText).prop('disabled', false);
            }, 2000);
        }
    });

    // --- AJAX Save Settings ---
    $('#maxlimits-main-form').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const btn = form.find('.submit-btn');
        const originalText = btn.find('.text').text();

        // Add loading state
        btn.addClass('loading').prop('disabled', true);
        btn.find('.text').text('Saving...');

        // Prepare data
        const formData = new FormData(this);
        formData.append('action', 'maxlimits_save_settings');
        formData.append('security', maxlimitsParams.nonce);

        $.ajax({
            url: maxlimitsParams.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    showToast(response.data.message || 'Settings saved successfully!', 'success');
                    
                    if (response.data.warnings && response.data.warnings.length > 0) {
                        response.data.warnings.forEach(function(warning, index) {
                            setTimeout(() => showToast(warning, 'warning'), (index + 1) * 800);
                        });
                    }
                    
                    // Refresh server limits display
                    // Delaying by 1500ms to allow LiteSpeed/Apache to apply .htaccess changes to new PHP workers
                    setTimeout(refreshServerLimits, 1500);
                } else {
                    showToast(response.data.message || 'Error saving settings.', 'error');
                }
            },
            error: function () {
                showToast('Server error. Please try again.', 'error');
            },
            complete: function () {
                btn.removeClass('loading').prop('disabled', false);
                btn.find('.text').text(originalText);
            }
        });
    });

    // --- Refresh Server Limits (Real-time update) ---
    function refreshServerLimits() {
        const list = $('#maxlimits-server-values');
        const btn = $('#ml-refresh-status');
        
        console.log('MaxLimits: Refreshing server limits...');
        list.css('opacity', '0.5');
        btn.addClass('rotating');

        $.ajax({
            url: maxlimitsParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'maxlimits_get_server_limits',
                security: maxlimitsParams.nonce
            },
            success: function (response) {
                console.log('MaxLimits: AJAX success', response);
                if (response.success) {
                    list.html(response.data.html);
                }
            },
            error: function(xhr, status, error) {
                console.error('MaxLimits: AJAX error', status, error);
            },
            complete: function () {
                list.animate({ opacity: 1 }, 200);
                setTimeout(() => {
                    console.log('MaxLimits: Refresh complete');
                    btn.removeClass('rotating');
                }, 500);
            }
        });
    }

    // Refresh button click
    $(document).on('click', '#ml-refresh-status', function(e) {
        e.preventDefault();
        console.log('MaxLimits: Refresh button clicked');
        refreshServerLimits();
    });

    // --- Toast Notification ---
    function showToast(message, type = 'success') {
        // For success, remove existing success toasts. For warnings/errors, keep them.
        if (type === 'success') $('.ml-toast.success').remove();

        const icons = {
            'success': '<svg class="ml-toast-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
            'error': '<svg class="ml-toast-icon" style="color:#ef4444" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'warning': '<svg class="ml-toast-icon" style="color:#f59e0b" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'
        };

        const borderColors = {
            'success': '#10b981',
            'error': '#ef4444',
            'warning': '#f59e0b'
        };

        const toast = $(`
            <div class="ml-toast ${type}" style="border-left: 4px solid ${borderColors[type]};">
                ${icons[type] || icons.success}
                <div class="ml-toast-content">
                    <p>${message}</p>
                </div>
            </div>
        `);

        $('body').append(toast);
        toast[0].offsetWidth; // force reflow
        toast.addClass('show');

        // Warnings and errors stay longer
        const duration = type === 'success' ? 3000 : 8000;

        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // --- Recovery Link Generation ---
    $(document).on('click', '#maxlimits-generate-recovery', function (e) {
        e.preventDefault();
        const pin = $('#maxlimits-recovery-pin').val();

        if (pin.length < 4) {
            showToast('PIN must be at least 4 characters.', 'error');
            return;
        }

        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('Generating...');

        $.post(maxlimitsParams.ajaxurl, {
            action: 'maxlimits_generate_recovery',
            security: maxlimitsParams.nonce,
            pin: pin
        }, function (response) {
            if (response.success) {
                showToast('Recovery link generated successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(response.data.message || 'Failed to generate link.', 'error');
                btn.prop('disabled', false).text(originalText);
            }
        }).fail(function () {
            showToast('Network error. Please try again.', 'error');
            btn.prop('disabled', false).text(originalText);
        });
    });

    // --- Recovery Link Deletion ---
    $(document).on('click', '#maxlimits-delete-recovery', function (e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete your emergency recovery link? This will permanently disable your rescue URL.')) {
            return;
        }

        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('Deleting...');

        $.post(maxlimitsParams.ajaxurl, {
            action: 'maxlimits_delete_recovery',
            security: maxlimitsParams.nonce
        }, function (response) {
            if (response.success) {
                showToast('Recovery link deleted.', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('Error deleting recovery link.', 'error');
                btn.prop('disabled', false).text(originalText);
            }
        }).fail(function () {
            showToast('Network error.', 'error');
            btn.prop('disabled', false).text(originalText);
        });
    });
    // --- Upgrade Modal ---
    function showUpgradeModal(message) {
        $('.ml-modal-overlay').remove(); // Clean up

        const modalHtml = `
            <div class="ml-modal-overlay">
                <div class="ml-modal-content">
                    <div class="ml-modal-close">&times;</div>
                    <div class="ml-modal-header">
                        <div class="ml-modal-icon">PRO</div>
                        <h3>Upgrade to MaxLimits PRO</h3>
                    </div>
                    <div class="ml-modal-body">
                        <p>${message}</p>
                        <ul class="ml-pro-features">
                            <li><span class="dashicons dashicons-yes"></span> Emergency Recovery Lifeline</li>
                            <li><span class="dashicons dashicons-yes"></span> 1-Click WooCommerce & Elementor Optimizers</li>
                            <li><span class="dashicons dashicons-yes"></span> Set Any Custom File & Memory Limits</li>
                            <li><span class="dashicons dashicons-yes"></span> Priority Support from DominoPress</li>
                        </ul>
                    </div>
                    <div class="ml-modal-footer">
                        <a href="https://dominopress.com/plugin/maxlimits" target="_blank" class="btn-primary" style="width: 100%; justify-content: center; font-size: 16px; padding: 14px;">Get MaxLimits PRO Now</a>
                        <p style="text-align: center; margin-top: 12px; font-size: 12px; color: #94a3b8;">30-Day Money Back Guarantee</p>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        setTimeout(() => $('.ml-modal-overlay').addClass('show'), 10);

        $('.ml-modal-close, .ml-modal-overlay').on('click', function(e) {
            if (e.target !== this) return;
            $('.ml-modal-overlay').removeClass('show');
            setTimeout(() => $('.ml-modal-overlay').remove(), 300);
        });
    }

    $(document).on('click', '.btn-upgrade-pro', function(e) {
        e.preventDefault();
        showUpgradeModal('Get all premium features and priority support with MaxLimits PRO.');
    });
});
