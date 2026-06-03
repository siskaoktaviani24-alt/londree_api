<?php
require_once "../config/database.php";

$customer_id = intval($_GET["customer_id"] ?? 0);

if ($customer_id <= 0) {
    res(false, "customer_id wajib dikirim");
}

$stmt = $pdo->prepare("
    SELECT 
        o.*,
        l.name AS laundry_name,
        s.service_name,
        u.name AS customer_name
    FROM orders o
    JOIN laundries l ON l.id = o.laundry_id
    JOIN services s ON s.id = o.service_id
    JOIN users u ON u.id = o.customer_id
    WHERE o.customer_id = ?
    ORDER BY o.order_date DESC
");

$stmt->execute([$customer_id]);

res(true, "Data pesanan berhasil diambil", [
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>