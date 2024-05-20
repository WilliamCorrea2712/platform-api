<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once __DIR__ . '/../../global/helpers.php';
require_once(__DIR__ . '/../../config.php');

class CartModel {
    public function getProductsCart($user_id, $session_id, $customer_id) {
        global $conn;
    
        $sql = "SELECT * FROM " . PREFIX . "temporary_cart WHERE ";
    
        $params = [];
        $types = ""; 
    
        if (!empty($customer_id) && !empty($session_id)) {
            $sql .= "customer_id = ? AND session_id = ?";
            $params[] = $customer_id;
            $types .= "i";
            $params[] = $session_id;
            $types .= "s";
        } elseif (!empty($customer_id)) {
            $sql .= "customer_id = ?";
            $params[] = $customer_id;
            $types .= "i";
        } elseif (!empty($session_id)) {
            $sql .= "session_id = ?";
            $params[] = $session_id;
            $types .= "s";
        } else {
            return [];
        }
    
        $stmt = $conn->prepare($sql);
    
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    
        return $products;
    }    
}
?>
