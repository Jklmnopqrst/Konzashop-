
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konza City Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" 
    rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header class="main-header">
        <div class="container header-container">
        <img src ="images/logo.jpg" class="logo-img">
            <a href="index.php" class="logo"><span>Konza</span></a>
            <div class="home">
                <a href="index.php"><img src="images/home.png" class="home-img"></a>
            </div>
            <form action="index.php" method="GET" class="search-bar">
                <div class="search-input-wrapper">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="search" placeholder="Search for products..." 
                     value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                     </div>
                <button type="submit" class="search-btn">
                    <i class="fa-solid fa-magnifying-glass"></i> Search</button>
    
            </form>
            <div class="header-actions">
                <?php if (is_logged_in()): ?>
                    <?php $uid=$_SESSION['user_id']; $unread=(int)$pdo->query("SELECT COUNT(*)
                     FROM notifications WHERE user_id=$uid AND is_read=0")->fetchColumn(); ?>
                    <a href="profile.php" class="action-item">
                        <div style="position:relative;">
                            <i class="fa-regular fa-user"></i>
                            <?php if($unread>0): ?><span class="badge-bubble">
                                <?=$unread?></span><?php endif; ?></div><span>Account</span></a>
                <?php else: ?>
                    <a href="login.php" class="action-item"><i class="fa-regular fa-user">

                    </i><span>Sign In</span></a>
                    <a href="register.php" class="action-item" style="color:var(--primary);">
                        <i class="fa-solid fa-user-plus"></i><span>Sign Up</span></a>
                <?php endif; ?>
                <a href="wishlist.php" class="action-item">
                    <i class="fa-regular fa-heart"></i>
                    <span>Wishlist</span></a>
                <a href="cart.php" class="action-item">
                    <div style="position:relative;"><i class="fa-solid fa-cart-shopping"></i>
                    <?php $cart=isset($_SESSION['cart'])?array_sum($_SESSION['cart']):0;
                     if($cart>0):?><span class="badge-bubble"><?=$cart?>
                    </span><?php endif;
                      ?>
                      </div>
                      <span>Cart</span></a>
            </div>
        </div>
    </header>
    <div class="container main-layout">
        <?php display_flash(); ?>
