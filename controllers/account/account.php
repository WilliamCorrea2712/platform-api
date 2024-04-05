<?php
require_once __DIR__ . "/../../models/account/account.php";

function addCustomer() {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // if (!userHasPermissionToAddCustomer($user_id)) {
        //     http_response_code(401);
        //     echo json_encode(array("error" => "Você não tem permissão para adicionar clientes."), JSON_UNESCAPED_UNICODE);
        //     exit;
        // }

        if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['phone_number']) && isset($_POST['birth_date'])) {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $phone_number = $_POST['phone_number'];
            $birth_date = $_POST['birth_date'];

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(array("error" => "O formato do email é inválido."), JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (!preg_match("/^\(\d{2}\)\s\d{4,5}-\d{4}$/", $phone_number)) {
                http_response_code(400);
                echo json_encode(array("error" => "O formato do número de telefone é inválido. O formato esperado é (XX) XXXX-XXXX."), JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $birth_date) || !strtotime($birth_date)) {
                http_response_code(400);
                echo json_encode(array("error" => "A data de nascimento é inválida. O formato esperado é YYYY-MM-DD."), JSON_UNESCAPED_UNICODE);
                exit;
            }

            $result = addCustomerToDatabase($name, $email, $phone_number, $birth_date);
            echo $result;
        } else {
            http_response_code(400);
            echo json_encode(array("error" => "Dados incompletos."), JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(405);
        echo json_encode(array("error" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
    }
}

function getCustomers() {
    $result = getAllCustomers();

    if ($result) {
        http_response_code(200);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Nenhum cliente encontrado."), JSON_UNESCAPED_UNICODE);
    }
}

function addAddress() {
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
      if (isset($_POST['customer_id']) && isset($_POST['street']) && isset($_POST['city']) && isset($_POST['state']) && isset($_POST['zip_code'])) {
          $customer_id = $_POST['customer_id'];
          $street = $_POST['street'];
          $city = $_POST['city'];
          $state = $_POST['state'];
          $zip_code = $_POST['zip_code'];

          if (!preg_match('/^\d{5}-\d{3}$/', $zip_code)) {
              http_response_code(400);
              echo json_encode(array("message" => "Formato inválido para o CEP. O formato esperado é XXXXX-XX"), JSON_UNESCAPED_UNICODE);
              return;
          }

          $result = addAddressToCustomer($customer_id, $street, $city, $state, $zip_code);

          http_response_code(200);
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

?>
