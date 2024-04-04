<?php
require_once __DIR__ . '/../mysql/conn.php';
require_once __DIR__ . '/../security/token.php';

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
        return array("error" => "Usuario ja existe.");
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

function getAllUsers() {
  global $conn;

  $sql = "SELECT id, name, password FROM api_user";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
      $users = array();

      while ($row = $result->fetch_assoc()) {
          $users[] = array(
              'id' => $row['id'],
              'name' => $row['name'],
          );
      }

      http_response_code(200);
      echo json_encode($users, JSON_PRETTY_PRINT);
  } else {
      return array();
  }
}

function updateUser($id, $name, $password = null) {
  global $conn;

  if ($password !== null) {
    $sql = "UPDATE api_user SET name=?, password=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $password, $id);
  } else {
    $sql = "UPDATE api_user SET name=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $name, $id);
  }

  if ($stmt->execute()) {
      http_response_code(200);
      return array("message" => "Dados do usuario atualizados com sucesso.");
  } else {
      http_response_code(500);
      return array("message" => "Erro ao atualizar os dados do usuário: " . $conn->error);
  }
}

function delUser($user_id) {
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
?>
