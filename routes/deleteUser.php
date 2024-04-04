<?php
require_once __DIR__ . '/../mysql/conn.php';
require_once __DIR__ . '/../security/tokens.php';

$user_id = verifyToken()->user_id;

function deleteUser($user_id) {
    global $conn;

    $sql_delete_tokens = "DELETE FROM api_tokens WHERE user_id = ?";
    $stmt_delete_tokens = $conn->prepare($sql_delete_tokens);
    $stmt_delete_tokens->bind_param("i", $user_id);

    if (!$stmt_delete_tokens->execute()) {
        http_response_code(500);
        return array("error" => "Erro ao excluir os tokens do usuário da tabela api_tokens: " . $conn->error);
    }

    $sql_delete_user = "DELETE FROM api_user WHERE id = ?";
    $stmt_delete_user = $conn->prepare($sql_delete_user);
    $stmt_delete_user->bind_param("i", $user_id);

    if (!$stmt_delete_user->execute()) {
        http_response_code(500);
        return array("error" => "Erro ao excluir o usuário da tabela api_user: " . $conn->error);
    }

    http_response_code(200);
    return array("message" => "Usuário excluído com sucesso.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['id'])) {
        $delete_user_id = $_POST['id'];

        if ((int)$delete_user_id != $user_id) {
            http_response_code(401);
            echo json_encode(array("message" => "Você não tem permissão para excluir este usuário."), JSON_UNESCAPED_UNICODE);
            exit;
        }

        $result = deleteUser($delete_user_id);

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "ID do usuário não fornecido."), JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
