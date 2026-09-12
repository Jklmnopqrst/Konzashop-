
<?php
include_once 'config.php';

if (isset($_POST['login'])) {
    $email = strtolower(trim($_POST['email']));
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        if (!$user['is_verified']) {
            $url = "verify.php?email=" . urlencode($email);
            set_flash('error', "Please verify. 
            <a href='$url' style='color:#fff;
            text-decoration:underline;'>Enter code</a>");
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // FINAL REDIRECT LOGIC
            if ($user['role'] === 'admin') {
                $loc = 'admin_index.php';
            } else {
                $loc = 'index.php';
            }

            header("Location: $loc");
            exit;
        }
    } else {
        set_flash('error', "Invalid credentials.");
    }
}

include 'header.php';
display_flash();
?>
<div class="auth-container">
    <h2>Login</h2>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <div style="text-align: right; margin-bottom: 15px; margin: top -5px;">
            <a href="reset.php" style="font-size: 0.8rem; color:var(--primary);">
                Forgot Password?</a>
        </div>
        <button type="submit" name="login" class="btn-orange full-width">
            Sign In
        </button>
        <div class="auth-links">Don't have an account? <a href="register.php">Sign up</a></div>
    </form>
</div>
<?php include 'footer.php'; ?>
