<?php
require_once "../config/database.php";

$data = inputJson();

$name = trim($data["name"] ?? "");
$email = trim($data["email"] ?? "");
$google_uid = trim($data["google_uid"] ?? "");
$role = trim($data["role"] ?? "customer");

if ($name == "" || $email == "" || $google_uid == "") {
    res(false, "Data Google tidak lengkap");
}

if (!in_array($role, ["owner", "customer"])) {
    res(false, "Role tidak valid");
}

// Cek apakah email Google sudah ada di database
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Kalau email sudah ada, update google_uid dan auth_provider
    $update = $pdo->prepare("
        UPDATE users 
        SET google_uid = ?, auth_provider = 'google'
        WHERE id = ?
    ");

    $update->execute([$google_uid, $user["id"]]);

    $getUser = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $getUser->execute([$user["id"]]);
    $updatedUser = $getUser->fetch(PDO::FETCH_ASSOC);

    unset($updatedUser["password"]);

    res(true, "Login Google berhasil", [
        "user" => $updatedUser
    ]);
}

// Kalau email belum ada, buat user baru di MySQL
$dummyPassword = password_hash($google_uid, PASSWORD_DEFAULT);

$insert = $pdo->prepare("
    INSERT INTO users (name, email, phone, password, role, google_uid, auth_provider)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$insert->execute([
    $name,
    $email,
    "",
    $dummyPassword,
    $role,
    $google_uid,
    "google"
]);

$newUserId = $pdo->lastInsertId();

$getUser = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$getUser->execute([$newUserId]);
$newUser = $getUser->fetch(PDO::FETCH_ASSOC);

unset($newUser["password"]);

res(true, "Login Google berhasil", [
    "user" => $newUser
]);
?>