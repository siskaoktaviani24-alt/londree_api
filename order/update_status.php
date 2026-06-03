<?php
require_once "../config/database.php";

$data = inputJson();

$order_id = intval($data["order_id"] ?? 0);
$status = $data["status"] ?? "";

$allowed = [
    "pending",
    "accepted",
    "picked_up",
    "washing",
    "ready",
    "delivered",
    "rejected",
    "cancelled"
];

if ($order_id <= 0 || !in_array($status, $allowed)) {
    res(false, "Status tidak valid");
}

$stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->execute([$status, $order_id]);

res(true, "Status berhasil diperbarui");
?>