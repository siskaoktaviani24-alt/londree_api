<?php
require_once "../config/database.php";

/*
  File ini menerima:
  - Data biasa dari $_POST
  - Foto dari $_FILES["photo"]

  Folder file:
  LONDREE_API/uploads/laundry/

  Path yang disimpan ke database:
  uploads/laundry/nama_file.jpg
*/

$owner_id = intval($_POST["owner_id"] ?? 0);
$name = trim($_POST["name"] ?? "");
$description = trim($_POST["description"] ?? "");
$address = trim($_POST["address"] ?? "");
$latitude = $_POST["latitude"] ?? "";
$longitude = $_POST["longitude"] ?? "";
$is_open = intval($_POST["is_open"] ?? 1);

if ($owner_id <= 0 || $name == "" || $address == "" || $latitude == "" || $longitude == "") {
    res(false, "Data laundry belum lengkap");
}

$photoPath = null;

if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . "/../uploads/laundry/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $originalName = $_FILES["photo"]["name"];
    $tmpName = $_FILES["photo"]["tmp_name"];
    $fileSize = $_FILES["photo"]["size"];

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExt = ["jpg", "jpeg", "png", "webp"];

    if (!in_array($ext, $allowedExt)) {
        res(false, "Format foto harus JPG, JPEG, PNG, atau WEBP");
    }

    if ($fileSize > 2 * 1024 * 1024) {
        res(false, "Ukuran foto maksimal 2 MB");
    }

    $newFileName = "laundry_" . $owner_id . "_" . time() . "." . $ext;
    $targetPath = $uploadDir . $newFileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        res(false, "Gagal mengupload foto laundry");
    }

    $photoPath = "uploads/laundry/" . $newFileName;
}

$check = $pdo->prepare("SELECT id, photo FROM laundries WHERE owner_id = ?");
$check->execute([$owner_id]);
$old = $check->fetch(PDO::FETCH_ASSOC);

if ($old) {
    if ($photoPath != null) {
        $stmt = $pdo->prepare("
            UPDATE laundries 
            SET name = ?, 
                description = ?, 
                address = ?, 
                latitude = ?, 
                longitude = ?, 
                is_open = ?,
                photo = ?
            WHERE owner_id = ?
        ");

        $stmt->execute([
            $name,
            $description,
            $address,
            $latitude,
            $longitude,
            $is_open,
            $photoPath,
            $owner_id
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE laundries 
            SET name = ?, 
                description = ?, 
                address = ?, 
                latitude = ?, 
                longitude = ?, 
                is_open = ?
            WHERE owner_id = ?
        ");

        $stmt->execute([
            $name,
            $description,
            $address,
            $latitude,
            $longitude,
            $is_open,
            $owner_id
        ]);
    }

    res(true, "Profil laundry berhasil diperbarui");
}

$stmt = $pdo->prepare("
    INSERT INTO laundries 
    (owner_id, name, description, address, latitude, longitude, is_open, photo)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $owner_id,
    $name,
    $description,
    $address,
    $latitude,
    $longitude,
    $is_open,
    $photoPath
]);

res(true, "Profil laundry berhasil ditambahkan");
?>