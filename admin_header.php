<?php /* admin_header */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" 
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background:#e5e7eb;">
    <nav style="background:#1f2937; padding:15px 30px; 
    display:flex; justify-content:space-between; align-items:center;">
        <img src ="images/logo.jpg" class="logo-img">
        <h2 style="color:#fff;">Konza Admin</h2>
        <div style="display:flex; gap:15px;">
            <a href="index.php" style="color:#fff;">View Store</a>
            <a href="logout.php" style="color:#ea580c;">Logout</a>
        </div>
    </nav>
    <div class="container" style="margin-top:30px;">
        <?php display_flash(); ?>