<?php
require_once __DIR__ . "/../../models/account/account.php";
require_once __DIR__ . '/../../global/helpers.php';

function addCustomer($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
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

            $result = addCustomerToDatabase($name, $email, $phone_number, $birth_date, $user_id);
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

function getCustomers($customer_id = null) {
    $result = getAllCustomers($customer_id);

    if (!empty($result)) {
        http_response_code(200);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}


function editCustomer($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "PATCH") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['customer_id'])) {
            $customer_id = $data['customer_id'];
            
            if (!customerExists($customer_id)) {
                http_response_code(404);
                echo json_encode(array("message" => "Cliente não encontrado."), JSON_UNESCAPED_UNICODE);
                return;
            }

            if (count($data) <= 1) {
                http_response_code(400);
                echo json_encode(array("message" => "Nenhum dado a ser alterado foi fornecido."), JSON_UNESCAPED_UNICODE);
                return;
            }

            $name = isset($data['name']) ? $data['name'] : null;
            $email = isset($data['email']) ? $data['email'] : null;
            $phone_number = isset($data['phone_number']) ? $data['phone_number'] : null;
            $birth_date = isset($data['birth_date']) ? $data['birth_date'] : null;

            if ($phone_number !== null && !preg_match("/^\(\d{2}\)\s\d{4,5}-\d{4}$/", $phone_number)) {
                http_response_code(400);
                echo json_encode(array("error" => "O formato do número de telefone é inválido. O formato esperado é (XX) XXXX-XXXX."), JSON_UNESCAPED_UNICODE);
                return;
            }


            if ($birth_date !== null && (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $birth_date) || !strtotime($birth_date))) {
                http_response_code(400);
                echo json_encode(array("error" => "A data de nascimento é inválida. O formato esperado é YYYY-MM-DD."), JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($email !== null) {
                http_response_code(400);
                echo json_encode(array("message" => "Não é permitido alterar o e-mail."), JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = editCustomerInDatabase($user_id, $customer_id, $name, $phone_number, $birth_date);

            http_response_code($result['status']);
            echo json_encode($result['response'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "O ID do cliente é obrigatório."), JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
    }
}

function deleteCustomer($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['customer_id'])) {
            $customer_id = $data['customer_id'];

            if (!customerExists($customer_id)) {
                http_response_code(404);
                echo json_encode(array("message" => "Cliente não encontrado."), JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = deleteCustomerFromDatabase($user_id, $customer_id);

            if ($result['success']) {
                http_response_code(200);
                echo json_encode(array("message" => "Cliente excluído com sucesso."), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(500);
                echo json_encode(array("error" => "Erro ao excluir cliente: " . $result['error']), JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID do cliente não fornecido."), JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
    }
}

function addAddress($user_id) {
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

          $result = addAddressToCustomer($customer_id, $street, $city, $state, $zip_code, $user_id);

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

function editAddress($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "PATCH") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['address_id'])) {
            $address_id = $data['address_id'];
            
            if (!addressExists($address_id)) {
                http_response_code(404);
                echo json_encode(array("message" => "Endereço não encontrado."), JSON_UNESCAPED_UNICODE);
                return;
            }

            if (count($data) <= 1) {
                http_response_code(400);
                echo json_encode(array("message" => "Nenhum dado a ser alterado foi fornecido."), JSON_UNESCAPED_UNICODE);
                return;
            }

            $street = isset($data['street']) ? $data['street'] : null;
            $city = isset($data['city']) ? $data['city'] : null;
            $state = isset($data['state']) ? $data['state'] : null;
            $zip_code = isset($data['zip_code']) ? $data['zip_code'] : null;

            if ($zip_code !== null && !preg_match('/^\d{5}-\d{3}$/', $zip_code)) {
                http_response_code(400);
                echo json_encode(array("message" => "Formato inválido para o CEP. O formato esperado é XXXXX-XX"), JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = editAddressInDatabase($user_id, $address_id, $street, $city, $state, $zip_code);

            http_response_code($result['status']);
            echo json_encode($result['response'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "O ID do endereço é obrigatório."), JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
    }
}

function deleteAddress($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['address_id'])) {
            $address_id = $data['address_id'];

            if (!addressExists($address_id)) {
                http_response_code(404);
                echo json_encode(array("message" => "Endereço não encontrado."), JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = deleteAddressFromDatabase($user_id, $address_id);

            if ($result['success']) {
                http_response_code(200);
                echo json_encode(array("message" => "Endereço excluído com sucesso."), JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(500);
                echo json_encode(array("error" => "Erro ao excluir endereço: " . $result['error']), JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID do endereço não fornecido."), JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
    }
}

?>
