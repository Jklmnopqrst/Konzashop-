<?php 
include 'config.php'; 
include 'functions.php';

if (!is_logged_in()) { 
    header("Location: login.php"); 
    exit; 
}

$uid = $_SESSION['user_id'];
$errors = [];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    
    if ($e = validate_name($name)) $errors[] = $e;
    if ($e = validate_phone($phone)) $errors[] = $e;
    
    $n_phone = normalize_phone($phone);

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone=? AND id!=?");
        $stmt->execute([$n_phone, $uid]);
        if ($stmt->fetch()) $errors[] = "Phone number is already in use.";
    }

    if (empty($errors)) {
        $sql = "UPDATE users SET name=?, phone=? WHERE id=?";
        $pdo->prepare($sql)->execute([$name, $n_phone, $uid]);
        $_SESSION['name'] = $name;
        set_flash('success', "Profile updated successfully.");
        header("Location: profile.php");
        exit;
    }
}

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $user['password'])) {
        $errors[] = "Current password is incorrect.";
    } elseif ($e = validate_password($new)) {
        $errors[] = $e;
    } elseif ($new !== $confirm) {
        $errors[] = "New passwords do not match.";
    }

    if (empty($errors)) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $uid]);
        set_flash('success', "Password changed successfully.");
        header("Location: profile.php");
        exit;
    }
}

include 'header.php'; 
?>
<div class="main-layout">
    <!-- SIDEBAR -->
    <aside class="sidebar" style="height: fit-content;">
        <h3>My Account</h3>
        <ul>
            <li><a href="profile.php" style="color:var(--primary); font-weight:bold;">
                <i class="fa-regular fa-user"></i> Profile</a></li>
            <li><a href="dashboard.php">
                <i class="fa-solid fa-box"></i> My Orders</a></li>
            <li><a href="wishlist.php">
                <i class="fa-regular fa-heart"></i> Wishlist</a></li>
            <li><a href="logout.php" style="color:#dc2626;">
                <i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="content-area">
        <h2>My Profile</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" style="margin-top:15px;">
                <?= implode('<br>', $errors) ?>
            </div>
        <?php endif; ?>

        <div class="profile-forms-wrapper">
            <!-- Form 1: Basic Info -->
            <div class="profile-card">
                <h3 style="margin-bottom:15px;">Personal Details</h3>
                <form method="POST">
                    <label>Email Address</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                    
                    <button type="submit" name="update_profile" class="btn-orange full-width" style="margin-top:5px;">
                        Update Profile
                    </button>
                </form>
            </div>

            <!-- Form 2: Change Password -->
            <div class="profile-card">
                <h3 style="margin-bottom:15px;">Change Password</h3>
                <form method="POST">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                    
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                    
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                    
                    <button type="submit" name="change_password" class="btn-orange full-width" style="background:#1f2937; margin-top:5px;">
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>
<?php include 'footer.php'; ?>