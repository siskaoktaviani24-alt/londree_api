<?php
require_once "../config/database.php";

$owner_id = intval($_GET["owner_id"] ?? 0);

if ($owner_id <= 0) {
    res(false, "owner_id wajib dikirim");
}

$stmt = $pdo->prepare("
    SELECT * FROM laundries 
    WHERE owner_id = ? 
    LIMIT 1
");
$stmt->execute([$owner_id]);
$laundry = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$laundry) {
    res(true, "Owner belum memiliki data laundry", [
        "laundry" => null,
        "services" => []
    ]);
}

$services = $pdo->prepare("
    SELECT * FROM services 
    WHERE laundry_id = ? 
    ORDER BY id DESC
");
$services->execute([$laundry["id"]]);

res(true, "Data laundry berhasil diambil", [
    "laundry" => $laundry,
    "services" => $services->fetchAll(PDO::FETCH_ASSOC)
]);
?>