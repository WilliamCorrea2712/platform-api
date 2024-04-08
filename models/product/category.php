<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once(__DIR__ . '/../../config.php');

function addCategoryToDatabase($name, $description, $image, $parent_id, $meta_title, $meta_description, $meta_keyword, $sort_order, $status) {
  global $conn;

  $sql = "INSERT INTO api_category (image, parent_id, sort_order, status) VALUES (?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("siis", $image, $parent_id, $sort_order, $status);
  $stmt->execute();

  if ($stmt->affected_rows > 0) {
      $category_id = $stmt->insert_id;

      $sql = "INSERT INTO api_category_description (category_id, name, description, meta_title, meta_description, meta_keyword) VALUES (?, ?, ?, ?, ?, ?)";
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
          return "Erro ao inserir na tabela api_category_description.";
      }
  } else {
      $stmt->close();
      $conn->close();
      return "Erro ao inserir na tabela api_category.";
  }
}
