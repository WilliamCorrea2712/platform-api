<?php
session_start();

require_once __DIR__ . '/../global/helpers.php';
require_once __DIR__ . '/../models/product/stock.php';

class ShoppingCart {
    public static function addCart($user_id) {
        $data = json_decode(file_get_contents("php://input"), true);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return createResponse("Método não permitido. Apenas POST é permitido.", 400);
        }

        if(isset($data['operation'])){            
            if ($data['operation'] !== 'add' && $data['operation'] !== 'subtract') {
                return createResponse("Operação inválida. Apenas 'add' ou 'subtract' são permitidos.", 400);
            }
        } else {
            return createResponse("O campo 'operation' é obrigatório.", 400);
        }
        
        $required_variables = ['product_id', 'id', 'attribute_id', 'quantity'];
        foreach ($required_variables as $variable) {
            if (!isset($data[$variable])) {
                return createResponse("A variável '$variable' é obrigatória.", 400);
            }
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }

        $operation = $data['operation'];
        $product_id = $data['product_id'];
        $id = $data['id'];
        $attribute_id = $data['attribute_id'];
        $quantity = $data['quantity'];

        $productStockModel = new ProductStockModel(); 
        if (!$productStockModel->stockOptionExists($product_id, $id, $attribute_id)) {
            return createResponse("Opção de estoque não encontrada para o produto especificado.", 404);
        }
    
        if (!is_int($quantity) || $quantity <= 0) {
            return createResponse("A quantidade fornecida deve ser um número inteiro positivo.", 400);
        }

        $product_key = self::getProductKey($_SESSION['cart'], $id, $attribute_id);

        if ($product_key !== false) {
            $_SESSION['cart'][$product_key]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][] = array(
                'product_id' => $product_id,
                'id' => $id,
                'attribute_id' => $attribute_id,
                'quantity' => $quantity
            );
        }

        $result = $productStockModel->saveToTemporaryCart($user_id, $product_id, $id, $attribute_id, $quantity, $operation, session_id());

        if (isset($result['status']) && $result['status'] !== 200) {
            return createResponse($result['message'], $result['status']);
        } else {        
            return createResponse("Produto adicionado ao carrinho com sucesso.", 200);
        }
    }

    private static function getProductKey($cart, $id, $attribute_id) {
        foreach ($cart as $key => $item) {
            if ($item['id'] == $id && $item['attribute_id'] == $attribute_id) {
                return $key;
            }
        }
        return false;
    }

    public function clearSession($session_id) {
        $productStockModel = new ProductStockModel();
        $result = $productStockModel->restoreStockFromCart($session_id);

        if ($result['status'] === 200) {
            echo "Estoque restaurado com sucesso.";
        } else {
            echo "Falha ao restaurar o estoque: " . $result['message'];
        }
    }
}

?>
