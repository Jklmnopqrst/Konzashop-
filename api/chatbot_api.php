<?php
if (file_exists(__DIR__ . '/../gemini_config.php')){
  include __DIR__ . '/../gemini_config.php';
}
header('Content-Type: application/json');
$cfg = __DIR__ . '/../config.php';
if(!file_exists($cfg)) $cfg = __DIR__ . '/../includes/config.php';
if(file_exists($cfg)) include $cfg;

$msg = $_POST['message'] ?? '';
$msgL = strtolower(trim($msg));

if($msgL === ''){
  echo json_encode(['reply'=>'Hi! 👋 Ask me "phones under 15000" or "fashion"','products'=>[]]);
  exit;
}

try{
  // Get real column names from your table
  $cols = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
  
  $imgCol = 'image';
  if(in_array('image_url',$cols)) $imgCol = 'image_url';
  elseif(in_array('product_image',$cols)) $imgCol = 'product_image';
  elseif(in_array('photo',$cols)) $imgCol = 'photo';
  elseif(in_array('img',$cols)) $imgCol = 'img';
  
  $sql = "SELECT id, name, price, category, $imgCol as img FROM products WHERE 1";
  $params = [];

  if(preg_match('/(\d+)/',$msgL,$m)){
    $price = intval($m[1]);
    if(strpos($msgL,'k')!==false || $price < 500) $price *= 1000;
    if($price > 500){
      $sql .= " AND price <= ?";
      $params[] = $price;
    }
  }
  
  if(strpos($msgL,'phone')!==false){
    $sql .= " AND (name LIKE '%phone%' OR category LIKE '%phone%' OR name LIKE '%samsung%' OR name LIKE '%tecno%' OR name LIKE '%infinix%')";
  } elseif(preg_match('/fashion|shirt|dress|shoe|bag/',$msgL)){
    $sql .= " AND category LIKE '%fashion%'";
  } elseif(strlen($msgL) > 2){
    $sql .= " AND (name LIKE ? OR category LIKE ?)";
    $params[] = "%$msgL%";
    $params[] = "%$msgL%";
  }

  $sql .= " ORDER BY price ASC LIMIT 6";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach($rows as &$r){
    $im = $r['img'] ?? '';
    if(empty($im)) $im = 'uploads/no-image.png';
    if(!str_starts_with($im,'http') && !str_starts_with($im,'uploads/') && !str_starts_with($im,'assets/')){
      $im = 'uploads/'.$im;
    }
    $r['img'] = $im;
  }

  echo json_encode(['reply'=>"Found ".count($rows)." for '$msg':", 'products'=>$rows]);

}catch(Exception $e){
  echo json_encode(['reply'=>'Error: '.$e->getMessage(), 'products'=>[]]);
}
