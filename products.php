<?php 
include 'config.php'; 
if (!is_admin()) { header("Location: ../login.php");
     exit; }

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

if (isset($_POST['delete_product'])) {
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    set_flash('success', "Product deleted.");
    header("Location: products.php");
    exit;
}

if (isset($_POST['save_product'])) {
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $old_price = !empty($_POST['old_price']);
    $stock = (int)$_POST['stock'];
    $cat = $_POST['category'];
    
    $img = $_POST['existing_image'] ?? 'https://via.placeholder.com/300';
    if (!empty($_FILES['image']['name'])) {
        $dir = "uploads/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        
        $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif']; // all image formats
        
        if (in_array($ext, $allowed)) {
            $fn = time() . "_" . basename($_FILES["image"]["name"]);
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $dir . $fn)) {
                $img = "uploads/" . $fn;
            }
        } else {
            die("Only images allowed: jpg, png, webp, gif");
        }
    }
    
    

    if ($action == 'edit' && $id) {
        $sql = "UPDATE products SET name=?, price=?, old_price?, stock=?, category=?, image_url=? 
                WHERE id=?";
        $pdo->prepare($sql)->execute([$name, $price, $old_price, $stock, $cat, $img, $id]);
        set_flash('success', "Updated.");
    } else {
        $sql = "INSERT INTO products (name, price, old_price, stock, category, image_url) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$name, $price, $old_price, $stock, $cat, $img]);
        set_flash('success', "Added.");
    }
    header("Location: products.php");
    exit;
}

$editing = null;
if ($action == 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $editing = $stmt->fetch();
}

include 'admin_header.php'; 
?>
<?php if ($action == 'add' || $action == 'edit'): ?>
    <div class="auth-container" style="margin:0; max-width:600px;">
        <h2><?= $action == 'edit' ? 'Edit' : 'Add' ?> Product</h2>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($editing): ?>
                <input type="hidden" name="existing_image" 
                value="<?= htmlspecialchars($editing['image_url']) ?>">
            <?php endif; ?>
            
            <input type="text" name="name" placeholder="Product Name" 
            value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
            
            <input type="number" name="price" placeholder="Price" 
            value="<?= $editing['price'] ?? '' ?>" required>
            <input type="number" name="old_price" placeholder="Old Price (optional)"
             value="<?= $editing['old_price'] ?? '' ?>" style="width:100%; padding:12px;
              margin-bottom:15px;">
            <input type="number" name="stock" placeholder="Stock Qty" 
            value="<?= $editing['stock'] ?? '' ?>" required>
            
            <select name="category" style="width:100%; padding:12px; margin-bottom:15px;">
                <option value="phones & Tablets">Phones</option>
                <option value="Electronics">Electronics</option>
                <option value="Fashion">Fashion</option>
                <option value="Home & Living">Home & Living</option>
                <option value="Supermarket">Supermarket</option>
            </select>
            
            <input type="file" name="image" accept="image/*" 
            style="margin-bottom:15px;">
            
            <button type="submit" name="save_product" class="btn-orange full-width">
                Save Product
            </button>
        </form>
    </div>
<?php else: ?>
    <div class="flex-between" style="margin-bottom:20px;">
        <h2>Products</h2>
        <a href="products.php?action=add" class="btn-orange" 
        style="padding:10px 20px;">+ Add Product</a>
    </div>
    <table class="admin-table">
        <tr>
            <th>Image</th>
            <th>Name</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Action</th>
        </tr>
        <?php
        $products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
        foreach ($products as $p):
            $img = str_starts_with($p['image_url'], 'http') ? 
            $p['image_url'] : "../{$p['image_url']}";
        ?>
        <tr>
            <td><img src="Konzashop/uploads/<?php echo basename($row['image_url']);
             ?>"></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td>KSh <?= number_format($p['price']) ?></td>
            <td><?= $p['stock'] ?></td>
            <td>
                <a href="?action=edit&id=<?= $p['id'] ?>" 
                style="color:var(--primary);">Edit</a>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" name="delete_product" 
                    style="color:red; border:none; background:none; cursor:pointer;"
                    onclick="return confirm('Delete?');">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php include 'footer.php'; ?>