<?php
require_once __DIR__ . "/../../models/product/listProducts.php";
require_once __DIR__ . '/../../global/helpers.php';

function addProductList($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);

        if ($data !== null && isset($data['name']) && isset($data['products']) && isset($data['sort_order']) && isset($data['status'])) {
            $name = $data['name'];
            $products = json_encode($data['products']);
            $sort_order = $data['sort_order'];
            $status = $data['status'];
            
            if (listProductsExists($name)) {
                return createResponse("A lista de produtos já existe.", 400);
            }

            $product_list_id = addProductListToDatabase($user_id, $name, $products, $sort_order, $status);

            if (is_numeric($product_list_id)) {
                return createResponse("Lista de produtos adicionada com sucesso.", 201);
            } else {
                return createResponse("Falha ao adicionar lista de produtos.", 500);
            }
        } else {
            return createResponse("Campos obrigatórios ausentes.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function getAllProductLists() {
    $product_lists = getAllProductListsFromDatabase();

    if (!empty($product_lists)) {
        return createResponse(array('product_lists' => $product_lists), 200); 
    } else {
        return createResponse(array('error' => "Nenhuma lista de produtos encontrada."), 404);
    }
}

function getProductList($product_list_id) {
    $product_list = getProductListFromDatabase($product_list_id);

    if (!empty($product_list)) {
        return createResponse(array('product_list' => $product_list), 200); 
    } else {
        return createResponse(array('error' => "Lista de produtos não encontrada."), 404);
    }
}

function editProductList($user_id, $product_list_id) {
    if ($_SERVER["REQUEST_METHOD"] == "PATCH") { 
        $data = json_decode(file_get_contents("php://input"), true);

        if ($data !== null) {
            $name = isset($data['name']) ? $data['name'] : null;
            $products = isset($data['products']) ? json_encode($data['products']) : null;
            $sort_order = isset($data['sort_order']) ? $data['sort_order'] : null;
            $status = isset($data['status']) ? $data['status'] : null;

            $result = editProductListInDatabase($user_id, $product_list_id, $name, $products, $sort_order, $status);

            return createResponse($result['response'], $result['status']);
        } else {
            return createResponse("Nenhum dado fornecido para atualização.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function deleteProductList($user_id, $product_list_id) {
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") { 
        $result = deleteProductListFromDatabase($user_id, $product_list_id);
        
        return createResponse($result['response'], $result['status']);
    } else {
        return createResponse("Método não permitido.", 405);
    }
}
?>
