<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once __DIR__ . '/../../global/helpers.php';
require_once(__DIR__ . '/../../config.php');

class CartModel {
    public function getProductsCart($session_id) {
        global $conn;

        $sql = "SELECT * FROM " . PREFIX . "temporary_cart WHERE session_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $session_id);
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
