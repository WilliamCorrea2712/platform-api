<?php
require_once __DIR__ . '/../mysql/conn.php';
require_once __DIR__ . '/../config.php';

function createResponse($message, $status) {
    http_response_code($status);
    if ($status >= 200 && $status < 400) {
        echo json_encode(array("message" => $message), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(array("error" => $message), JSON_UNESCAPED_UNICODE);
    }
}

/*function createResponse($message, $status) {
    http_response_code($status);
    if ($status >= 200 && $status < 400) {
        if (is_array($message)) {
            $response = array();
            foreach ($message as $item) {
                $response[] = formatData($item);
            }
            echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(formatData($message), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(array("error" => $message), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

function formatData($data) {
    if (is_array($data)) {
        $formatted_data = array();
        foreach ($data as $key => $value) {
            $formatted_data[$key] = formatData($value);
        }
        return $formatted_data;
    } else {
        return $data;
    }
}*/

function existsInTable($table, $column, $value) {
    global $conn;

    $sql = "SELECT COUNT(*) AS total FROM " . PREFIX . "$table WHERE $column = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['total'] > 0;
}

function customerExists($customer_id) {
    $exists = existsInTable('customers', 'id', $customer_id);
    return $exists;
}

function customerExistsByEmail($email) {
    $exists = existsInTable('customers', 'email', $email);
    return $exists;
}

function userExists($name, $email) {
    $exists = existsInTable('user', 'name', $name) || userEmailExists($email);
    return $exists;
}

function userEmailExists($email) {
    $exists = existsInTable('user', 'email', $email);
    return $exists;
}

function itemExists($table, $id_column, $item_id) {
    global $conn;

    $sql = "SELECT COUNT(*) AS count FROM " . PREFIX . $table . " WHERE " . $id_column . " = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return $count > 0;
}

function itemExistsArray($table, $id_column, $item_ids) {
    global $conn;

    $placeholders = implode(',', array_fill(0, count($item_ids), '?'));

    $sql = "SELECT COUNT(*) AS count FROM " . PREFIX . $table . " WHERE " . $id_column . " IN ($placeholders)";

    $stmt = $conn->prepare($sql);

    $types = str_repeat('i', count($item_ids));
    $stmt->bind_param($types, ...$item_ids);

    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return $count === count($item_ids);
}

function getProductImages($product_id) {
    global $conn;

    $sql = "SELECT COUNT(*) AS total FROM " . PREFIX . "product_image WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['total'] ;
}

function isValidCnpjCpf($cnpj_cpf) {
    $cnpj_cpf = preg_replace("/[^0-9]/", "", $cnpj_cpf);

    if (strlen($cnpj_cpf) != 11 && strlen($cnpj_cpf) != 14) {
        return false;
    }

    if (strlen($cnpj_cpf) == 11) {
        if (preg_match("/(\d)\1{10}/", $cnpj_cpf)) {
            return false;
        }

        $sum = 0;
        for ($i = 0, $j = 10; $i < 9; $i++, $j--) {
            $sum += $cnpj_cpf[$i] * $j;
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        $sum = 0;
        for ($i = 0, $j = 11; $i < 10; $i++, $j--) {
            $sum += $cnpj_cpf[$i] * $j;
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        if ($cnpj_cpf[9] != $digit1 || $cnpj_cpf[10] != $digit2) {
            return false;
        }

        return true;
    }

    if (strlen($cnpj_cpf) == 14) {
        if (preg_match("/(\d)\1{13}/", $cnpj_cpf)) {
            return false;
        }

        $sum = 0;
        $weights = array(5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2);
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj_cpf[$i] * $weights[$i];
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        $sum = 0;
        $weights = array(6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2);
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj_cpf[$i] * $weights[$i];
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        if ($cnpj_cpf[12] != $digit1 || $cnpj_cpf[13] != $digit2) {
            return false;
        }

        return true;
    }

    return false;
}

function isValidRgIe($rg_ie) {
    $rg_ie = preg_replace("/[^0-9]/", "", $rg_ie);

    if (strlen($rg_ie) < 1) {
        return false;
    }
    return true;
}

function isValidTypePerson($type_person) {
    return in_array($type_person, ['fisica', 'juridica']);
}

function cpfExists($cpf, $customer_id = null) {
    global $conn;

    $sql = "SELECT COUNT(*) as count FROM " . PREFIX . "customers WHERE cnpj_cpf = ?";

    if ($customer_id !== null) {
        $sql .= " AND id <> ?";
    }

    $stmt = $conn->prepare($sql);
    
    if ($customer_id !== null) {
        $stmt->bind_param("si", $cpf, $customer_id);
    } else {
        $stmt->bind_param("s", $cpf);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['count'] > 0;
}

function is_valid_image($file_tmp_name, $file_type) {
    $allowed_types = array(
        'image/jpeg',
        'image/png',
        'image/gif'
    );

    if (in_array($file_type, $allowed_types)) {
        if (getimagesize($file_tmp_name) !== false) {
            return true;
        }
    }
    return false;
}
?>
