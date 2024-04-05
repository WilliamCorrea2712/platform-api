<?php
require_once __DIR__ . '/../../mysql/conn.php';
require_once __DIR__ . '/../../global/logs.php';
require_once(__DIR__ . '/../../config.php');

function addCustomerToDatabase($name, $email, $phone_number, $birth_date, $cnpj_cpf, $rg_ie, $type_person, $sex, $user_id) {
    global $conn;

    if (customerExistsByEmail($email)) {
        http_response_code(400);
        return json_encode(array("error" => "O cliente com o email fornecido já existe."), JSON_UNESCAPED_UNICODE);
    }

    $sql = "INSERT INTO " . PREFIX . "customers (name, email, phone_number, birth_date, cnpj_cpf, rg_ie, type_person, sex, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", $name, $email, $phone_number, $birth_date, $cnpj_cpf, $rg_ie, $type_person, $sex, $user_id);

    if ($stmt->execute()) {
        http_response_code(200);
        return json_encode(array("message" => "Cliente adicionado com sucesso."), JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        return json_encode(array("error" => "Erro ao adicionar cliente: " . $stmt->error), JSON_UNESCAPED_UNICODE);
    }
    $conn->close();
}

function getAllCustomers($customer_id = null) {
    global $conn;

    $sql = "SELECT c.id, c.name, c.email, c.phone_number, c.birth_date, c.status, 
                   c.cnpj_cpf, c.rg_ie, c.type_person, c.sex, 
                   a.id as address_id, a.street, a.city, a.state, a.zip_code 
            FROM " . PREFIX . "customers c
            LEFT JOIN " . PREFIX . "addresses a ON c.id = a.customer_id";

    if ($customer_id !== null) {
        $sql .= " WHERE c.id = ?";
    }

    $stmt = $conn->prepare($sql);

    if ($customer_id !== null) {
        $stmt->bind_param("i", $customer_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $customers = array();

        while ($row = $result->fetch_assoc()) {
            $customer_id = $row['id'];

            if (!isset($customers[$customer_id])) {
                $customers[$customer_id] = array(
                    'id' => $customer_id,
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'phone_number' => $row['phone_number'],
                    'birth_date' => $row['birth_date'],
                    'status' => $row['status'],
                    'cnpj_cpf' => $row['cnpj_cpf'],
                    'rg_ie' => $row['rg_ie'],
                    'type_person' => $row['type_person'],
                    'sex' => $row['sex'],
                    'addresses' => array()
                );
            }
            if ($row['address_id'] !== null) {
                $customers[$customer_id]['addresses'][] = array(
                    'id' => $row['address_id'],
                    'street' => $row['street'],
                    'city' => $row['city'],
                    'state' => $row['state'],
                    'zip_code' => $row['zip_code']
                );
            }
        }

        http_response_code(200);
        echo json_encode(array_values($customers), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Nenhum cliente encontrado."), JSON_UNESCAPED_UNICODE);
    }
    
    $stmt->close();
    $conn->close();
}


function editCustomerInDatabase($user_id, $customer_id, $name, $phone_number, $birth_date, $cnpj_cpf, $rg_ie, $type_person, $sex) {
    global $conn;

    if (!customerExists($customer_id)) {
        return array("status" => 404, "response" => array("message" => "Cliente não encontrado."));
    }

    $sql = "UPDATE " . PREFIX . "customers SET ";
    $params = array();

    if ($name !== null) {
        $sql .= "name = ?, ";
        $params[] = $name;
    }
    if ($phone_number !== null) {
        $sql .= "phone_number = ?, ";
        $params[] = $phone_number;
    }
    if ($birth_date !== null) {
        $sql .= "birth_date = ?, ";
        $params[] = $birth_date;
    }
    if ($cnpj_cpf !== null) {
        $sql .= "cnpj_cpf = ?, ";
        $params[] = $cnpj_cpf;
    }
    if ($rg_ie !== null) {
        $sql .= "rg_ie = ?, ";
        $params[] = $rg_ie;
    }
    if ($type_person !== null) {
        $sql .= "type_person = ?, ";
        $params[] = $type_person;
    }
    if ($sex !== null) {
        $sql .= "sex = ?, ";
        $params[] = $sex;
    }

    $sql = rtrim($sql, ", ");

    $sql .= " WHERE id = ?";
    $params[] = $customer_id;

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return array("status" => 500, "response" => array("error" => "Erro na preparação da declaração SQL: " . $conn->error));
    }

    $bind_types = str_repeat("s", count($params));

    $stmt->bind_param($bind_types, ...$params);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return array("status" => 200, "response" => array("message" => "Cliente atualizado com sucesso."));
    } else {
        $stmt->close();
        $conn->close();
        return array("status" => 500, "response" => array("error" => "Erro ao atualizar o cliente: " . $conn->error));
    }
}

function deleteCustomerFromDatabase($user_id, $customer_id) {
    global $conn;

    $sql_delete_addresses = "DELETE FROM " . PREFIX . "addresses WHERE customer_id = ?";
    $stmt_delete_addresses = $conn->prepare($sql_delete_addresses);
    $stmt_delete_addresses->bind_param("i", $customer_id);

    if (!$stmt_delete_addresses->execute()) {
        return array("success" => false, "error" => "Erro ao excluir endereços associados ao cliente: " . $stmt_delete_addresses->error);
    }

    $sql_delete_customer = "DELETE FROM " . PREFIX . "customers WHERE id = ?";
    $stmt_delete_customer = $conn->prepare($sql_delete_customer);
    $stmt_delete_customer->bind_param("i", $customer_id);

    if ($stmt_delete_customer->execute()) {
        insertLog($user_id, $customer_id);

        $deleted_addresses_ids = array();
        $deleted_addresses_query = "SELECT id FROM " . PREFIX . "addresses WHERE customer_id = ?";
        $stmt_deleted_addresses = $conn->prepare($deleted_addresses_query);
        $stmt_deleted_addresses->bind_param("i", $customer_id);
        $stmt_deleted_addresses->execute();
        $deleted_addresses_result = $stmt_deleted_addresses->get_result();

        while ($row = $deleted_addresses_result->fetch_assoc()) {
            $deleted_addresses_ids[] = $row['id'];
        }

        return array("success" => true, "deleted_addresses_ids" => $deleted_addresses_ids);
    } else {
        return array("success" => false, "error" => "Erro ao excluir cliente: " . $stmt_delete_customer->error);
    }

    $stmt_delete_addresses->close();
    $stmt_delete_customer->close();
}

function addAddressToCustomer($customer_id, $street, $city, $state, $zip_code, $name, $number, $country, $user_id) {
    global $conn;

    if (!customerExists($customer_id)) {
        http_response_code(400);
        return json_encode(array("error" => "O ID do cliente fornecido não existe."), JSON_UNESCAPED_UNICODE);
    }

    $created_at = date('Y-m-d H:i:s');

    $sql = "INSERT INTO " . PREFIX . "addresses (customer_id, street, city, state, zip_code, name, number, country, created_at, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssssi", $customer_id, $street, $city, $state, $zip_code, $name, $number, $country, $created_at, $user_id);

    if ($stmt->execute()) {
        $address_id = $stmt->insert_id;
        $conn->close();
        return json_encode(array("address_id" => $address_id, "message" => "Endereço adicionado com sucesso."), JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        return json_encode(array("error" => "Erro ao adicionar endereço: " . $stmt->error), JSON_UNESCAPED_UNICODE);
    }
}

function editAddressInDatabase($user_id, $address_id, $street = null, $city = null, $state = null, $zip_code = null, $name = null, $number = null, $country = null) {
    global $conn;

    if (!addressExists($address_id)) {
        return array("status" => 404, "response" => array("message" => "Endereço não encontrado."));
    }

    $sql = "UPDATE " . PREFIX . "addresses SET ";
    $params = array();
    if ($street !== null) {
        $sql .= "street = ?, ";
        $params[] = $street;
    }
    if ($city !== null) {
        $sql .= "city = ?, ";
        $params[] = $city;
    }
    if ($state !== null) {
        $sql .= "state = ?, ";
        $params[] = $state;
    }
    if ($zip_code !== null) {
        $sql .= "zip_code = ?, ";
        $params[] = $zip_code;
    }
    if ($name !== null) {
        $sql .= "name = ?, ";
        $params[] = $name;
    }
    if ($number !== null) {
        $sql .= "number = ?, ";
        $params[] = $number;
    }
    if ($country !== null) {
        $sql .= "country = ?, ";
        $params[] = $country;
    }

    $sql .= "updated_at = NOW(), updated_by_user_id = ? WHERE id = ?";
    $params[] = $user_id;
    $params[] = $address_id;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return array("status" => 500, "response" => array("error" => "Erro na preparação da declaração SQL: " . $conn->error));
    }

    $bind_types = str_repeat("s", count($params));
    $stmt->bind_param($bind_types, ...$params);
    $success = $stmt->execute();

    if ($success) {
        return array("status" => 200, "response" => array("message" => "Endereço atualizado com sucesso."));
    } else {
        return array("status" => 500, "response" => array("error" => "Erro ao atualizar endereço: " . $stmt->error));
    }
    
    $stmt->close();
    $conn->close();
}

function deleteAddressFromDatabase($address_id) {
    global $conn;

    $sql = "DELETE FROM " . PREFIX . "addresses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $address_id);

    if ($stmt->execute()) {
        return array("success" => true);
    } else {
        return array("success" => false, "error" => "Erro ao excluir endereço: " . $stmt->error);
    }

    $conn->close();
}
?>
