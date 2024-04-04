<?php

require_once __DIR__ . '/../models/user.php'; 

function addUser() {
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
}

function getUsers() {
  return getAllUsers();
}

function editUser($user_id) {
  if ($_SERVER["REQUEST_METHOD"] == "POST") { 
      if (isset($_POST['id']) && isset($_POST['name'])) {
          $id = $_POST['id'];
          $name = $_POST['name'];

          if(isset($_POST['password'])){
            $password = $_POST['password'];
          } else {
            $password = null;
          }
          
          if ((int)$id != $user_id) {
              http_response_code(401);
              echo json_encode(array("message" => "Você não tem permissão para editar este usuário."), JSON_UNESCAPED_UNICODE);
              exit;
          }

          $result = updateUser($id, $name, $password);
          echo json_encode($result, JSON_UNESCAPED_UNICODE);

      } else {
          http_response_code(400);
          echo json_encode(array("message" => "Dados incompletos."), JSON_UNESCAPED_UNICODE);
      }
  } else {
      http_response_code(405);
      echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
  }
}

function deleteUser($user_id){
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
      if (isset($_POST['id'])) {
          $id = $_POST['id'];

          if ((int)$id != $user_id) {
              http_response_code(401);
              echo json_encode(array("message" => "Você não tem permissão para excluir este usuário."), JSON_UNESCAPED_UNICODE);
              exit;
          }

          $result = delUser($id);
          echo json_encode($result, JSON_UNESCAPED_UNICODE);
      } else {
          http_response_code(400);
          echo json_encode(array("message" => "ID do usuário não fornecido."), JSON_UNESCAPED_UNICODE);
      }
  } else {
      http_response_code(405);
      echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
  }

}

?>
