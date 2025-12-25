<?php
// تنظیم گزارش خطا
error_reporting(E_ALL);
ini_set('display_errors', '1');

// تنظیم timeoutها (قابل تنظیم با env)
$connect_timeout = getenv('TLPROXY_CONNECT_TIMEOUT');
$connect_timeout = is_numeric($connect_timeout) ? (int) $connect_timeout : 10;

$timeout = getenv('TLPROXY_TIMEOUT');
$timeout = is_numeric($timeout) ? (int) $timeout : 60;

$time_limit = getenv('TLPROXY_TIME_LIMIT');
$time_limit = is_numeric($time_limit) ? (int) $time_limit : ($timeout + 5);
set_time_limit($time_limit);

$debug = filter_var(getenv('TLPROXY_DEBUG') ?: '', FILTER_VALIDATE_BOOLEAN);
$ipv4_only = filter_var(getenv('TLPROXY_IPV4_ONLY') ?: '', FILTER_VALIDATE_BOOLEAN);

// دریافت URI پس از دامنه، مثلا /bot123456:ABC/sendMessage
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$request_path = parse_url($request_uri, PHP_URL_PATH) ?? '';

// بررسی وجود bot token
if (!preg_match('#^/bot([0-9]+:[A-Za-z0-9_-]+)/([a-zA-Z0-9_]+)#', $request_path, $matches)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'description' => 'Invalid API format'], JSON_UNESCAPED_UNICODE);
    exit;
}

$bot_token = $matches[1];
$method = $matches[2];

// آدرس API اصلی تلگرام
$telegram_api = "https://api.telegram.org/bot{$bot_token}/{$method}";

// ارسال درخواست به تلگرام
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $telegram_api);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

if ($ipv4_only) {
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
}

$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($request_method === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);

    // اگر body به صورت JSON ارسال شده باشد، $_POST خالی است؛ پس raw body را فوروارد می‌کنیم.
    if (!empty($_POST)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
    } else {
        $raw_body = file_get_contents('php://input');
        $raw_body = $raw_body === false ? '' : $raw_body;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $raw_body);

        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        if ($content_type !== '') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: ' . $content_type]);
        }
    }
} else {
    if (!empty($_GET)) {
        $telegram_api .= '?' . http_build_query($_GET);
        curl_setopt($ch, CURLOPT_URL, $telegram_api);
    }
}

$response = curl_exec($ch);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);
$curl_info = curl_getinfo($ch);
curl_close($ch);

if ($response === false) {
    $status = ($curl_errno === CURLE_OPERATION_TIMEDOUT) ? 504 : 502;
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    $payload = [
        'ok' => false,
        'description' => 'Telegram upstream request failed',
    ];

    if ($debug) {
        $payload['curl_errno'] = $curl_errno;
        $payload['curl_error'] = $curl_error;
        $payload['timings'] = [
            'namelookup_time' => $curl_info['namelookup_time'] ?? null,
            'connect_time' => $curl_info['connect_time'] ?? null,
            'appconnect_time' => $curl_info['appconnect_time'] ?? null,
            'starttransfer_time' => $curl_info['starttransfer_time'] ?? null,
            'total_time' => $curl_info['total_time'] ?? null,
        ];
        $payload['upstream'] = [
            'http_code' => $curl_info['http_code'] ?? null,
            'primary_ip' => $curl_info['primary_ip'] ?? null,
        ];
        $payload['proxy'] = [
            'timeout' => $timeout,
            'connect_timeout' => $connect_timeout,
            'ipv4_only' => $ipv4_only,
        ];
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// بازگرداندن پاسخ اصلی به کلاینت
http_response_code((int) ($curl_info['http_code'] ?? 200));
header('Content-Type: application/json; charset=utf-8');
echo $response;
