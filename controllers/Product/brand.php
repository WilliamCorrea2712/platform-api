<?php
require_once __DIR__ . "/../../models/product/brand.php";
require_once __DIR__ . '/../../global/helpers.php';

function addBrand($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if ($data !== null && 
            isset($data['name']) && 
            isset($data['description'])
        ) {
            $name = $data['name'];
            $description = $data['description'];
            $image = isset($data['image']) ? $data['image'] : null;
            $meta_title = isset($data['meta_title']) ? $data['meta_title'] : '';
            $meta_description = isset($data['meta_description']) ? $data['meta_description'] : '';
            $meta_keyword = isset($data['meta_keyword']) ? $data['meta_keyword'] : '';
            $sort_order = isset($data['sort_order']) ? $data['sort_order'] : null;
            $status = isset($data['status']) ? $data['status'] : null;

            addBrandToDatabase($user_id, $name, $description, $image, $meta_title, $meta_description, $meta_keyword, $sort_order, $status);

            return createResponse("Marca adicionada com sucesso.", 201);
        } else {
            return createResponse("Os campos 'name' e 'description' são obrigatórios.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function getBrands($brand_id = null) {
    $result = getAllBrands($brand_id);

    if (!empty($result)) {
        return createResponse($result, 200);
    } else {
        return createResponse("Nenhuma marca encontrada.", 404);
    }
}

function editBrand($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "PATCH") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['brand_id'])) {
            $brand_id = $data['brand_id'];
            
            if (!itemExists("brand", "brand_id", $brand_id)) {
                return createResponse("Marca não encontrada.", 404);
            }

            if (count($data) <= 1) {
                return createResponse("Nenhum dado a ser alterado foi fornecido.", 400);
            }

            $name = isset($data['name']) ? $data['name'] : null;
            $description = isset($data['description']) ? $data['description'] : null;
            $image = isset($data['image']) ? $data['image'] : null;
            $meta_title = isset($data['meta_title']) ? $data['meta_title'] : null;
            $meta_description = isset($data['meta_description']) ? $data['meta_description'] : null;
            $meta_keyword = isset($data['meta_keyword']) ? $data['meta_keyword'] : null;
            $sort_order = isset($data['sort_order']) ? $data['sort_order'] : null;
            $status = isset($data['status']) ? $data['status'] : null;

            $result = editBrandInDatabase($user_id, $brand_id, $name, $description, $image, $meta_title, $meta_description, $meta_keyword, $sort_order, $status);

            return createResponse($result['response'], $result['status']);
        } else {
            return createResponse("O ID da marca é obrigatório.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function deleteBrand($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['brand_id'])) {
            $brand_id = $data['brand_id'];

            if (!itemExists("brand", "brand_id", $brand_id)) {
                return createResponse("Marca não encontrada.", 404);
            }

            $result = deleteBrandFromDatabase($user_id, $brand_id);

            if ($result['status'] === 200) {
                return createResponse("Marca excluída com sucesso.", 200);
            } else {
                return createResponse("Erro ao excluir marca: " . $result['response']['error'], 500);
            }
        } else {
            return createResponse("ID da marca não fornecido.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}
?>
