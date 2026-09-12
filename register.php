<?php 
include_once 'config.php'; 
include_once 'functions.php';

$errors = [];
$old = [
    'name' => '',
    'email' => '',
    'phone' => ''
];

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = strtolower(trim($_POST['email']));
    $phone = trim($_POST['phone']);
    $pw = $_POST['password'];
    $old = compact('name', 'email', 'phone');

    if ($e = validate_name($name)) {
        $errors[] = $e;
    }
    if ($e = validate_email($email)) {
        $errors[] = $e;
    }
    if ($e = validate_phone($phone)) {
        $errors[] = $e;
    }
    if ($e = validate_password($pw)) {
        $errors[] = $e;
    }
    
    if ($pw !== ($_POST['confirm_password'] ?? '')) {
        $errors[] = "Passwords do not match.";
    }

    $n_phone = normalize_phone($phone);

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email already registered.";
        }

        if (!empty($n_phone)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone=?");
            $stmt->execute([$n_phone]);
            if ($stmt->fetch()) {
                $errors[] = "Phone already registered.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));
            $code = sprintf('%06d', random_int(100000, 999999));
            $exp = date('Y-m-d H:i:s', strtotime('+30 mins'));

            $sql = "INSERT INTO users 
            (name, email, phone, password, verify_token, verify_code, verify_expires) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $name,
                $email,
                $n_phone,
                $hash,
                $token,
                $code,
                $exp
            ]);

            $sent = send_verification_email($email, $name, $code, $token);

            $msg = "Account created! Check your email for the 6-digit code.";
            if (!$sent && APP_ENV === 'development') {
                $msg .= "<br><small>Dev Code: $code</small>";
            }
            set_flash('success', $msg);
            header("Location: verify.php?email=" . urlencode($email));
            exit;

        } catch (PDOException $e) {
            if (APP_ENV === 'development') {
                $errors[] = "DB Error: " . $e->getMessage();
            } elseif ($e->getCode() == 23000) {
                $errors[] = "Email or phone already registered.";
            } else {
                $errors[] = "Registration failed. Try again.";
            }
        }
    }

    if (!empty($errors)) {
        set_flash('error', implode('<br>', $errors));
    }
}

include 'header.php'; 
display_flash();
?>
<div class="auth-container">
    <h2>Create Account</h2>
    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" 
        value="<?= htmlspecialchars($old['name']) ?>" required>
        
        <input type="email" name="email" placeholder="Email Address" 
        value="<?= htmlspecialchars($old['email']) ?>" required>
        
        <input type="text" name="phone" placeholder="Phone (2547XXXXXXXX)" 
        value="<?= htmlspecialchars($old['phone']) ?>" required>
        
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" 
        placeholder="Confirm Password" required>
        
        <button type="submit" name="register" class="btn-orange full-width">
            Create Account
        </button>
        <div class="auth-links">Already have an account?
             <a href="login.php" style="padding-top: 20px; font-size:0.85rem; color:var(--primary);">Login</a></div>
    </form>
</div>
<?php include 'footer.php'; ?>
