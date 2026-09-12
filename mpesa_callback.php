<?php
include 'config.php';
include 'mpesa_config.php';

// Safaricom sends JSON
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Log for debugging (create logs folder)
@file_put_contents(__DIR__.'/logs/mpesa_'.date('Y-m-d').'.log', date('Y-m-d H:i:s')." $raw\n", FILE_APPEND);

if (!isset($data['Body']['stkCallback'])) {
    http_response_code(200);
    echo json_encode(["ResultCode" => 0, "ResultDesc" => "Success"]);
    exit;
}

$callback = $data['Body']['stkCallback'];
$checkoutId = $callback['CheckoutRequestID'];
$resultCode = $callback['ResultCode'];

// 1. Find pending payment
$stmt = $pdo->prepare("SELECT * FROM pending_payments WHERE checkout_request_id = ?");
$stmt->execute([$checkoutId]);
$pending = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pending) {
    http_response_code(200);
    echo json_encode(["ResultCode" => 0, "ResultDesc" => "Success"]);
    exit;
}

// 2. If payment failed, just delete pending and stop
if ($resultCode != 0) {
    $pdo->prepare("DELETE FROM pending_payments WHERE id = ?")->execute([$pending['id']]);
    http_response_code(200);
    echo json_encode(["ResultCode" => 0, "ResultDesc" => "Success"]);
    exit;
}

// 3. Payment Successful - extract data
$items = $callback['CallbackMetadata']['Item'];
$amountPaid = 0;
$receipt = '';
foreach ($items as $it) {
    if ($it['Name'] == 'Amount') $amountPaid = $it['Value'];
    if ($it['Name'] == 'MpesaReceiptNumber') $receipt = $it['Value'];
}

// 4. Verify amount - prevent 1 KSH hack
if ((float)$amountPaid < (float)$pending['amount'] - 1) { // allow 1 KSH tolerance
    $pdo->prepare("DELETE FROM pending_payments WHERE id = ?")->execute([$pending['id']]);
    http_response_code(200);
    exit;
}

// 5. Prevent duplicate order (Safaricom can send callback twice)
$stmt = $pdo->prepare("SELECT id FROM orders WHERE mpesa_receipt = ?");
$stmt->execute([$receipt]);
if ($stmt->fetch()) {
    $pdo->prepare("DELETE FROM pending_payments WHERE id = ?")->execute([$pending['id']]);
    http_response_code(200);
    echo json_encode(["ResultCode" => 0, "ResultDesc" => "Success"]);
    exit;
}

$cart = json_decode($pending['cart_data'], true);
$user_id = $pending['user_id'];

$pdo->beginTransaction();
try {
    // 6. Create Order
    $eta = date('Y-m-d H:i:s', strtotime('+2 hours'));
    $sql = "INSERT INTO orders (user_id, total_amount, status, delivery_address, delivery_phone, delivery_eta, mpesa_receipt) VALUES (?, ?, 'paid', ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $pending['amount'], $pending['delivery_address'], $pending['delivery_phone'], $eta, $receipt]);
    $order_id = $pdo->lastInsertId();

    // 7. Deduct stock and save items
    foreach ($cart as $prod_id => $qty) {
        $stmt = $pdo->prepare("SELECT price, stock FROM products WHERE id = ? FOR UPDATE");
        $stmt->execute([$prod_id]);
        $product = $stmt->fetch();
        if (!$product) continue;
        
        // Prevent negative stock
        if ($product['stock'] < $qty) {
            throw new Exception("Insufficient stock for product $prod_id");
        }

        $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$qty, $prod_id]);
        $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)")->execute([$order_id, $prod_id, $qty, $product['price']]);
    }

    // 8. Notification
    $title = "Order #$order_id confirmed";
    $msg = "Payment of KSh {$pending['amount']} received. Receipt: $receipt. Delivery to: {$pending['delivery_address']}.";
    $pdo->prepare("INSERT INTO notifications (user_id, order_id, type, title, message) VALUES (?, ?, 'order', ?, ?)")->execute([$user_id, $order_id, $title, $msg]);

    // 9. Cleanup pending
    $pdo->prepare("DELETE FROM pending_payments WHERE id = ?")->execute([$pending['id']]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    @file_put_contents(__DIR__.'/logs/mpesa_error.log', date('Y-m-d H:i:s')." Error $checkoutId: ".$e->getMessage()."\n", FILE_APPEND);
}

http_response_code(200);
echo json_encode(["ResultCode" => 0, "ResultDesc" => "Success"]);
