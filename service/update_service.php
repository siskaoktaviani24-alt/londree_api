<?php
require_once "../config/database.php";

$data = inputJson();

$owner_id = intval($data["owner_id"] ?? 0);
$service_id = intval($data["service_id"] ?? 0);
$service_name = trim($data["service_name"] ?? "");
$price_per_kg = $data["price_per_kg"] ?? "";
$estimated_time = trim($data["estimated_time"] ?? "");

if ($owner_id <= 0 || $service_id <= 0 || $service_name == "" || $price_per_kg == "") {
    res(false, "Data layanan belum lengkap");
}

$check = $pdo->prepare("
    SELECT s.id
    FROM services s
    JOIN laundries l ON l.id = s.laundry_id
    WHERE s.id = ? AND l.owner_id = ?
    LIMIT 1
");
$check->execute([$service_id, $owner_id]);

if (!$check->fetch()) {
    res(false, "Layanan tidak ditemukan atau bukan milik owner ini");
}

$stmt = $pdo->prepare("
    UPDATE services 
    SET service_name = ?, price_per_kg = ?, estimated_time = ?
    WHERE id = ?
");

$stmt->execute([
    $service_name,
    $price_per_kg,
    $estimated_time,
    $service_id
]);

res(true, "Layanan berhasil diperbarui");
?>