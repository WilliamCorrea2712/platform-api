<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once __DIR__ . '/../../global/helpers.php';
require_once __DIR__ . '/../../global/logs.php';
require_once(__DIR__ . '/../../config.php');

class BrandModel {
    private $conn;

    public function __construct() {
        $this->conn = $GLOBALS['conn'];
    }

    function addBrandToDatabase($user_id, $name, $description, $image, $meta_title, $meta_description, $meta_keyword, $sort_order, $status) {
        $apiUrlModel = new ApiUrlModel();
        $urlCreationResult = $apiUrlModel->valid($name);

        if ($urlCreationResult) {
            return createResponse("Url Amigável já existe, altere o nome!", 500);
        }

        $sql = "INSERT INTO " . PREFIX . "brand (image, sort_order, status, created_by_user_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sisi", $image, $sort_order, $status, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $brand_id = $stmt->insert_id;

            $apiUrlModel->createUrl('brand', $brand_id, $name);

            $sql = "INSERT INTO " . PREFIX . "brand_description (brand_id, name, description, meta_title, meta_description, meta_keyword) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isssss", $brand_id, $name, $description, $meta_title, $meta_description, $meta_keyword);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $stmt->close();
                $this->conn->close();
                return createResponse("Marca adicionada com sucesso.", 201);
            } else {
                $stmt->close();
                $this->conn->close();
                return createResponse("Erro ao inserir na tabela " . PREFIX . "brand_description.", 500);
            }
        } else {
            $stmt->close();
            $this->conn->close();
            return createResponse("Erro ao inserir na tabela " . PREFIX . "brand.", 500);
        }
    }

    function getAllBrands($brand_id = null) {
        $sql = "SELECT c.*, cd.name, cd.description, cd.meta_title, cd.meta_description, cd.meta_keyword
                FROM " . PREFIX . "brand c
                LEFT JOIN " . PREFIX . "brand_description cd ON c.brand_id = cd.brand_id ";

        if ($brand_id !== null) {
            $sql .= " WHERE c.brand_id = ?";
        }

        $stmt = $this->conn->prepare($sql);

        if ($brand_id !== null) {
            $stmt->bind_param("i", $brand_id);
        }

        if (!$stmt->execute()) {
            return createResponse("Erro ao buscar marcas: " . $this->conn->error, 500);
        }

        $result = $stmt->get_result();
        $brands = array();

        while ($row = $result->fetch_assoc()) {
            $brand_id = $row['brand_id'];

            $apiUrlModel = new ApiUrlModel();
            $friendlyUrl = $apiUrlModel->getUrlValue('brand', $brand_id);

            $brands[] = array(
                'id' => $brand_id,
                'name' => $row['name'],
                'image' => $row['image'],
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

        return $brands;
    }

    function editbrandInDatabase($user_id, $brand_id, $name, $description, $image, $meta_title, $meta_description, $meta_keyword, $sort_order, $status) {
        $sql_brand = "UPDATE " . PREFIX . "brand SET ";
        $params_brand = array();

        if ($image !== null) {
            $sql_brand .= "image = ?, ";
            $params_brand[] = $image;
        }
        if ($sort_order !== null) {
            $sql_brand .= "sort_order = ?, ";
            $params_brand[] = $sort_order;
        }
        if ($status !== null) {
            $sql_brand .= "status = ?, ";
            $params_brand[] = $status;
        }
        $sql_brand .= "updated_by_user_id = ?, updated_at = CURRENT_TIMESTAMP WHERE brand_id = ?";
        $params_brand[] = $user_id;
        $params_brand[] = $brand_id;

        $sql_brand = rtrim($sql_brand, ", ");

        $stmt_brand = $this->conn->prepare($sql_brand);

        if (!$stmt_brand) {
            return createResponse("Erro na preparação da declaração SQL: " . $this->conn->error, 500);
        }

        $bind_types_brand = str_repeat("s", count($params_brand));

        $stmt_brand->bind_param($bind_types_brand, ...$params_brand);

        if (!$stmt_brand->execute()) {
            $stmt_brand->close();
            $this->conn->close();
            return createResponse("Erro ao atualizar a marca na tabela " . PREFIX . "brand: " . $this->conn->error, 500);
        }

        $stmt_brand->close();

        $sql_description = "UPDATE " . PREFIX . "brand_description SET ";
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
        $sql_description .= "updated_at = CURRENT_TIMESTAMP WHERE brand_id = ?";
        $params_description[] = $brand_id;

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
            return createResponse("Erro ao atualizar a marca na tabela " . PREFIX . "brand_description: " . $this->conn->error, 500);
        }

        $stmt_description->close();
        $this->conn->close();
        
        return createResponse("Marca atualizada com sucesso.", 200);
    }

    function deleteBrandFromDatabase($user_id, $brand_id) {
        $sql_description = "DELETE FROM " . PREFIX . "brand_description WHERE brand_id = ?";
        $stmt_description = $this->conn->prepare($sql_description);
        $stmt_description->bind_param("i", $brand_id);
        $stmt_description->execute();
        $stmt_description->close();

        $sql_brand = "DELETE FROM " . PREFIX . "brand WHERE brand_id = ?";
        $stmt_brand = $this->conn->prepare($sql_brand);
        $stmt_brand->bind_param("i", $brand_id);
        $stmt_brand->execute();

        $apiUrlModel = new ApiUrlModel();
        $urlDelete = $apiUrlModel->deleteUrl('brand', $brand_id);

        if (isset($urlDelete['error'])) {
            return createResponse("Erro ao deletar URL amigável: " . $urlDelete['error'] , 500);
        }

        if ($stmt_brand->affected_rows > 0) {
            insertLog($user_id, "brand_id=$brand_id", "deleted");

            $stmt_brand->close();
            $this->conn->close();
            return createResponse("marca excluída com sucesso.", 200);
        } else {
            $stmt_brand->close();
            $this->conn->close();
            return createResponse("Erro ao excluir a marca: " . $this->conn->error, 500);
        }
    }
}
?>
