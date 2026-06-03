<?php
require_once "../config/database.php";

$data = inputJson();

$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";

if ($email == "" || $password == "") {
    res(false, "Email dan password wajib diisi");
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user["password"])) {
    res(false, "Email atau password salah");
}

unset($user["password"]);

res(true, "Login berhasil", [
    "user" => $user
]);
?>