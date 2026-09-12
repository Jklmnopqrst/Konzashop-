<?php 
/* FILE: reset.php */
include 'config.php'; 
include 'functions.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$valid_token = false;

// 1. HANDLE PASSWORD UPDATE (If token exists)

if ($token) {
    $stmt = $pdo->prepare("SELECT id, reset_expires FROM users WHERE reset_token=?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user && strtotime($user['reset_expires']) > time()) {
        $valid_token = true;
    } else {
        $error = "This reset link is invalid or has expired.";
    }

    if (isset($_POST['update_password']) && $valid_token) {
        $pw = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        if ($e = validate_password($pw)) {
            $error = $e;
        } elseif ($pw !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?";
            $pdo->prepare($sql)->execute([$hash, $user['id']]);

            set_flash('success', "Password successfully reset! You can now log in.");
            header("Location: login.php");
            exit;
        }
    }
} 
// ==========================================
// 2. HANDLE EMAIL REQUEST (If no token)
// ==========================================
elseif (isset($_POST['request_reset'])) {
    $email = strtolower(trim($_POST['email']));
    
    if ($e = validate_email($email)) {
        $error = $e;
    } else {
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $new_token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $sql = "UPDATE users SET reset_token=?, reset_expires=? WHERE id=?";
            $pdo->prepare($sql)->execute([$new_token, $expires, $user['id']]);

            $sent = send_password_reset_email($email, $user['name'], $new_token);

            $msg = "If this email is registered, a reset link has been sent.";
            if (!$sent && APP_ENV === 'development') {
                $link = "reset.php?token=$new_token";
                $msg .= "<br><br><b>DEV MODE:</b> <a href='$link' style='color:#fff;text-decoration:underline;'>Click here to reset</a>";
            }
            set_flash('success', $msg);
        } else {
            set_flash('success', "If this email is registered, a reset link has been sent.");
        }
        header("Location: reset.php");
        exit;
    }
}

include 'header.php'; 
?>
<div class="auth-container">
    
    <?php if ($token): ?>
        <!-- ================= RESET PASSWORD VIEW ================= -->
        <h2>Create New Password</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
            <div style="text-align:center; margin-top:20px;">
                <a href="reset.php" class="btn-orange" style="display:inline-block; padding:10px 20px;">Request New Link</a>
            </div>
        <?php elseif ($valid_token): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <input type="password" name="password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                
                <button type="submit" name="update_password" class="btn-orange full-width">
                    Reset Password
                </button>
            </form>
        <?php endif; ?>

    <?php else: ?>
        <!-- ================= FORGOT PASSWORD VIEW ================= -->
        <h2>Forgot Password</h2>
        <p style="text-align:center; color:var(--text-muted); margin-bottom:20px; font-size:0.9rem;">
            Enter your email and we'll send a link to reset your password.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Email Address" required>
            <button type="submit" name="request_reset" class="btn-orange full-width">
                Send Reset Link
            </button>
        </form>
        <p style="text-align:center; margin-top:15px; font-size:0.9rem;">
            Remembered? <a href="login.php" style="color:var(--primary); font-weight:600;">Sign In</a>
        </p>
    <?php endif; ?>

</div>
<?php include 'footer.php'; ?>