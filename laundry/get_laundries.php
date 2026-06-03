<?php
require_once "../config/database.php";

$lat = $_GET["lat"] ?? null;
$lng = $_GET["lng"] ?? null;

if ($lat === null || $lng === null) {
    res(false, "lat dan lng wajib dikirim");
}

$stmt = $pdo->prepare("
    SELECT 
        l.*,
        u.name AS owner_name,
        COALESCE((SELECT AVG(rating) FROM reviews WHERE laundry_id = l.id), 0) AS rating,
        COALESCE((SELECT COUNT(*) FROM reviews WHERE laundry_id = l.id), 0) AS total_review,
        (
            6371 * ACOS(
                COS(RADIANS(?)) *
                COS(RADIANS(l.latitude)) *
                COS(RADIANS(l.longitude) - RADIANS(?)) +
                SIN(RADIANS(?)) *
                SIN(RADIANS(l.latitude))
            )
        ) AS distance
    FROM laundries l
    JOIN users u ON u.id = l.owner_id
    WHERE l.is_open = 1
    ORDER BY distance ASC
");

$stmt->execute([$lat, $lng, $lat]);

res(true, "Data laundry berhasil diambil", [
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>