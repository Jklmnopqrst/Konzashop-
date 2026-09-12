<?php 

include 'config.php'; 
include 'mpesa_config.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$action = $_GET['action'] ?? 'checkout';

// 1. INITIATE STK PUSH (Form Submission)

if ($action === 'initiate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['cart'])) {
        header("Location: index.php");
        exit;
    }

    $phone = trim($_POST['phone']);
    if (str_starts_with($phone, '0')) $phone = '254' . substr($phone, 1);
    if (str_starts_with($phone, '+')) $phone = substr($phone, 1);
    
    $addr = trim($_POST['delivery_address']);
    $dphone = trim($_POST['delivery_phone']);
    $total = 0;
foreach ($_SESSION['cart'] as $id => $qty) {
    $stmt = $pdo->prepare("SELECT price FROM products WHERE id=?");
    $stmt->execute([$id]);
    $price = $stmt->fetchColumn();
    if($price){
        $total += $price * $qty;
    }
}
$amount = $total; // Now amount is from YOUR database, not hacker

// Stop if amount is 0
if($amount <= 0){
    set_flash('error', "Invalid cart amount");
    header("Location: cart.php");
    exit;
}


    // Get Access Token
    $url = (MPESA_ENV == 'sandbox') 
        ? 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
        : 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET)]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $result = json_decode(curl_exec($curl));
    curl_close($curl);

    if (!isset($result->access_token)) {
        set_flash('error', "Failed to connect to M-Pesa.");
        header("Location: payment.php");
        exit;
    }

    // Initiate Push
    $timestamp = date('YmdHis');
    $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

    $stk_url = (MPESA_ENV == 'sandbox')
        ? 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
        : 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

    $data = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => MPESA_SHORTCODE,
        'PhoneNumber' => $phone,
        'CallBackURL' => MPESA_CALLBACK_URL,
        'AccountReference' => 'KonzaOrder',
        'TransactionDesc' => 'Payment'
    ];

    $curl = curl_init($stk_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $result->access_token,
        'Content-Type: application/json'
    ]);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($curl));
    curl_close($curl);

    if (isset($response->CheckoutRequestID)) {
        // Save to pending table
        $sql = "INSERT INTO pending_payments 
                (user_id, checkout_request_id, delivery_address, 
                delivery_phone, amount, cart_data) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $_SESSION['user_id'], $response->CheckoutRequestID, 
            $addr, $dphone, $amount, json_encode($_SESSION['cart'])
        ]);
        
        header("Location: payment.php?action=wait");
        exit;
    } else {
        set_flash('error', "M-Pesa request failed. Try again.");
        header("Location: payment.php");
        exit;
    }
}

//2. WAIT SCREEN
if ($action === 'wait') {
    include 'header.php'; 
    ?>
    <div class="auth-container" style="text-align:center; padding:50px 20px;">
        <i class="fa-solid fa-spinner fa-spin" style="font-size:3rem; color:var(--primary);"></i>
        <h2 style="margin-top:20px;">Waiting for Payment</h2>
        <p style="color:var(--text-muted); margin-top:10px;">
            An STK push has been sent to your phone.<br>
            Please enter your M-Pesa PIN to complete the order.
        </p>
        <a href="dashboard.php" class="btn-orange" style="display:inline-block; margin-top:30px;">
            Go to Dashboard
        </a>
    </div>
    <?php 
    include 'footer.php'; 
    exit;
}

// 3. CHECKOUT FORM (Default View)
if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit;
}

$total = 0;
foreach ($_SESSION['cart'] as $id => $qty) {
    $stmt = $pdo->prepare("SELECT price FROM products WHERE id=?");
    $stmt->execute([$id]);
    $total += $stmt->fetchColumn() * $qty;
}

include 'header.php'; 
?>
<div class="auth-container">
    <h2>Checkout</h2>
    <form action="payment.php?action=initiate" method="POST">
        <label>Delivery Address</label>
        <input type="text" name="delivery_address" required>
        
        <label>Delivery Phone</label>
        <input type="text" name="delivery_phone" required>
        
        <h3 style="margin:20px 0; text-align:center;">
            Total: KSh <?= number_format($total) ?>
        </h3>
        
        <label>M-Pesa Phone Number</label>
        <input type="text" name="phone" placeholder="2547XXXXXXXX" required>
        
        <button type="submit" class="btn-orange full-width">
            Pay Now
        </button>
    </form>
</div>
<?php include 'footer.php'; ?>