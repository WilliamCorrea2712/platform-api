<?php
require_once __DIR__ . '/../../models/product/stock.php';

function addStockOptions($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (isset($data['type']) && isset($data['options'])) {
            $type = $data['type'];
            $options = $data['options'];

            return checkAndAddStockTypeOptions($type, $options);
        } else {
            return createResponse("Os campos 'type' e 'options' são obrigatórios.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function getStockOptions() {
    $stockOptions = getAllStockOptions();

    if (!empty($stockOptions)) {
        return createResponse($stockOptions, 200);
    } else {
        return createResponse("Nenhum tipo de estoque encontrado.", 404);
    }
}
 
function deleteStockOptions($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (isset($data['type_id']) || isset($data['option_id'])) {
            $type_id = isset($data['type_id']) ? $data['type_id'] : null;
            $option_id = isset($data['option_id']) ? $data['option_id'] : null;

            if (!$type_id && !$option_id) {
                return createResponse("Deve ser fornecido pelo menos type_id ou option_id.", 400);
            }

            $result = deleteStockOptionsFromDatabase($type_id, $option_id);

            return createResponse($result['response'], $result['status']);
        } else {
            return createResponse("Pelo menos type_id ou option_id deve ser fornecido.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function addProductStock($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (isset($data['product_id']) && isset($data['type_id']) && isset($data['option_id']) && isset($data['quantity'])) {
            $product_id = $data['product_id'];
            $type_id = $data['type_id'];
            $option_ids = $data['option_id'];
            $quantity = $data['quantity'];

            if (!itemExists("product", "product_id", $product_id)) {
                return createResponse("Produto não encontrado.", 404);
            }

            if (!itemExists("stock_types", "id", $type_id)) {
                return createResponse("Tipo de estoque não encontrado.", 404);
            }

            foreach ($option_ids as $option_id) {
                $optionExistsForType = optionExistsForType($option_id, $type_id);
                if (!$optionExistsForType) {
                    return createResponse("Uma ou mais opções de estoque não pertencem ao tipo de estoque fornecido.", 400);
                }
            }

            if (itemExists("product_stock", "product_id", $product_id)) {
                return createResponse("O estoque para este produto e opção já existe.", 404);
            }

            $result = addProductStockToDatabase($product_id, $type_id, $option_ids, $quantity);

            return createResponse($result['response'], $result['status']);
        } else {
            return createResponse("Os campos 'product_id', 'option_id' e 'quantity' são obrigatórios.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

?>
