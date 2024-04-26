<?php

$maxRequestsPerSecond = 10;
$expiryTime = 60;
$ip = $_SERVER['REMOTE_ADDR'];
$key = 'rate_limit:' . $ip;

if (!isset($_SESSION[$key])) {
    $_SESSION[$key] = [
        'count' => 1,
        'expiry' => time() + $expiryTime
    ];
} else {
    if ($_SESSION[$key]['expiry'] < time()) {
        $_SESSION[$key] = [
            'count' => 1,
            'expiry' => time() + $expiryTime
        ];
    } else {
        if ($_SESSION[$key]['count'] >= $maxRequestsPerSecond) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests']);
            exit;
        } else {
            $_SESSION[$key]['count']++;
        }
    }
}