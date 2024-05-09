<?php
require_once __DIR__ . "/../../models/product/category.php";
require_once __DIR__ . '/../../global/helpers.php';

class CategoryController {
    public static function addCategory($user_id) {
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

                $categoryModel = new CategoryModel();
                $result = $categoryModel->addCategoryToDatabase($user_id, $name, $description, $image, $parent_id, $meta_title, $meta_description, $meta_keyword, $sort_order, $status);

                return $result;
            } else {
                return createResponse("Os campos 'name' e 'description' são obrigatórios.", 400);
            }
        } else {
            return createResponse("Método não permitido.", 405);
        }
    }

    public static function getCategories($category_id, $parent_id) {
        $categoryModel = new CategoryModel();
        $retult = $categoryModel->getAllCategories($category_id, $parent_id);

        if (!empty($retult)) {
            return createResponse(array('categories' => $retult), 200); 
        } else {
            return $retult;
        }
    }

    public static function editCategory($user_id) {
        if ($_SERVER["REQUEST_METHOD"] == "PATCH") { 
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (isset($data['category_id'])) {
                $category_id = $data['category_id'];
                
                if (!itemExists("category", "category_id", $category_id)) {
                    return createResponse("Categoria não encontrada.", 404);
                }

                if (count($data) <= 1) {
                    return createResponse("Nenhum dado a ser alterado foi fornecido.", 400);
                }

                $name = isset($data['name']) ? $data['name'] : null;
                $description = isset($data['description']) ? $data['description'] : null;
                $image = isset($data['image']) ? $data['image'] : null;
                $parent_id = isset($data['parent_id']) ? $data['parent_id'] : null;
                $meta_title = isset($data['meta_title']) ? $data['meta_title'] : null;
                $meta_description = isset($data['meta_description']) ? $data['meta_description'] : null;
                $meta_keyword = isset($data['meta_keyword']) ? $data['meta_keyword'] : null;
                $sort_order = isset($data['sort_order']) ? $data['sort_order'] : null;
                $status = isset($data['status']) ? $data['status'] : null;

                $categoryModel = new CategoryModel();
                $result = $categoryModel->editCategoryInDatabase($user_id, $category_id, $name, $description, $image, $parent_id, $meta_title, $meta_description, $meta_keyword, $sort_order, $status);

                return $result;
            } else {
                return createResponse("O ID da categoria é obrigatório.", 400);
            }
        } else {
            return createResponse("Método não permitido.", 405);
        }
    }

    public static function deleteCategory($user_id) {
        if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (isset($data['category_id'])) {
                $category_id = $data['category_id'];

                if (!itemExists("category", "category_id", $category_id)) {
                    return createResponse("Categoria não encontrada.", 404);
                }

                $categoryModel = new CategoryModel();
                $result = $categoryModel->deleteCategoryFromDatabase($user_id, $category_id);

                return $result;
            } else {
                return createResponse("ID da categoria não fornecido.", 400);
            }
        } else {
            return createResponse("Método não permitido.", 405);
        }
    }
}
?>
