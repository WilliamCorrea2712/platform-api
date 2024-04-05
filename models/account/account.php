<?php
require_once __DIR__ . '/../../mysql/conn.php';
require_once __DIR__ . '/../../global/helpers.php';

function addCustomerToDatabase($name, $email, $phone_number, $birth_date) {
    global $conn;

    if (customerExistsByEmail($email)) {
        http_response_code(400);
        return json_encode(array("error" => "O cliente com o email fornecido já existe."), JSON_UNESCAPED_UNICODE);
    }

    $sql = "INSERT INTO api_customers (name, email, phone_number, birth_date) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $phone_number, $birth_date);

    if ($stmt->execute()) {
        http_response_code(200);
        return json_encode(array("message" => "Cliente adicionado com sucesso."), JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        return json_encode(array("error" => "Erro ao adicionar cliente: " . $stmt->error), JSON_UNESCAPED_UNICODE);
    }
    $conn->close();
}

function getAllCustomers() {
  global $conn;

  $sql = "SELECT id, name, email, phone_number, birth_date, status FROM api_customers";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
      $customers = array();

      while ($row = $result->fetch_assoc()) {
          $customers[] = array(
              'id' => $row['id'],
              'name' => $row['name'],
              'email' => $row['email'],
              'phone_number' => $row['phone_number'],
              'birth_date' => $row['birth_date'],
              'status' => $row['status']
          );
      }

      http_response_code(200);
      echo json_encode($customers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  } else {
      return array();
  }
  $conn->close();
}

function addAddressToCustomer($customer_id, $street, $city, $state, $zip_code) {
    global $conn;

    if (!customerExists($customer_id)) {
        http_response_code(400);
        return json_encode(array("error" => "O ID do cliente fornecido não existe."), JSON_UNESCAPED_UNICODE);
    }

    $created_at = date('Y-m-d H:i:s');

    $sql = "INSERT INTO api_addresses (customer_id, street, city, state, zip_code, created_at) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $customer_id, $street, $city, $state, $zip_code, $created_at);

    if ($stmt->execute()) {
        $address_id = $stmt->insert_id;
        $conn->close();
        return json_encode(array("address_id" => $address_id, "message" => "Endereço adicionado com sucesso."), JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        return json_encode(array("error" => "Erro ao adicionar endereço: " . $stmt->error), JSON_UNESCAPED_UNICODE);
    }
}

?>
