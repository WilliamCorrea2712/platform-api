<?php
if (session_status() == 'CART') {
    session_start();
}


require_once __DIR__ . '/../global/helpers.php';
require_once __DIR__ . '/../models/product/stock.php';
require_once __DIR__ . "/../models/checkout/cart.php";

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
        
        $required_variables = ['product_id', 'id', 'attribute_id', 'quantity', 'session_id'];
        foreach ($required_variables as $variable) {
            if (!isset($data[$variable])) {
                return createResponse("A variavel '$variable' e obrigatoria.", 400);
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
        $customer_id = isset($data['customer_id'])?$data['customer_id'] : 0;
        $session_id = $data['session_id'];

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

        $result = $productStockModel->saveToTemporaryCart($user_id, $product_id, $id, $attribute_id, $quantity, $operation, $session_id, $customer_id);

        if (isset($result['status']) && $result['status'] !== 200) {
            return createResponse($result['message'], $result['status']);
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

    public static function getProductsCart($user_id, $session_id, $customer_id) {
        $cartModel = new CartModel();
        $products = $cartModel->getProductsCart($user_id, $session_id, $customer_id);
    
        if ($products) {
            return createResponse($products, 200);
        } else {
            return createResponse("Nenhum produto encontrado no carrinho.", 404);
        }
    }

    public static function clearSession($user_id, $session_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return createResponse("Método não permitido. Apenas POST é permitido.", 400);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if(isset($session_id)) {
            $productStockModel = new ProductStockModel();
            $result = $productStockModel->restoreStockFromCart($user_id, $session_id);

            if ($result && isset($result['status'])) {
                if ($result['status'] === 200) {
                    return createResponse("Estoque restaurado com sucesso.", 200);
                } else {
                    return createResponse("Falha ao restaurar o estoque: " . $result['message'], 500);
                }
            }          
        } else {
            return createResponse("A sessão ainda não foi definida.", 400);
        }
    }

    public static function removeToCart($user_id){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return createResponse("Método não permitido. Apenas POST é permitido.", 400);
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $required_variables = ['product_id', 'id', 'attribute_id', 'session_id'];
        foreach ($required_variables as $variable) {
            if (!isset($data[$variable])) {
                return createResponse("A variavel '$variable' e obrigatoria.", 400);
            }
        }

        $product_id = $data['product_id'];
        $id = $data['id'];
        $attribute_id = $data['attribute_id'];
        $session_id = $data['session_id'];

        $productStockModel = new ProductStockModel();
        $result = $productStockModel->restoreStockFromCart($user_id, $session_id, $product_id, $id, $attribute_id);

        return $result;    
    }
}
?>
