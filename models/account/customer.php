<?php
require_once __DIR__ . '/../../mysql/conn.php';

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

function customerExistsByEmail($email) {
    global $conn;

    $sql = "SELECT COUNT(*) AS total FROM api_customers WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] > 0;
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

?>
