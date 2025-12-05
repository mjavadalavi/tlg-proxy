<?php
// تنظیم زمان پاسخ‌دهی و گزارش خطا
set_time_limit(30);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// دریافت URI پس از دامنه، مثلا /bot123456:ABC/sendMessage
$request_uri = $_SERVER['REQUEST_URI'];

// بررسی وجود bot token
if (!preg_match('#^/bot([0-9]+:[A-Za-z0-9_-]+)/([a-zA-Z0-9_]+)#', $request_uri, $matches)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'description' => 'Invalid API format']);
    exit;
}

$bot_token = $matches[1];
$method = $matches[2];

// آدرس API اصلی تلگرام
$telegram_api = "https://api.telegram.org/bot{$bot_token}/{$method}";

// دریافت پارامترها (POST یا GET)
$params = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

// ارسال درخواست به تلگرام
$ch = curl_init($telegram_api);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
} else {
    $telegram_api .= '?' . http_build_query($params);
    curl_setopt($ch, CURLOPT_URL, $telegram_api);
}

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// بازگرداندن پاسخ اصلی به کلاینت
http_response_code($http_code);
header('Content-Type: application/json');
echo $response;
