<?php
require_once __DIR__ . '/../mysql/conn.php';
require_once __DIR__ . '/../security/token.php';

function addUserWithToken($name, $password) {
    global $conn;

    $sql = "INSERT INTO api_user (name, password) VALUES ('$name', '$password')";
    if ($conn->query($sql) === TRUE) {
        $user_id = $conn->insert_id;

        $token = generateToken($user_id);

        $sql_token = "INSERT INTO api_tokens (user_id, token) VALUES ($user_id, '$token')";
        $conn->query($sql_token);

        return array("user_id" => $user_id, "token" => $token);
    } else {
        return array("error" => "Erro ao adicionar usuário: " . $conn->error);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['name']) && isset($_POST['password'])) {
        $name = $_POST['name'];
        $password = $_POST['password'];

        $result = addUserWithToken($name, $password);

        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido."));
}

$conn->close();
?>
