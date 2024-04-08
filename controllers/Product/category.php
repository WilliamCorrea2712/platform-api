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

            addCategoryToDatabase($user_id, $name, $description, $image, $parent_id, $meta_title, $meta_description, $meta_keyword, $sort_order, $status);

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

function getCategories($category_id = null) {
  $result = getAllCategories($category_id);

  if (!empty($result)) {
      http_response_code(200);
      echo json_encode($result, JSON_UNESCAPED_UNICODE);
  }
}

function editCategory($user_id) {
  if ($_SERVER["REQUEST_METHOD"] == "PATCH") { 
      $data = json_decode(file_get_contents("php://input"), true);
      
      if (isset($data['category_id'])) {
          $category_id = $data['category_id'];
          
          if (!categoryExists($category_id)) {
              http_response_code(404);
              echo json_encode(array("message" => "Categoria não encontrada."), JSON_UNESCAPED_UNICODE);
              return;
          }

          if (count($data) <= 1) {
              http_response_code(400);
              echo json_encode(array("message" => "Nenhum dado a ser alterado foi fornecido."), JSON_UNESCAPED_UNICODE);
              return;
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

          $result = editCategoryInDatabase($user_id, $category_id, $name, $description, $image, $parent_id, $meta_title, $meta_description, $meta_keyword, $sort_order, $status);

          http_response_code($result['status']);
          echo json_encode($result['response'], JSON_UNESCAPED_UNICODE);
      } else {
          http_response_code(400);
          echo json_encode(array("message" => "O ID da categoria é obrigatório."), JSON_UNESCAPED_UNICODE);
      }
  } else {
      http_response_code(405);
      echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
  }
}

function deleteCategory($user_id) {
  if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
      $data = json_decode(file_get_contents("php://input"), true);
      
      if (isset($data['category_id'])) {
          $category_id = $data['category_id'];

          if (!categoryExists($category_id)) {
              http_response_code(404);
              echo json_encode(array("message" => "Categoria não encontrada."), JSON_UNESCAPED_UNICODE);
              return;
          }

          $result = deleteCategoryFromDatabase($user_id, $category_id);

          if ($result['status'] === 200) {
              http_response_code(200);
              echo json_encode(array("message" => "Categoria excluída com sucesso."), JSON_UNESCAPED_UNICODE);
          } else {
              http_response_code(500);
              echo json_encode(array("error" => "Erro ao excluir categoria: " . $result['response']['error']), JSON_UNESCAPED_UNICODE);
          }
      } else {
          http_response_code(400);
          echo json_encode(array("message" => "ID da categoria não fornecido."), JSON_UNESCAPED_UNICODE);
      }
  } else {
      http_response_code(405);
      echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
  }
}


?>