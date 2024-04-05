<?php
require_once __DIR__ . '/../../mysql/conn.php';
require_once __DIR__ . '/../../global/helpers.php';
require_once __DIR__ . '/../../security/token.php';
require_once(__DIR__ . '/../../config.php');

function addUserWithToken($name, $password, $email) {
    global $conn;

    if (userExists($name, $email)) {
        http_response_code(400);
        return array("error" => "Usuário já existe.");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $created_at = date('Y-m-d H:i:s');

    $sql = "INSERT INTO " . PREFIX . "user (name, password, email, created_at) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $hashed_password, $email, $created_at);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        $token = generateToken($user_id);

        $sql_token = "INSERT INTO " . PREFIX . "tokens (user_id, token) VALUES (?, ?)";
        $stmt_token = $conn->prepare($sql_token);
        $stmt_token->bind_param("is", $user_id, $token);
        $stmt_token->execute();
        $conn->close();
        return array("user_id" => $user_id, "token" => $token);
    } else {
        http_response_code(500);
        return array("error" => "Erro ao adicionar usuário: " . $stmt->error);
    }
}

function getAllUsers() {
    global $conn;

    $sql = "SELECT id, name, email, password FROM " . PREFIX . "user";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $users = array();

        while ($row = $result->fetch_assoc()) {
            $users[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'email' => $row['email'],
            );
        }

        $conn->close();
        http_response_code(200);
        echo json_encode($users, JSON_PRETTY_PRINT);
    } else {
        return array();
    }
}

function updateUser($id, $name, $email, $password = null) {
    global $conn;

    $updated_at = date('Y-m-d H:i:s');

    if ($password !== null) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "UPDATE " . PREFIX . "user SET name=?, email=?, password=?, updated_at=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $name, $email, $hashed_password, $updated_at, $id);
    } else {
        $sql = "UPDATE " . PREFIX . "user SET name=?, email=?, updated_at=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $email, $updated_at, $id);
    }

    if ($stmt->execute()) {
        $conn->close();
        http_response_code(200);
        return array("message" => "Dados do usuário atualizados com sucesso.");
    } else {
        http_response_code(500);
        return array("message" => "Erro ao atualizar os dados do usuário: " . $conn->error);
    }
}

function delUser($user_id) {
    global $conn;

    $sql_delete_tokens = "DELETE FROM " . PREFIX . "tokens WHERE user_id = ?";
    $stmt_delete_tokens = $conn->prepare($sql_delete_tokens);
    $stmt_delete_tokens->bind_param("i", $user_id);

    if (!$stmt_delete_tokens->execute()) {
        http_response_code(500);
        return array("error" => "Erro ao excluir os tokens do usuário da tabela " . PREFIX . "tokens: " . $conn->error);
    }

    $sql_delete_user = "DELETE FROM " . PREFIX . "user WHERE id = ?";
    $stmt_delete_user = $conn->prepare($sql_delete_user);
    $stmt_delete_user->bind_param("i", $user_id);

    if (!$stmt_delete_user->execute()) {
        http_response_code(500);
        return array("error" => "Erro ao excluir o usuário da tabela " . PREFIX . "user: " . $conn->error);
    }

    $conn->close();
    http_response_code(200);
    return array("message" => "Usuário excluído com sucesso.");
}

function loginUser($email, $password) {
    global $conn;

    $sql = "SELECT id, name, password FROM " . PREFIX . "user WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $hashed_password = $user['password'];

        if (password_verify($password, $hashed_password)) {
            $user_id = $user['id'];
            $token = generateToken($user_id);
            return array("user_id" => $user_id, "token" => $token);
        } else {
            http_response_code(401);
            return array("error" => "Senha incorreta.");
        }
    } else {
        http_response_code(404);
        return array("error" => "E-mail nao encontrado.", );
    }
}
?>
