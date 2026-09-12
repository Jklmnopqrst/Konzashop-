<?php 

include 'config.php'; 

if (isset($_POST['toggle_wishlist'])) {
    if (!is_logged_in()) {
        set_flash('error', "Log in to use wishlist.");
        header("Location: login.php");
        exit;
    }
    $pid = (int)$_POST['product_id'];
    $uid = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id=? AND product_id=?");
    $stmt->execute([$uid, $pid]);
    
    if ($stmt->fetch()) {
        $sql = "DELETE FROM wishlists WHERE user_id=? AND product_id=?";
        $pdo->prepare($sql)->execute([$uid, $pid]);
        set_flash('success', "Removed from wishlist.");
    } else {
        $sql = "INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)";
        $pdo->prepare($sql)->execute([$uid, $pid]);
        set_flash('success', "Added to wishlist!");
    }
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'wishlist.php'));
    exit;
}

include 'header.php'; 

if (!is_logged_in()) {
    echo "<div class='auth-container'><h2>Please log in.</h2></div>";
    include 'footer.php'; 
    exit;
}
?>
<div class="auth-container" style="max-width:900px;">
    <h2>My Wishlist</h2>
    <div class="product-grid">
        <?php
        $sql = "SELECT p.* FROM products p 
                JOIN wishlists w ON p.id=w.product_id WHERE w.user_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        
        $items = $stmt->fetchAll();
        if (!$items) echo "<p>Wishlist is empty.</p>";
        
        foreach ($items as $row) {
            echo "<div class='product-card'>";
            echo "<img src='{$row['image_url']}'>";
            echo "<h3>{$row['name']}</h3>";
            echo "<div class='price'><span class='new-price'>";
            echo "KSh " . number_format($row['price']) . "</span></div>";
            
            echo "<form method='POST' action='cart.php'>";
            echo "<input type='hidden' name='product_id' value='{$row['id']}'>";
            echo "<button type='submit' name='add_to_cart' class='btn-add-cart'>";
            echo "Add to Cart</button></form>";
            
            echo "<form method='POST'>";
            echo "<input type='hidden' name='product_id' value='{$row['id']}'>";
            echo "<button type='submit' name='toggle_wishlist' ";
            echo "style='background:none; border:none; color:red; margin-top:5px;'>";
            echo "Remove</button></form></div>";
        }
        ?>
    </div>
</div>
<?php include 'footer.php'; ?>