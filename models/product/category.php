<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once __DIR__ . '/../../global/helpers.php';
require_once __DIR__ . '/../../global/logs.php';
require_once(__DIR__ . '/../../config.php');

function addCategoryToDatabase($user_id, $name, $description, $image, $parent_id, $meta_title, $meta_description, $meta_keyword, $sort_order, $status) {
  global $conn;

  $sql = "INSERT INTO " . PREFIX . "category (image, parent_id, sort_order, status, created_by_user_id) VALUES (?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("siisi", $image, $parent_id, $sort_order, $status, $user_id);
  $stmt->execute();

  if ($stmt->affected_rows > 0) {
      $category_id = $stmt->insert_id;

      $sql = "INSERT INTO " . PREFIX . "category_description (category_id, name, description, meta_title, meta_description, meta_keyword) VALUES (?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("isssss", $category_id, $name, $description, $meta_title, $meta_description, $meta_keyword);
      $stmt->execute();

      if ($stmt->affected_rows > 0) {
          $stmt->close();
          $conn->close();
          return $category_id;
      } else {
          $stmt->close();
          $conn->close();
          return "Erro ao inserir na tabela " . PREFIX . "category_description.";
      }
  } else {
      $stmt->close();
      $conn->close();
      return "Erro ao inserir na tabela " . PREFIX . "category.";
  }
}

function getAllCategories($category_id = null) {
  global $conn;

  $sql = "SELECT c.*, cd.name, cd.description, cd.meta_title, cd.meta_description, cd.meta_keyword
          FROM " . PREFIX . "category c
          LEFT JOIN " . PREFIX . "category_description cd ON c.category_id = cd.category_id";

  if ($category_id !== null) {
      $sql .= " WHERE c.category_id = ?";
  }

  $stmt = $conn->prepare($sql);

  if ($category_id !== null) {
      $stmt->bind_param("i", $category_id);
  }

  if (!$stmt->execute()) {
      http_response_code(500);
      echo json_encode(array("error" => "Erro ao buscar categorias: " . $conn->error), JSON_UNESCAPED_UNICODE);
      return;
  }

  $result = $stmt->get_result();
  $categories = array();

  while ($row = $result->fetch_assoc()) {
      $category_id = $row['category_id'];

      $categories[$category_id] = array(
          'category_id' => $category_id,
          'image' => $row['image'],
          'parent_id' => $row['parent_id'],
          'sort_order' => $row['sort_order'],
          'status' => $row['status'],
          'created_at' => $row['created_at'],
          'updated_at' => $row['updated_at'],
          'description' => $row['description'],
          'meta_title' => $row['meta_title'],
          'meta_description' => $row['meta_description'],
          'meta_keyword' => $row['meta_keyword'],
      );
  }

  http_response_code(200);
  echo json_encode(array_values($categories), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

  $stmt->close();
  $conn->close();
}

function editCategoryInDatabase($user_id, $category_id, $name, $description, $image, $parent_id, $meta_title, $meta_description, $meta_keyword, $sort_order, $status) {
  global $conn;

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

  $stmt_category = $conn->prepare($sql_category);

  if (!$stmt_category) {
      return array("status" => 500, "response" => array("error" => "Erro na preparação da declaração SQL: " . $conn->error));
  }

  $bind_types_category = str_repeat("s", count($params_category));

  $stmt_category->bind_param($bind_types_category, ...$params_category);

  if (!$stmt_category->execute()) {
      $stmt_category->close();
      $conn->close();
      return array("status" => 500, "response" => array("error" => "Erro ao atualizar a categoria na tabela api_category: " . $conn->error));
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

  $stmt_description = $conn->prepare($sql_description);

  if (!$stmt_description) {
      return array("status" => 500, "response" => array("error" => "Erro na preparação da declaração SQL: " . $conn->error));
  }

  $bind_types_description = str_repeat("s", count($params_description));

  $stmt_description->bind_param($bind_types_description, ...$params_description);

  if (!$stmt_description->execute()) {
      $stmt_description->close();
      $conn->close();
      return array("status" => 500, "response" => array("error" => "Erro ao atualizar a categoria na tabela api_category_description: " . $conn->error));
  }

  $stmt_description->close();
  $conn->close();
  
  return array("status" => 200, "response" => array("message" => "Categoria atualizada com sucesso."));
}

function deleteCategoryFromDatabase($user_id, $category_id) {
  global $conn;

  $sql_description = "DELETE FROM " . PREFIX . "category_description WHERE category_id = ?";
  $stmt_description = $conn->prepare($sql_description);
  $stmt_description->bind_param("i", $category_id);
  $stmt_description->execute();
  $stmt_description->close();

  $sql_category = "DELETE FROM " . PREFIX . "category WHERE category_id = ?";
  $stmt_category = $conn->prepare($sql_category);
  $stmt_category->bind_param("i", $category_id);
  $stmt_category->execute();

  if ($stmt_category->affected_rows > 0) {
      insertLog($user_id, "category_id=$category_id", "deleted");

      $stmt_category->close();
      $conn->close();
      return array("status" => 200, "response" => array("message" => "Categoria excluída com sucesso."));
  } else {
      $stmt_category->close();
      $conn->close();
      return array("status" => 500, "response" => array("error" => "Erro ao excluir a categoria: " . $conn->error));
  }
}
?>