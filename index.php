<?php
$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxMiwiZXhwaXJhdGlvbiI6MTc0Mzc4MjgyNH0.PsPiAZ6xofS7XDjgxzcmfu60c1bJyhDs0Mg78XUqbWg";

$token_parts = explode(".", $token);

$payload = json_decode(base64_decode($token_parts[1]));

$expiration_timestamp = $payload->expiration;

$current_timestamp = time();
if ($current_timestamp < $expiration_timestamp) {
    echo "O token ainda está dentro do período de validade.";
} else {
    echo "O token expirou.";
}
?>