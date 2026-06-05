<?php
require_once "../config/database.php";
require_once "../fcm_helper.php";

$data = inputJson();

$order_id = intval($data["order_id"] ?? 0);
$status = trim($data["status"] ?? "");

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

/*
    Ambil data pesanan + customer + token FCM customer
*/
$orderStmt = $pdo->prepare("
    SELECT 
        o.id,
        o.customer_id,
        o.laundry_id,
        o.service_id,
        o.status,
        l.name AS laundry_name,
        s.service_name,
        u.fcm_token AS customer_fcm_token
    FROM orders o
    LEFT JOIN laundries l ON o.laundry_id = l.id
    LEFT JOIN services s ON o.service_id = s.id
    LEFT JOIN users u ON o.customer_id = u.id
    WHERE o.id = ?
");
$orderStmt->execute([$order_id]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    res(false, "Pesanan tidak ditemukan");
}

$old_status = $order["status"] ?? "";

if ($old_status == "cancelled" || $old_status == "rejected" || $old_status == "delivered") {
    res(false, "Status pesanan sudah final dan tidak dapat diubah lagi");
}

/*
    Update status
*/
$stmt = $pdo->prepare("
    UPDATE orders 
    SET status = ?
    WHERE id = ?
");
$stmt->execute([$status, $order_id]);

function statusLabel($status) {
    switch ($status) {
        case "pending":
            return "Menunggu Konfirmasi";
        case "accepted":
            return "Diterima";
        case "picked_up":
            return "Sudah Dijemput";
        case "washing":
            return "Sedang Dicuci";
        case "ready":
            return "Siap Diambil";
        case "delivered":
            return "Selesai";
        case "rejected":
            return "Ditolak";
        case "cancelled":
            return "Dibatalkan";
        default:
            return $status;
    }
}

$laundry_name = $order["laundry_name"] ?? "Laundry";
$service_name = $order["service_name"] ?? "Layanan Laundry";
$customer_id = intval($order["customer_id"]);
$customer_fcm_token = $order["customer_fcm_token"] ?? "";

$status_text = statusLabel($status);

/*
    Kirim FCM ke customer
*/
$fcm_result = null;

if ($customer_fcm_token != "") {
    $fcm_result = sendFcmNotification(
        $customer_fcm_token,
        "Status Pesanan Diperbarui",
        "Pesanan $service_name di $laundry_name sekarang: $status_text",
        [
            "type" => "status_update",
            "order_id" => $order_id,
            "customer_id" => $customer_id,
            "status" => $status
        ]
    );
}

res(true, "Status berhasil diperbarui", [
    "order_id" => $order_id,
    "customer_id" => $customer_id,
    "status" => $status,
    "fcm_result" => $fcm_result
]);
?>