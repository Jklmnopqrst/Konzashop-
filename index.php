<?php 
include 'config.php';
include 'header.php';
$where=""; $params=[]; $is_filtered=false;
if(!empty($_GET['search'])){
     $where=" WHERE (name LIKE ? OR category LIKE ?) ";
      $term="%".trim($_GET['search'])."%";
       $params=[$term,$term]; $is_filtered=true;
     }
elseif(!empty($_GET['category'])){
     $where=" WHERE category = ? ";
      $params=[$_GET['category']];
       $is_filtered=true;
     }
elseif(isset($_GET['deals'])){
     $where=" WHERE old_price IS NOT NULL AND old_price > price AND old_price > 1 ";
      $is_filtered=true; }
?>
<aside class="sidebar">
    <h3>CATEGORIES</h3>
    <ul>
        <li><a href="index.php?category=Phones+%26+Tablets">
            <i class="fa-solid fa-mobile-screen"></i>
             Phones</a></li>
        <li><a href="index.php?category=Fashion">
            <i class="fa-solid fa-shirt"></i>
             Fashion</a></li>
        <li><a href="index.php?category=Home+%26+Living">
            <i class="fa-solid fa-couch"></i> Home</a></li>
        <li><a href="index.php?category=Electronics">
            <i class="fa-solid fa-laptop">
            </i> Electronics</a></li>
        <li><a href="index.php?category=Supermarket">
            <i class="fa-solid fa-cart-flatbed"></i>
             Supermarket</a></li>
    </ul>
</aside>
<main class="content-area">
<?php if($is_filtered): ?>
    <div class="section-header">
        <h2>Results</h2><a href="index.php"
         class="see-all-link">Clear <i class="fa-solid fa-xmark">
         </i></a>
        </div>
    <div class="product-grid">
    <?php $sql="SELECT * FROM products".$where." ORDER BY id DESC";
     $stmt=$pdo->prepare($sql); $stmt->execute($params);
      foreach($stmt->fetchAll() as $row){
        $disc = 0;
        if(!empty($row['old_price']) && $row['old_price'] > $row['price']
         && $row['old_price'] > 1){
          $disc = round(($row['old_price']-$row['price'])/$row['old_price']*100);
        } ?>
        <div class="product-card">
        <?php if($disc > 0 && $disc <= 90){ ?>
            <span class="discount-badge">-<?php echo $disc; ?>% OFF</span>
        <?php } ?>
        <img src="<?php echo htmlspecialchars($row['image_url']);
         ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
        <div class="price">
            <span class="new-price">KSh <?php echo number_format($row['price']); ?></span>
            <?php if(!empty($row['old_price']) && $row['old_price'] > $row['price']){ ?>
                <span class="old-price">KSh <?php echo number_format($row['old_price']); ?></span>
            <?php } ?>
        </div>
        <form method="POST" action="cart.php">
            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
            <button type="submit" name="add_to_cart" class="btn-add-cart">Add to Cart</button>
        </form>
        </div>
    <?php } ?>
    </div>
  
<?php else: ?>
    <!-- HERO BANNER -->
    <div class="hero-banner">
        <div class="hero-text">
            <span class="hero-badge">KONZA MARKET PLACE</span>
            <h1>Deals up to 50% OFF</h1>
            <p>Shop Electronics, Fashion, Home & More</p>
            <div class="hero-btns">
                <a href="index.php?deals=1"
                 class="btn-hero-white">Shop Now
                  <i class="fa-solid fa-chevron-right"></i></a>
                </div>
        </div>
        <div class="hero-images">
            <img src="images/samsung_galaxy.jpg"
             class="img-iphone">
            <img src="images/iphone_fold.webp" class="img-galaxy">
            <img src="images/hauwei_fold.webp" class="img-sneakers">
        </div>
    </div>
</main>
</div> 
<!-- FLASH DEALS -->
<div class="container full-width-deals">
    <div class="section-header" id="flash-deals">
        <h2>FLASH DEALS</h2>
        <a href="index.php?deals=1"
         class="see-all-link">See All
          <i class="fa-solid fa-arrow-right">
          </i></a>
        </div>
    <div class="product-grid">
    <?php 
$sql="SELECT * FROM products WHERE old_price IS NOT NULL AND old_price > price
 AND old_price > 1 ORDER BY id DESC LIMIT 8";
foreach($pdo->query($sql)->fetchAll() as $row){
  $disc = 0;
  if(!empty($row['old_price']) && $row['old_price'] > $row['price'] && $row['old_price'] > 1){
    $disc = round(($row['old_price']-$row['price'])/$row['old_price']*100);
  }
?>
<div class="product-card" style="position: relative; overflow:hidden;">
  <?php if($disc > 0 && $disc <= 90){ ?>
    <span class="discount-badge">-<?php echo $disc; ?>% OFF</span>
  <?php } ?>
  <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php
   echo htmlspecialchars($row['name']); ?>">
  <h3><?php echo htmlspecialchars($row['name']); ?></h3>
  <div class="price">
    <span class="new-price">KSh <?php echo number_format($row['price']); ?></span>
    <span class="old-price">KSh <?php echo number_format($row['old_price']); ?></span>
  </div>
  <form method="POST" action="cart.php">
    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
    <button type="submit" name="Add_to_Cart" class="btn-add-cart">Add to Cart</button>
  </form>
</div>
<?php } ?>
    </div>
</div>
<div class="container main-layout" style="margin-top:0;">
<main class="content-area" style="flex:1;">
<?php endif; ?>
</main>
</div>
<?php include 'footer.php'; ?>
