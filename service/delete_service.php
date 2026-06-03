<?php
require_once "../config/database.php";

$data = inputJson();

$owner_id = intval($data["owner_id"] ?? 0);
$service_id = intval($data["service_id"] ?? 0);

if ($owner_id <= 0 || $service_id <= 0) {
    res(false, "Data hapus layanan belum lengkap");
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

$checkOrder = $pdo->prepare("
    SELECT COUNT(*) AS total 
    FROM orders 
    WHERE service_id = ?
");
$checkOrder->execute([$service_id]);
$orderCount = $checkOrder->fetch(PDO::FETCH_ASSOC);

if (intval($orderCount["total"]) > 0) {
    res(false, "Layanan tidak dapat dihapus karena sudah pernah digunakan dalam pesanan");
}

$stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
$stmt->execute([$service_id]);

res(true, "Layanan berhasil dihapus");
?>