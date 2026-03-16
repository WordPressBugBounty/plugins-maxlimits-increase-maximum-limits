<?php
if (!defined('ABSPATH')) {
    exit;
}

class MaxLimits_Recovery {
    
    public function __construct() {
        add_action('wp_ajax_maxlimits_generate_recovery', [$this, 'ajax_generate_recovery']);
        add_action('wp_ajax_maxlimits_delete_recovery', [$this, 'ajax_delete_recovery']);
    }

    public function ajax_generate_recovery() {
        check_ajax_referer('maxlimits-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $pin = isset($_POST['pin']) ? sanitize_text_field($_POST['pin']) : '';
        if (strlen($pin) < 4) {
            wp_send_json_error(['message' => 'PIN must be at least 4 characters']);
        }

        $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
        $filename = 'maxlimits-recovery-' . strtolower(wp_generate_password(8, false, false)) . '.php';
        $filepath = ABSPATH . $filename;
        
        $script_content = $this->get_recovery_script_template($hashed_pin);

        if (file_put_contents($filepath, $script_content)) {
            update_option('maxlimits_recovery_file', $filename);
            wp_send_json_success(['filename' => $filename, 'url' => site_url('/' . $filename)]);
        } else {
            wp_send_json_error(['message' => 'Failed to create recovery script. Check directory permissions.']);
        }
    }

    public function ajax_delete_recovery() {
        check_ajax_referer('maxlimits-nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $filename = get_option('maxlimits_recovery_file');
        if ($filename) {
            $filepath = ABSPATH . $filename;
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
            delete_option('maxlimits_recovery_file');
        }
        
        wp_send_json_success(['message' => 'Recovery link deleted.']);
    }

    private function get_recovery_script_template($hashed_pin) {
        $php = '<?php' . PHP_EOL;
        $php .= '$stored_hash = \'' . addslashes($hashed_pin) . '\';' . PHP_EOL;
        $php .= <<<'PHP'
session_start();

$message = '';
$message_type = '';

if (isset($_POST['pin'])) {
    if (password_verify($_POST['pin'], $stored_hash)) {
        $_SESSION['maxlimits_recovery_auth'] = true;
    } else {
        $message = "Invalid PIN.";
        $message_type = "error";
    }
}
PHP;

        $php .= <<<'PHP'


if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . basename(__FILE__));
    exit;
}

$is_auth = !empty($_SESSION['maxlimits_recovery_auth']);

if ($is_auth && isset($_POST['action']) && $_POST['action'] === 'fix_limits') {
    $limits = [
        'upload_max_filesize' => '1024M',
        'post_max_size' => '1024M',
        'memory_limit' => '1024M',
        'max_execution_time' => '3600',
        'max_input_time' => '3600',
        'max_input_vars' => '20000'
    ];
    
    // Write to .user.ini
    $user_ini = __DIR__ . '/.user.ini';
    $ini_content = "";
    if (file_exists($user_ini)) {
        $ini_content = file_get_contents($user_ini);
    }
    
    $ini_lines = [ "\n; MaxLimits Emergency Recovery" ];
    foreach ($limits as $k => $v) {
        $ini_lines[] = "{$k} = {$v}";
    }
    file_put_contents($user_ini, $ini_content . implode("\n", $ini_lines) . "\n");

    // Try to update .htaccess safely
    $htaccess = __DIR__ . '/.htaccess';
    if (file_exists($htaccess) && is_writable($htaccess)) {
        $ht_content = file_get_contents($htaccess);
        $ht_lines = [ "\n# BEGIN MaxLimits Emergency Recovery" ];
        foreach ($limits as $k => $v) {
            $ht_lines[] = "php_value {$k} {$v}";
        }
        $ht_lines[] = "# END MaxLimits Emergency Recovery\n";
        file_put_contents($htaccess, $ht_content . implode("\n", $ht_lines));
    }

    $message = "Limits successfully increased safely via .user.ini and .htaccess! You should now be able to access your wp-admin.";
    $message_type = "success";
}

if ($is_auth && isset($_POST['action']) && $_POST['action'] === 'fix_limits_manual') {
    $mem = isset($_POST['memory_limit']) ? intval($_POST['memory_limit']) . 'M' : '512M';
    $exec = isset($_POST['max_execution_time']) ? intval($_POST['max_execution_time']) : '300';
    
    $user_ini = __DIR__ . '/.user.ini';
    $ini_content = file_exists($user_ini) ? file_get_contents($user_ini) : "";
    $ini_lines = [ "\n; MaxLimits Manual Recovery", "memory_limit = {$mem}", "max_execution_time = {$exec}" ];
    file_put_contents($user_ini, $ini_content . implode("\n", $ini_lines) . "\n");

    $htaccess = __DIR__ . '/.htaccess';
    if (file_exists($htaccess) && is_writable($htaccess)) {
        $ht_content = file_get_contents($htaccess);
        $ht_lines = [ "\n# BEGIN MaxLimits Manual Recovery", "php_value memory_limit {$mem}", "php_value max_execution_time {$exec}", "# END MaxLimits Manual Recovery\n" ];
        file_put_contents($htaccess, $ht_content . implode("\n", $ht_lines));
    }

    $message = "Manual limits applied successfully!";
    $message_type = "success";
}

?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MaxLimits Emergency Recovery</title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
    .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; }
    h1 { margin-top: 0; color: #0f172a; font-size: 22px; }
    p { color: #64748b; font-size: 14px; line-height: 1.5; }
    input[type=password] { width: 100%; box-sizing: border-box; padding: 12px; margin: 15px 0; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px; text-align: center; letter-spacing: 2px;}
    button { background: #10b981; color: white; border: none; padding: 12px 20px; font-size: 16px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; }
    button:hover { background: #059669; }
    .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
    .alert-error { background: #fee2e2; color: #b91c1c; }
    .alert-success { background: #d1fae5; color: #047857; text-align: left; }
    .logout { display: block; margin-top: 20px; color: #64748b; text-decoration: none; font-size: 13px; }
</style>
</head>
<body>
<div class="card">
    <h1>MaxLimits Emergency Recovery</h1>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if (!$is_auth): ?>
        <p>Enter your recovery PIN to access the emergency panel.</p>
        <form method="post">
            <input type="password" name="pin" placeholder="••••••" required>
            <button type="submit">Unlock Panel</button>
        </form>
    <?php else: ?>
        <p>If your WordPress site has crashed due to memory or execution timeouts, you can fix it here.</p>
        
        <form method="post" style="margin-bottom: 25px;">
            <input type="hidden" name="action" value="fix_limits">
            <button type="submit">1-Click Fix (Maximum Power)</button>
        </form>

        <div style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
            <p style="font-weight: bold; margin-bottom: 10px;">Manual Increase</p>
            <form method="post" style="text-align: left;">
                <input type="hidden" name="action" value="fix_limits_manual">
                
                <div style="margin-bottom: 10px;">
                    <label style="font-size: 12px; font-weight: 600;">Memory Limit (MB)</label>
                    <input type="number" name="memory_limit" value="512" style="width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #cbd5e1; border-radius: 4px;">
                </div>

                <div style="margin-bottom: 10px;">
                    <label style="font-size: 12px; font-weight: 600;">Execution Time (Sec)</label>
                    <input type="number" name="max_execution_time" value="300" style="width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #cbd5e1; border-radius: 4px;">
                </div>

                <button type="submit" style="background: #64748b; margin-top: 10px;">Update Manually</button>
            </form>
        </div>

        <a href="?logout=1" class="logout">Lock Panel & Logout</a>
    <?php endif; ?>
</div>
</body>
</html>
PHP;
        return $php;
    }
}
