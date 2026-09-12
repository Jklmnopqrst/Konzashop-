<?php 

include 'config.php'; 
if (!is_logged_in()) { header("Location: login.php"); exit; }

if (isset($_GET['mark_read'])) {
    $sql = "UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0";
    $pdo->prepare($sql)->execute([$_SESSION['user_id']]);
    header("Location: dashboard.php");
    exit;
}

$uid = $_SESSION['user_id'];
$notifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC");
$notifs->execute([$uid]);
$notifications = $notifs->fetchAll();

$orders_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC");
$orders_stmt->execute([$uid]);
$orders = $orders_stmt->fetchAll();

include 'header.php'; 
?>
<div style="display:flex; gap:20px; width:100%;">
    <aside class="sidebar" style="width:200px;">
        <h3>My Account</h3>
        <ul>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="dashboard.php#orders">My Orders</a></li>
            <li><a href="wishlist.php">Wishlist</a></li>
            <li><a href="logout.php" style="color:#dc2626;">Logout</a></li>
        </ul>
    </aside>
    
    <div style="flex:1;">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</h2>
        
        <div style="margin:25px 0;">
            <div class="flex-between">
                <h3>Notifications</h3>
                <a href="dashboard.php?mark_read=1" 
                style="font-size:0.8rem; color:var(--primary);">Mark Read</a>
            </div>
            <?php if (!$notifications): ?>
                <p style="color:gray;">No notifications.</p>
            <?php endif; ?>
            <?php foreach ($notifications as $n): ?>
                <div style="background:<?= $n['is_read'] ? '#fff' : '#fef3c7' ?>; 
                padding:15px; border-radius:8px; margin-bottom:10px;">
                    <b><?= htmlspecialchars($n['title']) ?></b>
                    <p style="font-size:0.9rem;"><?= htmlspecialchars($n['message']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 id="orders">Order History</h3>
        <table class="admin-table">
            <tr><th>Order</th><th>Amount</th><th>Address</th><th>ETA</th><th>Status</th></tr>
            <?php if (!$orders): ?>
                <tr><td colspan="5">No orders yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td>#<?= $o['id'] ?></td>
                    <td>KSh <?= number_format($o['total_amount']) ?></td>
                    <td><?= htmlspecialchars($o['delivery_address']) ?></td>
                    <td><?= date('d M H:i', strtotime($o['delivery_eta'])) ?></td>
                    <td><span class="status-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>