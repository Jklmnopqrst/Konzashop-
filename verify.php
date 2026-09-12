<?php 
include 'config.php'; 

$email = trim($_GET['email'] ?? '');

if (isset($_GET['token'])) {
    $stmt = $pdo->prepare("SELECT id, email, verify_expires 
    FROM users WHERE verify_token=? AND is_verified=0");
    $stmt->execute([$_GET['token']]);
    $u = $stmt->fetch();

    if (!$u) {
        set_flash('error', "Invalid or used link.");
    } elseif (strtotime($u['verify_expires']) < time()) {
        set_flash('error', "Link expired.");
        header("Location: verify.php?email=" . urlencode($u['email']));
        exit;
    } else {
        $sql = "UPDATE users SET is_verified=1, 
        verify_token=NULL, verify_code=NULL WHERE id=?";
        $pdo->prepare($sql)->execute([$u['id']]);
        set_flash('success', "Verified! You can sign in.");
        header("Location: login.php");
        exit;
    }
}

if (isset($_POST['verify_code'])) {
    $email = strtolower(trim($_POST['email']));
    $code = trim($_POST['code']);

    $sql = "SELECT id, verify_expires FROM users 
            WHERE email=? AND verify_code=? AND is_verified=0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $code]);
    $u = $stmt->fetch();

    if (!$u) {
        set_flash('error', "Incorrect code.");
    } elseif (strtotime($u['verify_expires']) < time()) {
        set_flash('error', "Code expired. Resend.");
    } else {
        $sql = "UPDATE users SET is_verified=1, 
        verify_token=NULL, verify_code=NULL WHERE id=?";
        $pdo->prepare($sql)->execute([$u['id']]);
        set_flash('success', "Verified! You can sign in.");
        header("Location: login.php");
        exit;
    }
}

include 'header.php'; 
?>
<div class="auth-container">
    <h2>Verify Email</h2>
    <p style="text-align:center; margin-bottom:15px;">
        Code sent to <b><?= htmlspecialchars($email) ?></b>
    </p>
    <form method="POST">
        <input type="email" name="email" 
        value="<?= htmlspecialchars($email) ?>" required>
        
        <input type="text" name="code" placeholder="6-digit code" 
        maxlength="6" required 
        style="text-align:center; letter-spacing:5px; font-size:1.2rem;">
        
        <button type="submit" name="verify_code" 
        class="btn-orange full-width">Verify</button>
    </form>
</div>
<?php include 'footer.php'; ?>