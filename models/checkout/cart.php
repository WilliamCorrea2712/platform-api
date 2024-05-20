<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once __DIR__ . '/../../global/helpers.php';
require_once(__DIR__ . '/../../config.php');

class CartModel {
    public function getProductsCart($user_id, $session_id, $customer_id) {
        global $conn;
    
        $sql = "SELECT 
                cart.*, 
                product.price, 
                product.weight, 
                product.length, 
                product.width, 
                product.height,
                description.name,
                attribute.value,
                parent_attribute.value AS parent_attribute_value,
                image.url AS image_url
            FROM " . PREFIX . "temporary_cart AS cart
            INNER JOIN " . PREFIX . "product AS product ON cart.product_id = product.product_id
            INNER JOIN " . PREFIX . "product_description AS description ON product.product_id = description.product_id
            INNER JOIN " . PREFIX . "product_attribute_value AS attribute ON cart.id = attribute.id
            LEFT JOIN " . PREFIX . "product_attribute_value AS parent_attribute ON attribute.parent_attribute_id = parent_attribute.id
            LEFT JOIN (
                SELECT product_id, url
                FROM " . PREFIX . "product_image
                WHERE sort_order = (
                    SELECT MIN(sort_order)
                    FROM " . PREFIX . "product_image AS img
                    WHERE img.product_id = " . PREFIX . "product_image.product_id
                )
                GROUP BY product_id
            ) AS image ON product.product_id = image.product_id
            WHERE ";
    
        $params = [];
        $types = ""; 
    
        if (!empty($customer_id) && !empty($session_id)) {
            $sql .= "cart.customer_id = ? AND cart.session_id = ?";
            $params[] = $customer_id;
            $types .= "i";
            $params[] = $session_id;
            $types .= "s";
        } elseif (!empty($customer_id)) {
            $sql .= "cart.customer_id = ?";
            $params[] = $customer_id;
            $types .= "i";
        } elseif (!empty($session_id)) {
            $sql .= "cart.session_id = ?";
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
