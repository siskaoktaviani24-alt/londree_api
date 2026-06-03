<?php
require_once "../config/database.php";

$laundry_id = intval($_GET["laundry_id"] ?? 0);

if ($laundry_id <= 0) {
    res(false, "laundry_id wajib dikirim");
}

$stmt = $pdo->prepare("
    SELECT 
        l.*,
        u.name AS owner_name,
        u.phone AS owner_phone,
        COALESCE((SELECT AVG(rating) FROM reviews WHERE laundry_id = l.id), 0) AS rating,
        COALESCE((SELECT COUNT(*) FROM reviews WHERE laundry_id = l.id), 0) AS total_review
    FROM laundries l
    JOIN users u ON u.id = l.owner_id
    WHERE l.id = ?
    LIMIT 1
");

$stmt->execute([$laundry_id]);
$laundry = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$laundry) {
    res(false, "Laundry tidak ditemukan");
}

$services = $pdo->prepare("SELECT * FROM services WHERE laundry_id = ? ORDER BY price_per_kg ASC");
$services->execute([$laundry_id]);

res(true, "Detail berhasil diambil", [
    "laundry" => $laundry,
    "services" => $services->fetchAll(PDO::FETCH_ASSOC)
]);
?>