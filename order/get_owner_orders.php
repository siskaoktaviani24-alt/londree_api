<?php
require_once "../config/database.php";

$owner_id = intval($_GET["owner_id"] ?? 0);

if ($owner_id <= 0) {
    res(false, "owner_id wajib dikirim");
}

$stmt = $pdo->prepare("
    SELECT 
        o.*,
        l.name AS laundry_name,
        s.service_name,
        u.name AS customer_name,
        COALESCE(NULLIF(o.customer_phone, ''), u.phone) AS customer_phone
    FROM orders o
    JOIN laundries l ON l.id = o.laundry_id
    JOIN services s ON s.id = o.service_id
    JOIN users u ON u.id = o.customer_id
    WHERE l.owner_id = ?
    ORDER BY o.order_date DESC
");

$stmt->execute([$owner_id]);

res(true, "Data pesanan owner berhasil diambil", [
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>