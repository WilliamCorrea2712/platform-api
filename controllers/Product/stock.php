<?php
require_once __DIR__ . '/../../models/product/stock.php';
require_once __DIR__ . '/../../global/helpers.php';

function addStockOptions($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!empty($data)) {
            $controller = new ProductAttributeController();
            return $controller->processAttributes($user_id, $data);
        } else {
            return createResponse("Nenhum dado foi enviado.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function editStockOptions($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "PUT") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['product_id']) || !isset($data['id']) || !isset($data['attribute_id']) || !isset($data['quantity']) || !isset($data['operation'])) {
            return createResponse("Os campos 'product_id', 'id', 'attribute_id', 'quantity' e 'operation: add ou subtract' são obrigatórios para editar uma opção de estoque.", 400);
        }

        $product_id = $data['product_id'];
        $id = $data['id'];
        $attribute_id = $data['attribute_id'];
        $quantity = $data['quantity'];
        $operation = $data['operation'];

        $additional_value = null;
        $operation_type = null;

        if (isset($data['additional_value']) && isset($data['operation_type'])) {
            $additional_value = $data['additional_value'];
            $operation_type = $data['operation_type'];

            if ($operation_type !== '+' && $operation_type !== '-') {
                return createResponse("O campo 'operation_type' deve ser '+' ou '-'!", 400);
            }
        } elseif (isset($data['additional_value']) || isset($data['operation_type'])) {
            return createResponse("Os campos 'additional_value' e 'operation_type' devem ser fornecidos juntos.", 400);
        }

        $model = new ProductStockModel();
        return $model->editStockOptions($user_id, $product_id, $id, $attribute_id, $quantity, $operation, $additional_value, $operation_type);
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function getStockOptions($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $model = new ProductStockModel();
        $product_id = $_GET['product_id'] ?? null;

        return $model->getStockOptions($product_id);
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function deleteStockOptions($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['product_id']) || !isset($data['stock_id']) || !isset($data['attribute_id'])) {
            return createResponse("Os campos 'product_id', 'id' e 'attribute_id' são obrigatórios para excluir uma opção de estoque.", 400);
        }

        $product_id = $data['product_id'];
        $id = $data['stock_id'];
        $attribute_id = $data['attribute_id'];

        $model = new ProductStockModel();
        return $model->deleteStockOptions($user_id, $product_id, $id, $attribute_id);
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

class ProductAttributeController {
    public function processAttributes($user_id, $options) {
        if (empty($options)) {
            return createResponse("Nenhuma opção de estoque foi fornecida.", 400);
        }
        $firstQuantity = $options[0]['quantity'];
        $otherQuantities = array_column(array_slice($options, 1), 'quantity');
        $totalQuantity = array_sum($otherQuantities);
    
        if ((int)$firstQuantity !== (int)$totalQuantity) {
            return createResponse("A quantidade do tipo não é igual à soma das quantidades dos atributos.", 400);
        }
    
        $model = new ProductStockModel();
        return $model->addStockOptions($user_id, $options);
    }
}
?>
