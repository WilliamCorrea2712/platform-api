<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once __DIR__ . '/../../global/helpers.php';
require_once __DIR__ . '/../../global/logs.php';
require_once(__DIR__ . '/../../config.php');

function addProductToDatabaseHelper($user_id, $brand_id, $categories, $price, $cost_price, $weight, $length, $width, $height, $sku, $sort_order, $minimum, $status, $name, $description, $tags, $meta_title, $meta_description, $meta_keyword, $description_resume) {
    global $conn;

    $sql = "INSERT INTO " . PREFIX . "product 
            (brand_id, categories, price, cost_price, weight, length, width, height, sku, sort_order, minimum, status, created_by_user_id, updated_by_user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdddddsiiiiii", $brand_id, $categories, $price, $cost_price, $weight, $length, $width, $height, $sku, $sort_order, $minimum, $status, $user_id, $user_id);
    
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $product_id = $stmt->insert_id;

        $sql_description = "INSERT INTO " . PREFIX . "product_description 
                            (product_id, name, description, tags, meta_title, meta_description, meta_keyword, description_resume) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_description = $conn->prepare($sql_description);
        $stmt_description->bind_param("isssssss", $product_id, $name, $description, $tags, $meta_title, $meta_description, $meta_keyword, $description_resume);
        $stmt_description->execute();

        $stmt->close();
        $stmt_description->close();
        $conn->close();
        return createResponse("Produto adicionado com sucesso.", 201);
    } else {
        $stmt->close();
        $conn->close();
        return createResponse("Erro ao inserir na tabela " . PREFIX . "product.", 500);
    }
}

function getAllProducts($product_id = null) {
    global $conn;

    $sql = "SELECT p.*, pd.name as product_name, pd.description as product_description, pd.meta_title, 
    pd.meta_description, pd.meta_keyword, pd.description_resume, pd.tags
            FROM " . PREFIX . "product p
            LEFT JOIN " . PREFIX . "product_description pd ON p.product_id = pd.product_id";

    if ($product_id !== null) {
        $sql .= " WHERE p.product_id = ?";
    }

    $stmt = $conn->prepare($sql);

    if ($product_id !== null) {
        $stmt->bind_param("i", $product_id);
    }

    if (!$stmt->execute()) {
        return createResponse("Erro ao buscar produtos: " . $conn->error, 500);
    }

    $result = $stmt->get_result();
    $products = array();

    if ($result->num_rows === 0) {
        return createResponse(array(), 200);
    }

    while ($row = $result->fetch_assoc()) {
        $product_id = $row['product_id'];

        $sql_images = "SELECT image_id, url, name FROM " . PREFIX . "product_image WHERE product_id = ?";
        $stmt_images = $conn->prepare($sql_images);
        $stmt_images->bind_param("i", $product_id);
        $stmt_images->execute();
        $result_images = $stmt_images->get_result();

        $images = array();
        while ($row_image = $result_images->fetch_assoc()) {
            $images[] = array(
                'image_id' => $row_image['image_id'],
                'image_url' => $row_image['url'],
                'image_name' => $row_image['name'],
            );
        }
        $stmt_images->close();

        $sql_stock = "SELECT pav.*, pa.name FROM " . PREFIX . "product_attribute_value pav ";
        $sql_stock .= "INNER JOIN " . PREFIX . "product_attribute pa ON pav.attribute_id = pa.id";
        $sql_stock .= " WHERE pav.product_id = ?";
        $stmt_stock = $conn->prepare($sql_stock);
        $stmt_stock->bind_param("i", $product_id);
        $stmt_stock->execute();
        $result_stock = $stmt_stock->get_result();

        $stock = array();
        while ($row_stock = $result_stock->fetch_assoc()) {
            $stock[] = array(
                'name' => $row_stock['name'],
                'value' => $row_stock['value'],
                'attribute_id' => $row_stock['attribute_id'],
                'parent_attribute_id' => $row_stock['parent_attribute_id'],
                'quantity' => $row_stock['quantity'],
                'stock_cart' => $row_stock['stock_cart']?$row_stock['stock_cart']:0,
                'operation_type' => $row_stock['operation_type'],
                'additional_value' => $row_stock['additional_value']
            );
        }
        $stmt_stock->close();

        $products[] = array(
            'id' => $product_id,
            'brand_id' => $row['brand_id'],
            'categories' => json_decode($row['categories']),
            'price' => $row['price'],
            'cost_price' => $row['cost_price'],
            'weight' => $row['weight'],
            'length' => $row['length'],
            'width' => $row['width'],
            'height' => $row['height'],
            'sku' => $row['sku'],
            'sort_order' => $row['sort_order'],
            'minimum' => $row['minimum'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'name' => $row['product_name'],
            'description' => $row['product_description'],
            'description_resume' => $row['description_resume'],
            'meta_title' => $row['meta_title'],
            'meta_description' => $row['meta_description'],
            'meta_keyword' => $row['meta_keyword'],
            'tags' => $row['tags'],
            'images' => $images,
            'stock' => $stock,
        );
    }
    return $products;
}

function editProductInDatabase($user_id, $product_id, $brand_id, $categories, $price, $cost_price, $weight, $length, $width, $height, $sku, $sort_order, $minimum, $status, $name, $description, $tags, $meta_title, $meta_description, $meta_keyword, $description_resume) {
    global $conn;

    $sql_product = "UPDATE " . PREFIX . "product SET ";
    $params_product = array();

    if ($brand_id !== null) {
        $sql_product .= "brand_id = ?, ";
        $params_product[] = $brand_id;
    }
    if ($categories !== null) {
        $sql_product .= "categories = ?, ";
        $params_product[] = $categories;
    }
    if ($price !== null) {
        $sql_product .= "price = ?, ";
        $params_product[] = $price;
    }
    if ($cost_price !== null) {
        $sql_product .= "cost_price = ?, ";
        $params_product[] = $cost_price;
    }
    if ($weight !== null) {
        $sql_product .= "weight = ?, ";
        $params_product[] = $weight;
    }
    if ($length !== null) {
        $sql_product .= "length = ?, ";
        $params_product[] = $length;
    }
    if ($width !== null) {
        $sql_product .= "width = ?, ";
        $params_product[] = $width;
    }
    if ($height !== null) {
        $sql_product .= "height = ?, ";
        $params_product[] = $height;
    }
    if ($sku !== null) {
        $sql_product .= "sku = ?, ";
        $params_product[] = $sku;
    }
    if ($sort_order !== null) {
        $sql_product .= "sort_order = ?, ";
        $params_product[] = $sort_order;
    }
    if ($minimum !== null) {
        $sql_product .= "minimum = ?, ";
        $params_product[] = $minimum;
    }
    if ($status !== null) {
        $sql_product .= "status = ?, ";
        $params_product[] = $status;
    }
    $sql_product .= "updated_by_user_id = ?, updated_at = CURRENT_TIMESTAMP WHERE product_id = ?";
    $params_product[] = $user_id;
    $params_product[] = $product_id;

    $sql_product = rtrim($sql_product, ", ");

    $stmt_product = $conn->prepare($sql_product);

    if (!$stmt_product) {
        return createResponse("Erro na preparação da declaração SQL: " . $conn->error, 500);
    }

    $bind_types_product = str_repeat("s", count($params_product));

    $stmt_product->bind_param($bind_types_product, ...$params_product);

    if (!$stmt_product->execute()) {
        $stmt_product->close();
        return createResponse("Erro ao atualizar o produto na tabela: " . $conn->error, 500);
    }

    $stmt_product->close();

    $sql_description = "UPDATE " . PREFIX . "product_description SET ";
    $params_description = array();

    if ($name !== null) {
        $sql_description .= "name = ?, ";
        $params_description[] = $name;
    }
    if ($description !== null) {
        $sql_description .= "description = ?, ";
        $params_description[] = $description;
    }
    if ($tags !== null) {
        $sql_description .= "tags = ?, ";
        $params_description[] = $tags;
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
    if ($description_resume !== null) {
        $sql_description .= "description_resume = ?, ";
        $params_description[] = $description_resume;
    }
    $sql_description .= "updated_at = CURRENT_TIMESTAMP WHERE product_id = ?";
    $params_description[] = $product_id;

    $sql_description = rtrim($sql_description, ", ");

    $stmt_description = $conn->prepare($sql_description);

    if (!$stmt_description) {
        return createResponse("Erro na preparação da declaração SQL: " . $conn->error, 500);
    }

    $bind_types_description = str_repeat("s", count($params_description));

    $stmt_description->bind_param($bind_types_description, ...$params_description);

    if (!$stmt_description->execute()) {
        $stmt_description->close();
        return createResponse("Erro ao atualizar a descrição do produto na tabela: " . $conn->error, 500);
    }

    $stmt_description->close();
    
    return createResponse("Produto atualizado com sucesso.", 200);
}

function deleteProductFromDatabase($user_id, $product_id) {
    global $conn;

    $sql_image = "DELETE FROM " . PREFIX . "product_image WHERE product_id = ?";
    $stmt_image = $conn->prepare($sql_image);
    $stmt_image->bind_param("i", $product_id);

    $stmt_image->execute();

    if ($stmt_image->affected_rows <= 0 && $stmt_image->errno != 0) {
        $stmt_image->close();
        return createResponse("Erro ao excluir as imagens do produto.", 500);
    }

    $sql_stock = "DELETE FROM " . PREFIX . "product_attribute_value WHERE product_id = ?";
    $stmt_stock = $conn->prepare($sql_stock);
    $stmt_stock->bind_param("i", $product_id);

    $stmt_stock->execute();

    if ($stmt_stock->affected_rows <= 0 && $stmt_stock->errno != 0) {
        $stmt_stock->close();
        return createResponse("Erro ao excluir o estoque do produto.", 500);
    }

    $sql_description = "DELETE FROM " . PREFIX . "product_description WHERE product_id = ?";
    $stmt_description = $conn->prepare($sql_description);
    $stmt_description->bind_param("i", $product_id);

    $stmt_description->execute();

    if ($stmt_description->affected_rows <= 0 && $stmt_description->errno != 0) {
        $stmt_description->close();
        return createResponse("Erro ao excluir a descrição do produto.", 500);
    }

    $sql_product = "DELETE FROM " . PREFIX . "product WHERE product_id = ? ";
    $stmt_product = $conn->prepare($sql_product);
    $stmt_product->bind_param("i", $product_id);

    $stmt_product->execute();

    if ($stmt_product->affected_rows > 0) {
        insertLog($user_id, "product_id=$product_id", "deleted");
        
        $stmt_product->close();
        return createResponse("Produto, estoque e imagens excluídos com sucesso.", 200);
    } else {
        $stmt_product->close();
        return createResponse("Erro ao excluir o produto.", 500);
    }
}

function saveImageToDatabase($user_id, $product_id, $image_name, $image_url, $alt = null) {
    global $conn;

    $get_next_so = "SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_so FROM " . PREFIX . "product_image WHERE product_id = ?";
    $stmt_get_next_so = $conn->prepare($get_next_so);
    $stmt_get_next_so->bind_param("i", $product_id);
    $stmt_get_next_so->execute();
    $result_next_so = $stmt_get_next_so->get_result();
    $next_so_row = $result_next_so->fetch_assoc();
    $next_so = $next_so_row['next_so'];

    $sql = "INSERT INTO " . PREFIX . "product_image (product_id, name, url, alt, sort_order, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return createResponse("Erro na preparação da declaração SQL: " . $conn->error, 500);
    }

    $stmt->bind_param("isssii", $product_id, $image_name, $image_url, $alt, $next_so, $user_id);

    if (!$stmt->execute()) {
        $stmt->close();
        return createResponse("Erro ao salvar a imagem no banco de dados: " . $stmt->error, 500);
    }

    $stmt->close();

    return createResponse("Imagem salva no banco de dados com sucesso.", 200);
}

function deleteProductImageFromDatabase($user_id, $product_id, $image_id) {
    global $conn;

    $sql = "DELETE FROM " . PREFIX . "product_image WHERE product_id = ? AND image_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $product_id, $image_id);

    if ($stmt->execute()) {
        insertLog($user_id, "image_id=$image_id", "deleted");
        $stmt->close();
        $conn->close();
        return array("status" => 200, "response" => "Imagem do produto excluída com sucesso.");
    } else {
        $stmt->close();
        $conn->close();
        return array("status" => 500, "response" => "Erro ao excluir a imagem do produto: " . $conn->error);
    }
}

?>
