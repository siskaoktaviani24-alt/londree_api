<?php
require_once "../config/database.php";

$data = inputJson();

$user_id = intval($data["user_id"] ?? 0);
$fcm_token = trim($data["fcm_token"] ?? "");

if ($user_id <= 0 || $fcm_token == "") {
    res(false, "Data token belum lengkap");
}

$stmt = $pdo->prepare("
    UPDATE users 
    SET fcm_token = ?
    WHERE id = ?
");

$stmt->execute([$fcm_token, $user_id]);

res(true, "FCM token berhasil disimpan");
?>