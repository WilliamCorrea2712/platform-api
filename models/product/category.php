<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once __DIR__ . '/../../global/helpers.php';
require_once __DIR__ . '/../../global/logs.php';
require_once(__DIR__ . '/../../config.php');
class CategoryModel {
    private $conn;

    public function __construct() {
        $this->conn = $GLOBALS['conn'];
    }

    function addCategoryToDatabase($user_id, $name, $description, $image, $parent_id, $meta_title, $meta_description, $meta_keyword, $sort_order, $status) {
        $apiUrlModel = new ApiUrlModel();
        $urlCreationResult = $apiUrlModel->valid($name);

        if ($urlCreationResult) {
            return createResponse("Url Amigável já existe, altere o nome!", 500);
        }

        $sql = "INSERT INTO " . PREFIX . "category (image, parent_id, sort_order, status, created_by_user_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("siisi", $image, $parent_id, $sort_order, $status, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $category_id = $stmt->insert_id;

            $apiUrlModel->createUrl('category', $category_id, $name);

            $sql = "INSERT INTO " . PREFIX . "category_description (category_id, name, description, meta_title, meta_description, meta_keyword) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isssss", $category_id, $name, $description, $meta_title, $meta_description, $meta_keyword);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {           
                $stmt->close();
                $this->conn->close();
                return createResponse("Categoria adicionada com sucesso.", 201);
            } else {
                $stmt->close();
                $this->conn->close();
                return createResponse("Erro ao inserir na tabela " . PREFIX . "category_description.", 500);
            }
        } else {
            $stmt->close();
            $this->conn->close();
            return createResponse("Erro ao inserir na tabela " . PREFIX . "category.", 500);
        }
    }

    function getAllCategories($category_id, $parent_id) {
        $sql = "SELECT c.*, cd.name, cd.description, cd.meta_title, cd.meta_description, cd.meta_keyword
                FROM " . PREFIX . "category c
                LEFT JOIN " . PREFIX . "category_description cd ON c.category_id = cd.category_id WHERE 1 = 1 ";

        if ($category_id !== null && $parent_id !== null) {
            return createResponse("Somente um parâmetro pode ser passado!", 400);
        }

        if ($category_id !== null) {
            $sql .= " AND c.category_id = ? ";
        }

        if ($parent_id !== null) {
            $sql .= " AND c.parent_id = ? ";
        }

        $stmt = $this->conn->prepare($sql);

        if ($category_id !== null) {
            $stmt->bind_param("i", $category_id);
        }

        if ($parent_id !== null) {
            $stmt->bind_param("i", $parent_id);
        }

        if (!$stmt->execute()) {
            return createResponse("Erro ao buscar categorias: " . $this->conn->error, 500);
        }

        $result = $stmt->get_result();
        $categories = array();

        while ($row = $result->fetch_assoc()) {
            $category_id = $row['category_id'];

            $apiUrlModel = new ApiUrlModel();
            $friendlyUrl = $apiUrlModel->getUrlValue('category', $category_id);

            $categories[] = array(
                'id' => $category_id,
                'name' => $row['name'],
                'image' => $row['image'],
                'parent_id' => $row['parent_id'],
                'sort_order' => $row['sort_order'],
                'status' => $row['status'],
                'url' => $friendlyUrl,
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'description' => $row['description'],
                'meta_title' => $row['meta_title'],
                'meta_description' => $row['meta_description'],
                'meta_keyword' => $row['meta_keyword'],
            );
        }

        $stmt->close();
        $this->conn->close();

        if (empty($categories)) {
            return createResponse("Nenhuma categoria encontrada!", 404);
        } else {
            return $categories;
        }        
    }

    function editCategoryInDatabase($user_id, $category_id, $name, $description, $image, $parent_id, $meta_title, $meta_description, $meta_keyword, $sort_order, $status) {
        $sql_category = "UPDATE " . PREFIX . "category SET ";
        $params_category = array();

        if ($image !== null) {
            $sql_category .= "image = ?, ";
            $params_category[] = $image;
        }
        if ($parent_id !== null) {
            $sql_category .= "parent_id = ?, ";
            $params_category[] = $parent_id;
        }
        if ($sort_order !== null) {
            $sql_category .= "sort_order = ?, ";
            $params_category[] = $sort_order;
        }
        if ($status !== null) {
            $sql_category .= "status = ?, ";
            $params_category[] = $status;
        }
        $sql_category .= "updated_by_user_id = ?, updated_at = CURRENT_TIMESTAMP WHERE category_id = ?";
        $params_category[] = $user_id;
        $params_category[] = $category_id;

        $sql_category = rtrim($sql_category, ", ");

        $stmt_category = $this->conn->prepare($sql_category);

        if (!$stmt_category) {
            return createResponse("Erro na preparação da declaração SQL: " . $this->conn->error, 500);
        }

        $bind_types_category = str_repeat("s", count($params_category));

        $stmt_category->bind_param($bind_types_category, ...$params_category);

        if (!$stmt_category->execute()) {
            $stmt_category->close();
            $this->conn->close();
            return createResponse("Erro ao atualizar a categoria na tabela api_category: " . $this->conn->error, 500);
        }

        $stmt_category->close();

        $sql_description = "UPDATE " . PREFIX . "category_description SET ";
        $params_description = array();

        if ($name !== null) {
            $sql_description .= "name = ?, ";
            $params_description[] = $name;
        }
        if ($description !== null) {
            $sql_description .= "description = ?, ";
            $params_description[] = $description;
        }
        if ($meta_title !== null) {
            $sql_description .= "meta_title = ?, ";
            $params_description[] = $meta_title;
        }
        if ($meta_description !== null) {
            $sql_description .= "meta_description = ?, ";
            $params_description[] = $meta_description;
        }
        if ($meta_keyword !== null) {
            $sql_description .= "meta_keyword = ?, ";
            $params_description[] = $meta_keyword;
        }
        $sql_description .= "updated_at = CURRENT_TIMESTAMP WHERE category_id = ?";
        $params_description[] = $category_id;

        $sql_description = rtrim($sql_description, ", ");

        $stmt_description = $this->conn->prepare($sql_description);

        if (!$stmt_description) {
            return createResponse("Erro na preparação da declaração SQL: " . $this->conn->error, 500);
        }

        $bind_types_description = str_repeat("s", count($params_description));

        $stmt_description->bind_param($bind_types_description, ...$params_description);

        if (!$stmt_description->execute()) {
            $stmt_description->close();
            $this->conn->close();
            return createResponse("Erro ao atualizar a categoria na tabela api_category_description: " . $this->conn->error, 500);
        }

        $stmt_description->close();
        $this->conn->close();
        
        return createResponse("Categoria atualizada com sucesso.", 200);
    }

    function deleteCategoryFromDatabase($user_id, $category_id) {
        $sql_description = "DELETE FROM " . PREFIX . "category_description WHERE category_id = ?";
        $stmt_description = $this->conn->prepare($sql_description);
        $stmt_description->bind_param("i", $category_id);
        $stmt_description->execute();
        $stmt_description->close();

        $sql_category = "DELETE FROM " . PREFIX . "category WHERE category_id = ?";
        $stmt_category = $this->conn->prepare($sql_category);
        $stmt_category->bind_param("i", $category_id);
        $stmt_category->execute();

        $apiUrlModel = new ApiUrlModel();
        $urlDelete = $apiUrlModel->deleteUrl('category', $category_id);

        if (isset($urlDelete['error'])) {
            return createResponse("Erro ao deletar URL amigável: " . $urlDelete['error'] , 500);
        }

        if ($stmt_category->affected_rows > 0) {
            insertLog($user_id, "category_id=$category_id", "deleted");

            $stmt_category->close();
            $this->conn->close();
            return createResponse("Categoria excluída com sucesso.", 200);
        } else {
            $stmt_category->close();
            $this->conn->close();
            return createResponse("Erro ao excluir a categoria: " . $this->conn->error, 500);
        }
    }
}
?>
