<?php
require_once "../config/database.php";

$data = inputJson();

$customer_id = intval($data["customer_id"] ?? 0);
$laundry_id = intval($data["laundry_id"] ?? 0);
$service_id = intval($data["service_id"] ?? 0);
$weight = floatval($data["weight"] ?? 0);
$pickup_address = trim($data["pickup_address"] ?? "");
$note = trim($data["note"] ?? "");

if ($customer_id <= 0 || $laundry_id <= 0 || $service_id <= 0 || $weight <= 0 || $pickup_address == "") {
    res(false, "Data pesanan belum lengkap");
}

$laundryStmt = $pdo->prepare("SELECT owner_id FROM laundries WHERE id = ?");
$laundryStmt->execute([$laundry_id]);
$laundry = $laundryStmt->fetch(PDO::FETCH_ASSOC);

if (!$laundry) {
    res(false, "Laundry tidak ditemukan");
}

$owner_id = intval($laundry["owner_id"]);

$service = $pdo->prepare("SELECT price_per_kg FROM services WHERE id = ? AND laundry_id = ?");
$service->execute([$service_id, $laundry_id]);
$row = $service->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    res(false, "Layanan tidak ditemukan");
}

$total_price = $weight * floatval($row["price_per_kg"]);

$stmt = $pdo->prepare("
    INSERT INTO orders 
    (customer_id, laundry_id, service_id, weight, total_price, pickup_address, note)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $customer_id,
    $laundry_id,
    $service_id,
    $weight,
    $total_price,
    $pickup_address,
    $note
]);

$order_id = intval($pdo->lastInsertId());

res(true, "Pesanan berhasil dibuat", [
    "order_id" => $order_id,
    "owner_id" => $owner_id,
    "laundry_id" => $laundry_id,
    "customer_id" => $customer_id,
    "total_price" => $total_price
]);
?>