<?php
require_once "../config/database.php";
require_once "../fcm_helper.php";

$data = inputJson();

$customer_id = intval($data["customer_id"] ?? 0);
$laundry_id = intval($data["laundry_id"] ?? 0);
$service_id = intval($data["service_id"] ?? 0);
$weight = floatval($data["weight"] ?? 0);
$customer_phone = trim($data["customer_phone"] ?? "");
$pickup_address = trim($data["pickup_address"] ?? "");
$note = trim($data["note"] ?? "");

if (
    $customer_id <= 0 || 
    $laundry_id <= 0 || 
    $service_id <= 0 || 
    $weight <= 0 || 
    $customer_phone == "" ||
    $pickup_address == ""
) {
    res(false, "Data pesanan belum lengkap");
}

/*
    Ambil data laundry + owner + token FCM owner
*/
$laundryStmt = $pdo->prepare("
    SELECT 
        l.id,
        l.name AS laundry_name,
        l.owner_id,
        u.fcm_token AS owner_fcm_token
    FROM laundries l
    LEFT JOIN users u ON l.owner_id = u.id
    WHERE l.id = ?
");
$laundryStmt->execute([$laundry_id]);
$laundry = $laundryStmt->fetch(PDO::FETCH_ASSOC);

if (!$laundry) {
    res(false, "Laundry tidak ditemukan");
}

$owner_id = intval($laundry["owner_id"]);
$laundry_name = $laundry["laundry_name"] ?? "Laundry";
$owner_fcm_token = $laundry["owner_fcm_token"] ?? "";

/*
    Ambil nama customer
*/
$customerStmt = $pdo->prepare("
    SELECT name 
    FROM users 
    WHERE id = ?
");
$customerStmt->execute([$customer_id]);
$customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

$customer_name = $customer["name"] ?? "Customer";

/*
    Ambil data layanan
*/
$service = $pdo->prepare("
    SELECT service_name, price_per_kg 
    FROM services 
    WHERE id = ? AND laundry_id = ?
");
$service->execute([$service_id, $laundry_id]);
$row = $service->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    res(false, "Layanan tidak ditemukan");
}

$service_name = $row["service_name"] ?? "Layanan Laundry";
$total_price = $weight * floatval($row["price_per_kg"]);

/*
    Simpan pesanan ke database
*/
$stmt = $pdo->prepare("
    INSERT INTO orders 
    (customer_id, customer_phone, laundry_id, service_id, weight, total_price, pickup_address, note)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $customer_id,
    $customer_phone,
    $laundry_id,
    $service_id,
    $weight,
    $total_price,
    $pickup_address,
    $note
]);

$order_id = intval($pdo->lastInsertId());

/*
    Kirim FCM ke owner
*/
$fcm_result = null;

if ($owner_fcm_token != "") {
    $fcm_result = sendFcmNotification(
        $owner_fcm_token,
        "Pesanan Baru Masuk",
        "$customer_name membuat pesanan $service_name di $laundry_name",
        [
            "type" => "new_order",
            "order_id" => $order_id,
            "owner_id" => $owner_id,
            "customer_id" => $customer_id,
            "laundry_id" => $laundry_id
        ]
    );
}

res(true, "Pesanan berhasil dibuat", [
    "order_id" => $order_id,
    "owner_id" => $owner_id,
    "laundry_id" => $laundry_id,
    "customer_id" => $customer_id,
    "customer_phone" => $customer_phone,
    "total_price" => $total_price,
    "fcm_result" => $fcm_result
]);
?>