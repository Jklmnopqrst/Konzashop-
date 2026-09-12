<?php 
include 'config.php'; 
if (!is_admin()) { header("Location: login.php"); exit; }

$tab = $_GET['tab'] ?? 'overview';
if (isset($_POST['save_product'])) {
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    
    $old_price = !empty($_POST['old_price']) ? $_POST['old_price'] : null;
    
    $stock = (int)$_POST['stock'];
    $cat = $_POST['category'];
    $img = trim($_POST['image_url'] ?? '');
    if ($action == 'edit' && $id) {
        $sql = "UPDATE products SET name=?, price=?, old_price=?, stock=?, category=?, image_url=?
         WHERE id=?";
        $pdo->prepare($sql)->execute([$name, $price, $old_price, $stock, $cat, $img, $id]);
        set_flash('success', "Updated.");
    } else {
        $sql = "INSERT INTO products (name, price, old_price, stock, category, image_url)
         VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$name, $price, $old_price, $stock, $cat, $img]);
        set_flash('success', "Added.");
    }
    header("Location: admin_index.php");
    exit;
}

if (isset($_POST['update_status'])) {
    $id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $pdo->prepare("UPDATE orders
     SET status=? WHERE id=?")->execute([$status, $id]);
    set_flash('success', "Order updated.");
    header("Location: index.php?tab=orders");
    exit;
}

include 'admin_header.php'; 
?>
<div style="display:flex; gap:10px; margin-bottom:20px;
 background:rgba(0,0,0,0.03)">
    <a href="?tab=overview" class="btn-orange" style="padding:10px 20px;
     background:rgba(0,0,0,0.03)">
        Overview</a>
    <a href="?tab=orders" class="btn-orange" style="padding:10px 20px;
     background:rgba(0,0,0,0.03)">
        Orders</a>
    <a href="?tab=users" class="btn-orange" style="padding:10px 20px;">
        Users</a>
        <a href="products.php" class="btn-orange" style="padding:10px 20px;
        background:rgba(0,0,0,0.03)">
        Manage Products</a>
</div>

<?php if ($tab == 'overview'): ?>
    <div style="display:grid; grid-template-columns:repeat(3, 1fr);
     gap:20px;">
        <div style="background:#fff; padding:20px; border-radius:8px;">
            <h3>Total Orders</h3>
            <h2><?= $pdo->query("SELECT COUNT(*)
             FROM orders")->fetchColumn() ?></h2>
        </div>
        <div style="background:#fff; padding:20px; border-radius:8px;">
            <h3>Products</h3>
            <h2><?= $pdo->query("SELECT COUNT(*)
             FROM products")->fetchColumn() ?></h2>
        </div>
        <div style="background:#fff; padding:20px; border-radius:8px;">
            <h3>Users</h3>
            <h2><?= $pdo->query("SELECT COUNT(*)
             FROM users")->fetchColumn() ?></h2>
        </div>
    </div>

<?php elseif ($tab == 'orders'): ?>
    <table class="admin-table">
        <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Update</th>
        </tr>
        <?php
        $sql = "SELECT o.*, u.name FROM orders o 
                JOIN users u ON o.user_id=u.id ORDER BY o.id DESC";
        $orders = $pdo->query($sql)->fetchAll();
        foreach ($orders as $o): ?>
        <tr>
            <td>#<?= $o['id'] ?></td>
            <td><?= htmlspecialchars($o['name']) ?></td>
            <td>KSh <?= number_format($o['total_amount']) ?></td>
            <td><?= $o['status'] ?></td>
            <td>
                <form method="POST" style="display:flex; gap:5px;">
                    <input type="hidden" name="order_id"
                     value="<?= $o['id'] ?>">
                    <select name="status" style="padding:5px;">
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                    </select>
                    <button type="submit" name="update_status" 
                    class="btn-orange" style="padding:5px 10px;">
                    Save</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

<?php elseif ($tab == 'users'): ?>
    <table class="admin-table">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
        <th>Verified</th>
    </tr>
        <?php
        $users = $pdo->query("SELECT * FROM users
         ORDER BY id DESC")->fetchAll();
        foreach ($users as $u): ?>
        <tr>
            <td>#<?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= $u['role'] ?></td>
            <td><?= $u['is_verified'] ? 'Yes' : 'No' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php include 'footer.php';
 ?>