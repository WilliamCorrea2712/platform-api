<?php
require_once __DIR__ . "/../../models/product/product.php";
require_once __DIR__ . '/../../global/helpers.php';

function addProduct($user_id) {
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $data = json_decode(file_get_contents("php://input"), true);

      if ($data !== null &&
          isset($data['name']) &&
          isset($data['description']) &&
          isset($data['price']) &&
          isset($data['weight'])
      ) {
          $brand_id = isset($data['brand_id']) ? $data['brand_id'] : null;
          $categories = isset($data['categories']) ? json_encode($data['categories']) : null;
          $price = $data['price'];
          $cost_price = isset($data['cost_price']) ? $data['cost_price'] : null;
          $weight = $data['weight'];
          $length = isset($data['length']) ? $data['length'] : null;
          $width = isset($data['width']) ? $data['width'] : null;
          $height = isset($data['height']) ? $data['height'] : null;
          $sku = isset($data['sku']) ? $data['sku'] : null;
          $sort_order = isset($data['status']) ? $data['sort_order'] : null;
          $minimum = isset($data['minimum']) ? $data['minimum'] : null;
          $status = isset($data['status']) ? $data['status'] : null;
          $name = $data['name'];
          $description = $data['description'];
          $tags = isset($data['tags']) ? $data['tags'] : '';
          $meta_title = isset($data['meta_title']) ? $data['meta_title'] : '';
          $meta_description = isset($data['meta_description']) ? $data['meta_description'] : '';
          $meta_keyword = isset($data['meta_keyword']) ? $data['meta_keyword'] : '';
          $description_resume = isset($data['description_resume']) ? $data['description_resume'] : '';

          $product_id = addProductToDatabaseHelper($user_id, $brand_id, $categories, $price, $cost_price, $weight, $length, $width, $height, $sku, $sort_order, $minimum, $status, $name, $description, $tags, $meta_title, $meta_description, $meta_keyword, $description_resume);

          if (is_numeric($product_id)) {
              http_response_code(201);
              echo json_encode(array("message" => "Produto adicionado com sucesso.", "product_id" => $product_id), JSON_UNESCAPED_UNICODE);
          } else {
              http_response_code(500);
              echo json_encode(array("error" => $product_id), JSON_UNESCAPED_UNICODE);
          }
      } else {
          http_response_code(400);
          echo json_encode(array("error" => "Os campos 'name', 'description', 'price' e 'weight' são obrigatórios."), JSON_UNESCAPED_UNICODE);
      }
  } else {
      http_response_code(405);
      echo json_encode(array("error" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
  }
}

function getProducts($product_id = null) {
  $result = getAllProducts($product_id);

  if (!empty($result)) {
      http_response_code(200);
      echo json_encode($result, JSON_UNESCAPED_UNICODE);
  }
}

function editProduct($user_id) {
  if ($_SERVER["REQUEST_METHOD"] == "PATCH") { 
      $data = json_decode(file_get_contents("php://input"), true);
      
      if (isset($data['product_id'])) {
          $product_id = $data['product_id'];
          
          if (itemExists("product", "product_id", $product_id)) {
              http_response_code(404);
              echo json_encode(array("message" => "Produto não encontrado."), JSON_UNESCAPED_UNICODE);
              return;
          }

          if (count($data) <= 1) {
              http_response_code(400);
              echo json_encode(array("message" => "Nenhum dado a ser alterado foi fornecido."), JSON_UNESCAPED_UNICODE);
              return;
          }

          $brand_id = isset($data['brand_id']) ? $data['brand_id'] : null;
          $categories = isset($data['categories']) ? json_encode($data['categories']) : null;
          $price = isset($data['price']) ? $data['price'] : null;
          $cost_price = isset($data['cost_price']) ? $data['cost_price'] : null;
          $weight = isset($data['weight']) ? $data['weight'] : null;
          $length = isset($data['length']) ? $data['length'] : null;
          $width = isset($data['width']) ? $data['width'] : null;
          $height = isset($data['height']) ? $data['height'] : null;
          $sku = isset($data['sku']) ? $data['sku'] : null;
          $sort_order = isset($data['sort_order']) ? $data['sort_order'] : null;
          $minimum = isset($data['minimum']) ? $data['minimum'] : null;
          $status = isset($data['status']) ? $data['status'] : null;
          $name = isset($data['name']) ? $data['name'] : null;
          $description = isset($data['description']) ? $data['description'] : null;
          $tags = isset($data['tags']) ? $data['tags'] : '';
          $meta_title = isset($data['meta_title']) ? $data['meta_title'] : '';
          $meta_description = isset($data['meta_description']) ? $data['meta_description'] : '';
          $meta_keyword = isset($data['meta_keyword']) ? $data['meta_keyword'] : '';
          $description_resume = isset($data['description_resume']) ? $data['description_resume'] : '';

          $result = editProductInDatabase($user_id, $product_id, $brand_id, $categories, $price, $cost_price, $weight, $length, $width, $height, $sku, $sort_order, $minimum, $status, $name, $description, $tags, $meta_title, $meta_description, $meta_keyword, $description_resume);

          http_response_code($result['status']);
          echo json_encode($result['response'], JSON_UNESCAPED_UNICODE);
      } else {
          http_response_code(400);
          echo json_encode(array("message" => "O ID do produto é obrigatório."), JSON_UNESCAPED_UNICODE);
      }
  } else {
      http_response_code(405);
      echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
  }
}

function deleteProduct($user_id) {
  if ($_SERVER["REQUEST_METHOD"] == "DELETE") { 
      $data = json_decode(file_get_contents("php://input"), true);
      
      if (isset($data['product_id'])) {
          $product_id = $data['product_id'];
          
          if (itemExists("product", "product_id", $product_id)) {
              http_response_code(404);
              echo json_encode(array("message" => "Produto não encontrado."), JSON_UNESCAPED_UNICODE);
              return;
          }

          $result = deleteProductFromDatabase($user_id, $product_id);

          http_response_code($result['status']);
          echo json_encode($result['response'], JSON_UNESCAPED_UNICODE);
      } else {
          http_response_code(400);
          echo json_encode(array("message" => "O ID do produto é obrigatório."), JSON_UNESCAPED_UNICODE);
      }
  } else {
      http_response_code(405);
      echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
  }
}

function addProductImages($user_id) {
  if ($_SERVER["REQUEST_METHOD"] == "POST") { 
      if (isset($_POST['product_id'])) {
          $product_id = $_POST['product_id'];

          if (itemExists("product", "product_id", $product_id)) {
              http_response_code(404);
              echo json_encode(array("error" => "O produto não foi encontrado."), JSON_UNESCAPED_UNICODE);
              return;
          }

          if (empty($_FILES['images'])) {
              http_response_code(400);
              echo json_encode(array("error" => "Nenhuma imagem foi enviada."), JSON_UNESCAPED_UNICODE);
              return;
          }

          $upload_dir = __DIR__ . "/../../public/images/";

          $errors = array();
          $max_images = 5;

          foreach ($_FILES['images']['name'] as $key => $image_name) {
              $image_tmp_name = $_FILES['images']['tmp_name'][$key];
              $image_type = $_FILES['images']['type'][$key];
              $image_error = $_FILES['images']['error'][$key];
              $image_size = $_FILES['images']['size'][$key];

              if ($image_error !== UPLOAD_ERR_OK) {
                  $errors[] = "Erro ao fazer upload da imagem '{$image_name}'. Código de erro: {$image_error}.";
                  continue;
              }

              if (!is_valid_image($image_tmp_name, $image_type)) {
                  $errors[] = "Tipo de arquivo inválido: '{$image_type}'.";
                  continue;
              }

              if (getProductImages($product_id) >= $max_images) {
                  $errors[] = "Número máximo de imagens por produto estourado: {$max_images}.";
                  break;
              }

              $image_extension = pathinfo($image_name, PATHINFO_EXTENSION);
              $image_unique_name = $product_id . "_" . uniqid() . "." . $image_extension;

              if (!move_uploaded_file($image_tmp_name, $upload_dir . $image_unique_name)) {
                  $errors[] = "Erro ao mover a imagem '{$image_name}' para o diretório de destino.";
                  continue;
              }

              $image_url = 'public/images/' . $image_unique_name;
              $result = saveImageToDatabase($user_id, $product_id, $image_unique_name, $image_url, pathinfo($image_name, PATHINFO_FILENAME));

              if ($result['status'] !== 200) {
                  $errors[] = $result['response']['error'];
              }
          }

          if (!empty($errors)) {
              http_response_code(500);
              echo json_encode(array("error" => $errors), JSON_UNESCAPED_UNICODE);
              return;
          }

          http_response_code(200);
          echo json_encode(array("message" => "Imagens adicionadas com sucesso!"), JSON_UNESCAPED_UNICODE);
      } else {
          http_response_code(400);
          echo json_encode(array("error" => "O campo 'product_id' é obrigatório."), JSON_UNESCAPED_UNICODE);
      }
  } else {
      http_response_code(405);
      echo json_encode(array("error" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
  }
}

?>
