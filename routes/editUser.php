<?php
require_once __DIR__ . '/../mysql/conn.php';
require_once __DIR__ . '/../security/tokens.php';

verifyToken();

if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    if (isset($_POST['id']) && isset($_POST['name']) && isset($_POST['password'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $password = $_POST['password'];

        $sql = "UPDATE api_user SET name='$name', password='$password' WHERE id='$id'";

        if ($conn->query($sql) === TRUE) {
            http_response_code(200);
            echo json_encode(array("message" => "Dados do usuário atualizados com sucesso."), JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Erro ao atualizar os dados do usuário: " . $conn->error), JSON_UNESCAPED_UNICODE);
        }
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
