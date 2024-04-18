<?php
require_once __DIR__ . '/../../mysql/conn.php';
require_once __DIR__ . '/../../global/logs.php';
require_once(__DIR__ . '/../../config.php');

function addCustomerToDatabase($name, $email, $phone_number, $birth_date, $cnpj_cpf, $rg_ie, $type_person, $sex, $password, $user_id) {
    global $conn;

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO " . PREFIX . "customers (name, email, phone_number, birth_date, cnpj_cpf, rg_ie, type_person, sex, password, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssi", $name, $email, $phone_number, $birth_date, $cnpj_cpf, $rg_ie, $type_person, $sex, $hashed_password, $user_id);

    if ($stmt->execute()) {
        return createResponse("Cliente adicionado com sucesso.", 200);
    } else {
        return createResponse("Erro ao adicionar cliente: " . $stmt->error, 500);
    }
}

function getAllCustomers($customer_id = null) {
    global $conn;

    $sql = "SELECT c.id, c.name, c.email, c.phone_number, c.birth_date, c.status, 
                   c.cnpj_cpf, c.rg_ie, c.type_person, c.sex, c.password,
                   a.id as address_id, a.street, a.city, a.state, a.zip_code 
            FROM " . PREFIX . "customers c
            LEFT JOIN " . PREFIX . "addresses a ON c.id = a.customer_id";

    if ($customer_id !== null) {
        $sql .= " WHERE c.id = ?";
    }

    $sql .= " ORDER BY c.name";

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

        return $customers;
    } else {
        return array();
    }
}

function editCustomerInDatabase($user_id, $customer_id, $name, $phone_number, $birth_date, $cnpj_cpf, $rg_ie, $type_person, $sex, $password) {
    global $conn;

    if (!customerExists($customer_id)) {
        return createResponse("Cliente não encontrado.", 404);
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
    if ($password !== null) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql .= "password = ?, ";
        $params[] = $hashed_password;
    }

    $sql = rtrim($sql, ", ");

    $sql .= " WHERE id = ?";
    $params[] = $customer_id;

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return createResponse("Erro na preparação da declaração SQL: " . $conn->error, 500);
    }

    $bind_types = str_repeat("s", count($params));

    $stmt->bind_param($bind_types, ...$params);

    if ($stmt->execute()) {
        return createResponse("Cliente atualizado com sucesso.", 200);
    } else {
        return createResponse("Erro ao atualizar o cliente: " . $conn->error, 500);
    }
}

function deleteCustomerFromDatabase($user_id, $customer_id) {
    global $conn;

    $sql_delete_addresses = "DELETE FROM " . PREFIX . "addresses WHERE customer_id = ?";
    $stmt_delete_addresses = $conn->prepare($sql_delete_addresses);
    $stmt_delete_addresses->bind_param("i", $customer_id);

    if (!$stmt_delete_addresses->execute()) {
        return createResponse("Erro ao excluir endereços associados ao cliente: " . $stmt_delete_addresses->error, 500);
    }

    $sql_delete_customer = "DELETE FROM " . PREFIX . "customers WHERE id = ?";
    $stmt_delete_customer = $conn->prepare($sql_delete_customer);
    $stmt_delete_customer->bind_param("i", $customer_id);

    if ($stmt_delete_customer->execute()) {
        insertLog($user_id, "customer_id=$customer_id", "deleted");

        $deleted_addresses_ids = array();
        $deleted_addresses_query = "SELECT id FROM " . PREFIX . "addresses WHERE customer_id = ?";
        $stmt_deleted_addresses = $conn->prepare($deleted_addresses_query);
        $stmt_deleted_addresses->bind_param("i", $customer_id);
        $stmt_deleted_addresses->execute();
        $deleted_addresses_result = $stmt_deleted_addresses->get_result();

        while ($row = $deleted_addresses_result->fetch_assoc()) {
            $deleted_addresses_ids[] = $row['id'];
        }

        return createResponse(array("deleted_addresses_ids" => $deleted_addresses_ids), 200);
    } else {
        return createResponse("Erro ao excluir cliente: " . $stmt_delete_customer->error, 500);
    }
}

function addAddressToCustomer($customer_id, $street, $city, $state, $zip_code, $name, $number, $country, $user_id) {
    global $conn;

    if (!customerExists($customer_id)) {
        return createResponse("O ID do cliente fornecido não existe.", 400);
    }

    $created_at = date('Y-m-d H:i:s');

    $sql = "INSERT INTO " . PREFIX . "addresses (customer_id, street, city, state, zip_code, name, number, country, created_at, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssssi", $customer_id, $street, $city, $state, $zip_code, $name, $number, $country, $created_at, $user_id);

    if ($stmt->execute()) {
        $address_id = $stmt->insert_id;
        return createResponse(array("address_id" => $address_id, "message" => "Endereço adicionado com sucesso."), 200);
    } else {
        return createResponse("Erro ao adicionar endereço: " . $stmt->error, 500);
    }
}

function editAddressInDatabase($user_id, $address_id, $street = null, $city = null, $state = null, $zip_code = null, $name = null, $number = null, $country = null) {
    global $conn;

    if (!itemExists("addresses", "id", $address_id)) {
        return createResponse("Endereço não encontrado.", 404);
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
        return createResponse("Erro na preparação da declaração SQL: " . $conn->error, 500);
    }

    $bind_types = str_repeat("s", count($params));
    $stmt->bind_param($bind_types, ...$params);
    $success = $stmt->execute();

    if ($success) {
        return createResponse("Endereço atualizado com sucesso.", 200);
    } else {
        return createResponse("Erro ao atualizar endereço: " . $stmt->error, 500);
    }
}

function deleteAddressFromDatabase($user_id, $address_id) {
    global $conn;

    $sql = "DELETE FROM " . PREFIX . "addresses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $address_id);

    if ($stmt->execute()) {
        insertLog($user_id, "address_id=$address_id", "deleted");
        return createResponse(array("success" => true), 200);
    } else {
        return createResponse("Erro ao excluir endereço: " . $stmt->error, 500);
    }
}
?>
