<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once __DIR__ . '/../../global/helpers.php';

function addProductListToDatabase($user_id, $name, $products, $sort_order, $status) {
    global $conn;

    $sql = "INSERT INTO " . PREFIX . "product_lists (name, products, sort_order, status, created_by_user_id, updated_by_user_id) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiii", $name, $products, $sort_order, $status, $user_id, $user_id);
    
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $product_list_id = $stmt->insert_id;
        $stmt->close();
        $conn->close();
        return $product_list_id;
    } else {
        $stmt->close();
        $conn->close();
        return null;
    }
}

function getAllProductListsFromDatabase($id = null) {
    global $conn;

    $sql = "SELECT * FROM " . PREFIX . "product_lists";

    if ($id !== null) {
        $sql .= " WHERE id = ?";
    }

    $stmt = $conn->prepare($sql);

    if ($id !== null) {
        $stmt->bind_param("i", $id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $product_lists = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $product_lists[] = $row;
        }
    }

    $stmt->close();
    $conn->close();
    return $product_lists;
}


function editProductListInDatabase($user_id, $list_id, $name, $products, $sort_order, $status) {
    global $conn;

    $sql = "UPDATE " . PREFIX . "product_lists SET ";
    $params = array();
    if ($name !== null) {
        $sql .= "name = ?, ";
        $params[] = $name;
    }
    if ($products !== null) {
        $sql .= "products = ?, ";
        $params[] = $products;
    }
    if ($sort_order !== null) {
        $sql .= "sort_order = ?, ";
        $params[] = $sort_order;
    }
    if ($status !== null) {
        $sql .= "status = ?, ";
        $params[] = $status;
    }

    $sql .= "updated_at = NOW(), updated_by_user_id = ? WHERE id = ?";
    $params[] = $user_id;
    $params[] = $list_id;
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return createResponse("Erro na preparação da declaração SQL: " . $conn->error, 500);
    }

    $bind_types = str_repeat("s", count($params));
    $stmt->bind_param($bind_types, ...$params);
    $success = $stmt->execute();

    if ($success) {
        return createResponse("Lista atualizada com sucesso.", 200);
    } else {
        return createResponse("Erro ao atualizar list: " . $stmt->error, 500);
    }
}

function deleteProductListFromDatabase($user_id, $list_id) {
    global $conn;

    $sql = "DELETE FROM " . PREFIX . "product_lists WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $list_id);

    if ($stmt->execute()) {
        insertLog($user_id, "product_list_id=$list_id", "deleted");
        $stmt->close();
        $conn->close();
        return createResponse("Lista Deletada com sucesso.", 200);
    } else {
        $stmt->close();
        $conn->close();
        return createResponse("Erro ao deletar lista: " . $stmt->error, 500);
    }
}
?>
