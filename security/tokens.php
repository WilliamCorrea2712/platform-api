<?php
require_once __DIR__ . '/../jwt/JWT.php';
require_once __DIR__ . '/../mysql/conn.php';

use \Firebase\JWT\JWT;

$secret_key = "secretkey";

function generateToken($user_id) {
  global $secret_key;

  $expiry_date = strtotime('+1 year');

  $token_payload = array(
      "user_id" => $user_id,
      "exp" => $expiry_date
  );

  $jwt = JWT::encode($token_payload, $secret_key, 'HS256'); // 'HS256' é o algoritmo de assinatura

  return $jwt;
}

function verifyToken() {
  global $conn;

  if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
      http_response_code(401);
      echo json_encode(array("message" => "Token de autenticação não fornecido."), JSON_UNESCAPED_UNICODE);
      exit;
  }

  $auth_header = $_SERVER['HTTP_AUTHORIZATION'];
  $token = str_replace('Bearer ', '', $auth_header);
  
  try {
      $decoded = decodeTokenFromDatabase($token, $conn);
      return $decoded;
  } catch (Exception $e) {
      http_response_code(401);
      echo json_encode(array("message" => "Token de autenticação inválido!"), JSON_UNESCAPED_UNICODE);
      exit;
  }
}

function decodeTokenFromDatabase($token, $conn) {
  $token_parts = explode(".", $token);

  $payload = json_decode(base64_decode($token_parts[1]));

  $user_id = $payload->user_id;
  $expiration_timestamp = $payload->exp;
  $current_timestamp = time();

  if ($current_timestamp < $expiration_timestamp) {
      $sql = "SELECT count(*) as total FROM api_tokens WHERE user_id = ? AND token = ? ";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("is", $user_id, $token);
      $stmt->execute();

      $result = $stmt->get_result();
      $row = $result->fetch_assoc();

      if ($row['total'] > 0) {
          return $payload;
      } else {
          echo json_encode(array("message" => "Token de autenticação inválido!"), JSON_UNESCAPED_UNICODE);
          exit;
      }
      $stmt->close();
  } else {
      echo json_encode(array("message" => "Token Expirado!"), JSON_UNESCAPED_UNICODE);
      exit;
  }
}

?>
