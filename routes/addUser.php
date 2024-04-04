<?php
require_once __DIR__ . '/../mysql/conn.php';
require_once __DIR__ . '/../security/tokens.php';

function userExists($name) {
    global $conn;

    $sql = "SELECT COUNT(*) AS total FROM api_user WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] > 0;
}

function addUserWithToken($name, $password) {
    global $conn;

    if (userExists($name)) {
        http_response_code(400);
        return array("error" => "Usuario já existe.");
    }

    $sql = "INSERT INTO api_user (name, password) VALUES ('$name', '$password')";
    if ($conn->query($sql) === TRUE) {
        $user_id = $conn->insert_id;

        $token = generateToken($user_id);

        $sql_token = "INSERT INTO api_tokens (user_id, token) VALUES ($user_id, '$token')";
        $conn->query($sql_token);

        return array("user_id" => $user_id, "token" => $token);
    } else {
        http_response_code(500);
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
        echo json_encode(array("message" => "Dados incompletos."), JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
