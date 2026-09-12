<?php
function validate_name($name) {
    if (strlen(trim($name)) < 2) {
        return "Name is too short.";
    }
    if (!preg_match("/^[a-zA-Z\s\-]+$/", $name)) {
        return "Name can only contain letters.";
    }
    return null;
}

function validate_email($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format.";
    }
    return null;
}

function validate_phone($phone) {
    $clean = preg_replace('/[\s\-\(\)]/', '', $phone);
    if (!preg_match('/^(\+254|254|0)(7\d{8}|1\d{8})$/', $clean)) {
        return "Invalid Kenyan phone number. Use 07XX or 01XX.";
    }
    return null;
}

function normalize_phone($phone) {
    $clean = preg_replace('/[\s\-\(\)]/', '', $phone);
    if (preg_match('/^(\+254|254|0)(7\d{8}|1\d{8})$/', $clean, $m)) {
        return '254' . $m[2];
    }
    return $clean;
}

function validate_password($pw) {
    if (strlen($pw) < 8) {
        return "Password needs at least 8 characters.";
    }
    if (!preg_match('/[A-Z]/', $pw)) {
        return "Needs at least one uppercase letter.";
    }
    if (!preg_match('/[0-9]/', $pw)) {
        return "Needs at least one number.";
    }
    return null;
}

// ================= MAILER =================
function send_verification_email($email, $name, $code, $token) {
    $subject = "Your Konza verification code: $code";
    $link = "http://localhost/konza/verify.php?token=$token";
    
    $html = "<h2>Konza Marketplace</h2>";
    $html .= "<p>Hi $name,</p>";
    $html .= "<p>Your code is: <b>$code</b></p>";
    $html .= "<p>Expires in 30 minutes.</p>";
    $html .= "<p><a href='$link'>Click to verify</a></p>";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Konza <noreply@konza.co.ke>\r\n";

    $sent = @mail($email, $subject, $html, $headers);

    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0777, true);
    }
    $log = date('Y-m-d H:i:s') . " | $email | $code | $token\n";
    @file_put_contents("$log_dir/mail.log", $log, FILE_APPEND);

    return $sent;
}
function send_password_reset_email($email, $name, $token) {
    $subject = "Reset your Konza Password";
    $link = "http://localhost/konzashop/reset.php?token=$token";
    
    $html = "<h2>Konza Marketplace</h2>";
    $html .= "<p>Hi $name,</p>";
    $html .= "<p>You requested to reset your password. Click the link below:</p>";
    $html .= "<p><a href='$link'>Reset My Password</a></p>";
    $html .= "<p>This link expires in 1 hour.</p>";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Konza <noreply@konza.co.ke>\r\n";

    $sent = @mail($email, $subject, $html, $headers);

    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) @mkdir($log_dir, 0777, true);
    $log = date('Y-m-d H:i:s') . " | RESET: $email | Link: $link\n";
    @file_put_contents("$log_dir/mail.log", $log, FILE_APPEND);

    return $sent;
}
?>
