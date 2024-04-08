<?php
require_once __DIR__ . "/../../models/product/category.php";
require_once __DIR__ . '/../../global/helpers.php';

function addCategory($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if ($data !== null && 
            isset($data['name']) && 
            isset($data['description'])
        ) {
            $name = $data['name'];
            $description = $data['description'];
            $image = isset($data['image']) ? $data['image'] : null;
            $parent_id = isset($data['parent_id']) ? $data['parent_id'] : null;
            $meta_title = isset($data['meta_title']) ? $data['meta_title'] : '';
            $meta_description = isset($data['meta_description']) ? $data['meta_description'] : '';
            $meta_keyword = isset($data['meta_keyword']) ? $data['meta_keyword'] : '';
            $sort_order = isset($data['sort_order']) ? $data['sort_order'] : null;
            $status = isset($data['status']) ? $data['status'] : null;

            addCategoryToDatabase($name, $description, $image, $parent_id, $meta_title, $meta_description, $meta_keyword, $sort_order, $status);

            http_response_code(201);
            echo json_encode(array("message" => "Categoria adicionada com sucesso."), JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(400);
            echo json_encode(array("error" => "Os campos 'name' e 'description' são obrigatórios."), JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(405);
        echo json_encode(array("error" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
    }
}
?>


?>