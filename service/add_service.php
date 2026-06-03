<?php
require_once "../config/database.php";

$data = inputJson();

$owner_id = intval($data["owner_id"] ?? 0);
$service_name = trim($data["service_name"] ?? "");
$price_per_kg = $data["price_per_kg"] ?? "";
$estimated_time = trim($data["estimated_time"] ?? "");

if ($owner_id <= 0 || $service_name == "" || $price_per_kg == "") {
    res(false, "Data layanan belum lengkap");
}

$getLaundry = $pdo->prepare("SELECT id FROM laundries WHERE owner_id = ? LIMIT 1");
$getLaundry->execute([$owner_id]);
$laundry = $getLaundry->fetch(PDO::FETCH_ASSOC);

if (!$laundry) {
    res(false, "Buat profil laundry terlebih dahulu");
}

$stmt = $pdo->prepare("
    INSERT INTO services (laundry_id, service_name, price_per_kg, estimated_time)
    VALUES (?, ?, ?, ?)
");

$stmt->execute([
    $laundry["id"],
    $service_name,
    $price_per_kg,
    $estimated_time
]);

res(true, "Layanan berhasil ditambahkan");
?>