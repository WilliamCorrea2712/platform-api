<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once __DIR__ . '/../../global/helpers.php';
require_once __DIR__ . '/../../global/logs.php';
require_once(__DIR__ . '/../../config.php');

function addProductToDatabaseHelper($user_id, $brand_id, $categories, $price, $cost_price, $weight, $length, $width, $height, $sku, $sort_order, $minimum, $status, $name, $description, $tags, $meta_title, $meta_description, $meta_keyword, $description_resume) {
  global $conn;

  $sql = "INSERT INTO " . PREFIX . "product 
          (brand_id, categories, price, cost_price, weight, length, width, height, sku, sort_order minimum, status, created_by_user_id, updated_by_user_id) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
      return $product_id;
  } else {
      $stmt->close();
      $conn->close();
      return "Erro ao inserir na tabela " . PREFIX . "product.";
  }
}

function getAllProducts($product_id = null) {
  global $conn;

  $sql = "SELECT p.*, pd.name as product_name, pd.description as product_description, pd.meta_title, pd.meta_description, pd.meta_keyword
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
      http_response_code(500);
      echo json_encode(array("error" => "Erro ao buscar produtos: " . $conn->error), JSON_UNESCAPED_UNICODE);
      return;
  }

  $result = $stmt->get_result();
  $products = array();

  while ($row = $result->fetch_assoc()) {
      $product_id = $row['product_id'];

      $products[$product_id] = array(
          'product_id' => $product_id,
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
          'meta_title' => $row['meta_title'],
          'meta_description' => $row['meta_description'],
          'meta_keyword' => $row['meta_keyword'],
      );
  }

  http_response_code(200);
  echo json_encode(array_values($products), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

  $stmt->close();
  $conn->close();
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
      return array("status" => 500, "response" => array("error" => "Erro na preparação da declaração SQL: " . $conn->error), JSON_UNESCAPED_UNICODE);
  }

  $bind_types_product = str_repeat("s", count($params_product));

  $stmt_product->bind_param($bind_types_product, ...$params_product);

  if (!$stmt_product->execute()) {
      $stmt_product->close();
      $conn->close();
      return array("status" => 500, "response" => array("error" => "Erro ao atualizar o produto na tabela: " . $conn->error), JSON_UNESCAPED_UNICODE);
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
      return array("status" => 500, "response" => array("error" => "Erro na preparação da declaração SQL: " . $conn->error), JSON_UNESCAPED_UNICODE);
  }

  $bind_types_description = str_repeat("s", count($params_description));

  $stmt_description->bind_param($bind_types_description, ...$params_description);

  if (!$stmt_description->execute()) {
      $stmt_description->close();
      $conn->close();
      return array("status" => 500, "response" => array("error" => "Erro ao atualizar a descrição do produto na tabela: " . $conn->error), JSON_UNESCAPED_UNICODE);
  }

  $stmt_description->close();
  $conn->close();
  
  return array("status" => 200, "response" => array("message" => "Produto atualizado com sucesso."), JSON_UNESCAPED_UNICODE);
}

function deleteProductFromDatabase($user_id, $product_id) {
  global $conn;

  $sql_description = "DELETE FROM " . PREFIX . "product_description WHERE product_id = ?";
  $stmt_description = $conn->prepare($sql_description);
  $stmt_description->bind_param("i", $product_id);
  
  $stmt_description->execute();

  if ($stmt_description->affected_rows <= 0 && $stmt_description->errno != 0) {
      $stmt_description->close();
      $conn->close();
      return array("status" => 500, "response" => array("error" => "Erro ao excluir a descrição do produto."), JSON_UNESCAPED_UNICODE);
  }

  $sql_product = "DELETE FROM " . PREFIX . "product WHERE product_id = ? ";
  $stmt_product = $conn->prepare($sql_product);
  $stmt_product->bind_param("i", $product_id);
  
  $stmt_product->execute();

  if ($stmt_product->affected_rows > 0) {
      insertLog($user_id, "product_id=$product_id", "deleted");
      $stmt_product->close();
      $conn->close();
      return array("status" => 200, "response" => array("message" => "Produto excluído com sucesso."), JSON_UNESCAPED_UNICODE);
  } else {
      $stmt_product->close();
      $conn->close();
      return array("status" => 500, "response" => array("error" => "Erro ao excluir o produto."), JSON_UNESCAPED_UNICODE);
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
      return array("status" => 500, "response" => array("error" => "Erro na preparação da declaração SQL: " . $conn->error));
  }

  $stmt->bind_param("isssii", $product_id, $image_name, $image_url, $alt, $next_so, $user_id);

  if (!$stmt->execute()) {
      $stmt->close();
      return array("status" => 500, "response" => array("error" => "Erro ao salvar a imagem no banco de dados: " . $stmt->error));
  }

  $stmt->close();

  return array("status" => 200, "response" => array("message" => "Imagem salva no banco de dados com sucesso."));
}

?>

