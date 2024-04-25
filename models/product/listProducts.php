<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once __DIR__ . '/../../global/helpers.php';

function addProductListToDatabase($user_id, $name, $products, $sort_order, $status) {
    global $conn;

    $sql = "INSERT INTO api_product_lists (name, products, sort_order, status, created_by_user_id, updated_by_user_id) 
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

function getAllProductListsFromDatabase() {
    global $conn;

    $sql = "SELECT * FROM api_product_lists";
    $result = $conn->query($sql);

    $product_lists = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $product_lists[] = $row;
        }
    }

    $conn->close();
    return $product_lists;
}

function getProductListFromDatabase($product_list_id) {
    global $conn;

    $sql = "SELECT * FROM api_product_lists WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_list_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $product_list = array();

    if ($result->num_rows > 0) {
        $product_list = $result->fetch_assoc();
    }

    $stmt->close();
    $conn->close();
    return $product_list;
}

function editProductListInDatabase($user_id, $product_list_id, $name, $products, $sort_order, $status) {
    global $conn;

    $sql = "UPDATE api_product_lists SET name = ?, products = ?, sort_order = ?, status = ?, updated_by_user_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiidi", $name, $products, $sort_order, $status, $user_id, $product_list_id);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return array('response' => "Product list updated successfully.", 'status' => 200);
    } else {
        $stmt->close();
        $conn->close();
        return array('response' => "Failed to update product list.", 'status' => 500);
    }
}

function deleteProductListFromDatabase($user_id, $product_list_id) {
    global $conn;

    $sql = "DELETE FROM api_product_lists WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_list_id);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return array('response' => "Product list deleted successfully.", 'status' => 200);
    } else {
        $stmt->close();
        $conn->close();
        return array('response' => "Failed to delete product list.", 'status' => 500);
    }
}
?>
