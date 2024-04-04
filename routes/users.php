<?php
require_once __DIR__ . '/../mysql/conn.php';
require_once __DIR__ . '/../security/tokens.php';

verifyToken();

$sql = "SELECT count(*) as total FROM api_user";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $users = array();

    while ($row = $result->fetch_assoc()) {
        $users[] = array(
            'id' => $row['id'],
            'name' => $row['name'],
            'password' => $row['password']
        );
    }

    http_response_code(200);
    echo json_encode($users, JSON_PRETTY_PRINT);
} else {
    // Responder com erro se não
}