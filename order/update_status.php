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
    Ambil data pesanan + customer + owner + token FCM
*/
$orderStmt = $pdo->prepare("
    SELECT 
        o.id,
        o.customer_id,
        o.laundry_id,
        o.service_id,
        o.status,
        l.name AS laundry_name,
        l.owner_id,
        s.service_name,
        customer.name AS customer_name,
        customer.fcm_token AS customer_fcm_token,
        owner.fcm_token AS owner_fcm_token
    FROM orders o
    LEFT JOIN laundries l ON o.laundry_id = l.id
    LEFT JOIN services s ON o.service_id = s.id
    LEFT JOIN users customer ON o.customer_id = customer.id
    LEFT JOIN users owner ON l.owner_id = owner.id
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
$customer_name = $order["customer_name"] ?? "Customer";

$customer_id = intval($order["customer_id"]);
$owner_id = intval($order["owner_id"]);

$customer_fcm_token = $order["customer_fcm_token"] ?? "";
$owner_fcm_token = $order["owner_fcm_token"] ?? "";

$status_text = statusLabel($status);

$fcm_result = null;

/*
    Jika customer membatalkan pesanan:
    Notifikasi dikirim ke owner.
*/
if ($status == "cancelled") {
    if ($owner_fcm_token != "") {
        $fcm_result = sendFcmNotification(
            $owner_fcm_token,
            "Pesanan Dibatalkan",
            "$customer_name membatalkan pesanan $service_name di $laundry_name",
            [
                "type" => "order_cancelled_by_customer",
                "order_id" => $order_id,
                "owner_id" => $owner_id,
                "customer_id" => $customer_id,
                "status" => $status
            ]
        );
    }

    res(true, "Pesanan berhasil dibatalkan", [
        "order_id" => $order_id,
        "owner_id" => $owner_id,
        "customer_id" => $customer_id,
        "status" => $status,
        "fcm_result" => $fcm_result
    ]);
}

/*
    Jika owner mengubah status, termasuk rejected:
    Notifikasi dikirim ke customer.
*/
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
    "owner_id" => $owner_id,
    "customer_id" => $customer_id,
    "status" => $status,
    "fcm_result" => $fcm_result
]);
?>