<?php
require_once __DIR__ . "/config/credentials.php";

function base64url_encode_fcm($data) {
    return rtrim(strtr(base64_encode($data), "+/", "-_"), "=");
}

function getFcmAccessToken() {
    if (!file_exists(SERVICE_ACCOUNT_PATH)) {
        return null;
    }

    $serviceAccount = json_decode(file_get_contents(SERVICE_ACCOUNT_PATH), true);

    if (!$serviceAccount) {
        return null;
    }

    if (!isset($serviceAccount["client_email"]) || !isset($serviceAccount["private_key"])) {
        return null;
    }

    $now = time();

    $header = base64url_encode_fcm(json_encode([
        "alg" => "RS256",
        "typ" => "JWT"
    ]));

    $claim = base64url_encode_fcm(json_encode([
        "iss" => $serviceAccount["client_email"],
        "scope" => "https://www.googleapis.com/auth/firebase.messaging",
        "aud" => "https://oauth2.googleapis.com/token",
        "iat" => $now,
        "exp" => $now + 3600
    ]));

    $signature = "";

    openssl_sign(
        "$header.$claim",
        $signature,
        openssl_pkey_get_private($serviceAccount["private_key"]),
        "SHA256"
    );

    $jwt = "$header.$claim." . base64url_encode_fcm($signature);

    $ch = curl_init("https://oauth2.googleapis.com/token");

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
            "assertion" => $jwt
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        return null;
    }

    $result = json_decode($response, true);

    return $result["access_token"] ?? null;
}

function sendFcmNotification($fcmToken, $title, $body, $data = []) {
    if (!$fcmToken || trim($fcmToken) == "") {
        return [
            "success" => false,
            "message" => "FCM token kosong"
        ];
    }

    if (!file_exists(SERVICE_ACCOUNT_PATH)) {
        return [
            "success" => false,
            "message" => "File service-account.json tidak ditemukan"
        ];
    }

    $serviceAccount = json_decode(file_get_contents(SERVICE_ACCOUNT_PATH), true);

    if (!$serviceAccount) {
        return [
            "success" => false,
            "message" => "Service account tidak valid"
        ];
    }

    $projectId = $serviceAccount["project_id"] ?? "";

    if ($projectId == "") {
        return [
            "success" => false,
            "message" => "Project ID Firebase tidak ditemukan"
        ];
    }

    $accessToken = getFcmAccessToken();

    if (!$accessToken) {
        return [
            "success" => false,
            "message" => "Gagal mendapatkan access token FCM"
        ];
    }

    $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

    $payload = [
        "message" => [
            "token" => $fcmToken,
            "notification" => [
                "title" => $title,
                "body" => $body
            ],
            "android" => [
                "notification" => [
                    "channel_id" => "high_importance_channel",
                    "default_sound" => true
                ]
            ],
            "data" => array_map("strval", $data)
        ]
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        return [
            "success" => false,
            "message" => $error
        ];
    }

    return json_decode($response, true);
}
?>