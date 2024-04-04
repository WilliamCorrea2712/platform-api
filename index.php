<?php
    $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoyMiwiZXhwIjoxNzQzNzkxMzIwfQ.1hDnJ-pfMux1vDN2yMY50QqmbNkmL1Ur0L3uX4txM6Y";

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