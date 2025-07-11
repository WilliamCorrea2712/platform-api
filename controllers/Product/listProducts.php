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

function getAllProductLists($id = null) {
    $product_lists = getAllProductListsFromDatabase($id);

    if (!empty($product_lists)) {
        return createResponse(array('product_lists' => $product_lists), 200); 
    } else {
        return createResponse(array('error' => "Nenhuma lista de produtos encontrada."), 404);
    }
}

function editProductList($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "PATCH") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['list_id'])) {
            $list_id = $data['list_id'];
            
            if (!itemExists("product_lists", "id", $list_id)) {
                return createResponse("Lista não encontrada.", 404);
            }

            if (count($data) <= 1) {
                return createResponse("Nenhum dado a ser alterado foi fornecido.", 400);
            }

            $name = isset($data['name']) ? $data['name'] : null;
            $products = isset($data['products']) ? json_encode($data['products']) : null;
            $sort_order = isset($data['sort_order']) ? $data['sort_order'] : null;
            $status = isset($data['status']) ? $data['status'] : null;  

            $result = editProductListInDatabase($user_id, $list_id, $name, $products, $sort_order, $status);

            return $result;
        } else {
            return createResponse("O ID da lista é obrigatório.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function deleteProductList($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['list_id'])) {
            $list_id = $data['list_id'];

            if (!listExists($list_id)) {
                return createResponse("Lista não encontrada.", 404);
            }

            $result = deleteProductListFromDatabase($user_id, $list_id);

            return $result;
        } else {
            return createResponse("ID da lista não fornecido.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}
?>
