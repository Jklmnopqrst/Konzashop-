<?php 
session_start();
include 'config.php'; 

if (isset($_POST['add_to_cart'])) {
    $id = (int)$_POST['product_id'];
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id=?");
    $stmt->execute([$id]);
    $stock = (int)$stmt->fetchColumn();
    $cur = $_SESSION['cart'][$id] ?? 0;

    if ($stock <= 0) {
        set_flash('error', "Out of stock.");
    } elseif ($cur + 1 > $stock) {
        set_flash('error', "Only $stock left.");
    } else {
        $_SESSION['cart'][$id] = $cur + 1;
        set_flash('success', "Added to cart!");
    }
    header("Location: cart.php");
    exit;
}

if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][(int)$_GET['remove']]);
    header("Location: cart.php");
    exit;
}

include 'header.php'; 
?>
<div class="auth-container" style="max-width:800px;">
    <h2>Your Cart</h2>
    <?php if (empty($_SESSION['cart'])): ?>
        <p>Cart is empty. <a href="index.php" 
        style="color:var(--primary);">Shop now</a></p>
    <?php else: ?>
        <table class="admin-table">
            <tr><th>Product</th><th>Qty</th><th>Price</th><th></th></tr>
            <?php
            $total = 0;
            foreach ($_SESSION['cart'] as $id => $qty):
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
                $stmt->execute([$id]);
                $p = $stmt->fetch();
                if (!$p) continue;
                $sub = $p['price'] * $qty;
                $total += $sub;
            ?>
            <tr>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= $qty ?></td>
                <td>KSh <?= number_format($sub) ?></td>
                <td><a href="cart.php?remove=<?= $id ?>" 
                style="color:#dc2626;">Remove</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <h3 style="margin:20px 0; text-align:right;">
            Total: KSh <?= number_format($total) ?>
        </h3>
        <a href="payment.php" class="btn-orange full-width" 
        style="display:block; text-align:center;">
            Proceed to pay
        </a>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>