<?php
require_once "../config/database.php";

$data = inputJson();

$owner_id = intval($data["owner_id"] ?? 0);
$name = trim($data["name"] ?? "");
$description = trim($data["description"] ?? "");
$address = trim($data["address"] ?? "");
$latitude = $data["latitude"] ?? "";
$longitude = $data["longitude"] ?? "";
$is_open = intval($data["is_open"] ?? 1);

if ($owner_id <= 0 || $name == "" || $address == "" || $latitude == "" || $longitude == "") {
    res(false, "Data laundry belum lengkap");
}

$check = $pdo->prepare("SELECT id FROM laundries WHERE owner_id = ?");
$check->execute([$owner_id]);
$old = $check->fetch(PDO::FETCH_ASSOC);

if ($old) {
    $stmt = $pdo->prepare("
        UPDATE laundries 
        SET name=?, description=?, address=?, latitude=?, longitude=?, is_open=?
        WHERE owner_id=?
    ");
    $stmt->execute([$name, $description, $address, $latitude, $longitude, $is_open, $owner_id]);
    res(true, "Profil laundry berhasil diperbarui");
}

$stmt = $pdo->prepare("
    INSERT INTO laundries (owner_id, name, description, address, latitude, longitude, is_open)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([$owner_id, $name, $description, $address, $latitude, $longitude, $is_open]);

res(true, "Profil laundry berhasil ditambahkan");
?>