<?php
require_once "../config/database.php";

$data = inputJson();

$name = trim($data["name"] ?? "");
$email = trim($data["email"] ?? "");
$phone = trim($data["phone"] ?? "");
$password = $data["password"] ?? "";
$role = $data["role"] ?? "";

if ($name == "" || $email == "" || $password == "" || $role == "") {
    res(false, "Data belum lengkap");
}

if (!in_array($role, ["owner", "customer"])) {
    res(false, "Role tidak valid");
}

$check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$email]);

if ($check->fetch()) {
    res(false, "Email sudah digunakan");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO users (name, email, phone, password, role)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([$name, $email, $phone, $hash, $role]);

res(true, "Registrasi berhasil");
?>