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
            $sort_order = isset($data['sort_order']) ? $data['sort_order'] : null;
            $minimum = isset($data['minimum']) ? $data['minimum'] : null;
            $status = isset($data['status']) ? $data['status'] : null;
            $name = $data['name'];
            $description = $data['description'];
            $tags = isset($data['tags']) ? $data['tags'] : '';
            $meta_title = isset($data['meta_title']) ? $data['meta_title'] : '';
            $meta_description = isset($data['meta_description']) ? $data['meta_description'] : '';
            $meta_keyword = isset($data['meta_keyword']) ? $data['meta_keyword'] : '';
            $description_resume = isset($data['description_resume']) ? $data['description_resume'] : '';

            $result = addProductToDatabaseHelper($user_id, $brand_id, $categories, $price, $cost_price, $weight, $length, $width, $height, $sku, $sort_order, $minimum, $status, $name, $description, $tags, $meta_title, $meta_description, $meta_keyword, $description_resume);

            return $result;
        } else {
            return createResponse("Os campos 'name', 'description', 'price' e 'weight' são obrigatórios.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function getProducts($product_id = null) {
    $products = getAllProducts($product_id);

    if (!empty($products)) {
        return createResponse(array('products' => $products), 200); 
    } else {
        return createResponse(array('error' => "Nenhum produto encontrado."), 404);
    }
}

function editProduct($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "PATCH") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['product_id'])) {
            $product_id = $data['product_id'];
            
            if (!itemExists("product", "product_id", $product_id)) {
                return createResponse("Produto não encontrado.", 404);
            }

            if (count($data) <= 1) {
                return createResponse("Nenhum dado a ser alterado foi fornecido.", 400);
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

            return createResponse($result['response'], $result['status']);
        } else {
            return createResponse("O ID do produto é obrigatório.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function deleteProduct($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['product_id'])) {
            $product_id = $data['product_id'];
            
            if (!itemExists("product", "product_id", $product_id)) {
                return createResponse("Produto não encontrado.", 404);
            }

            $result = deleteProductFromDatabase($user_id, $product_id);
            
            if ($result !== null && isset($result['response']) && isset($result['status'])) {
                return createResponse($result['response'], $result['status']);
            } elseif ($result !== null && isset($result['error'])) {
                return createResponse($result['error'], 500);
            }            
        } else {
            return createResponse("O ID do produto é obrigatório.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function addProductImages($user_id) {
    $json_data = file_get_contents('php://input');
    $request_data = json_decode($json_data, true);

    if ($request_data === null) {
        return createResponse("Erro ao decodificar o JSON.", 400);
    }

    if (!isset($request_data['product_id'])) {
        return createResponse("O campo 'product_id' é obrigatório.", 400);
    }

    $product_id = $request_data['product_id'];

    if (!itemExists("product", "product_id", $product_id)) {
        return createResponse("O produto não foi encontrado.", 404);
    }

    if (empty($request_data['images'])) {
        return createResponse("Nenhuma imagem foi enviada.", 400);
    }

    $upload_dir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR;

    $errors = array();
    $max_images = 5;
    $qtdImagesRequest = count($request_data['images']);
    $qtdImage = getProductImages($product_id) + $qtdImagesRequest;

    foreach ($request_data['images'] as $image_data) {
        $image_name = $image_data['name'];
        $image_tmp_name = $image_data['tmp_name'];
        $image_type = $image_data['type'];
        $image_error = $image_data['error'];
        $image_size = $image_data['size'];

        if ($image_error !== UPLOAD_ERR_OK) {
            $errors[] = "Erro ao fazer upload da imagem '{$image_name}'. Código de erro: {$image_error}.";
            continue;
        }

       if ($qtdImage > $max_images) {
            $errors[] = "Numero maximo de imagens por produto estourado: {$max_images} imagens.";
            continue;
        }

        $image_extension = pathinfo($image_name, PATHINFO_EXTENSION);
        $image_unique_name = $product_id . "_" . uniqid() . "." . $image_extension;

        $destination = $upload_dir . $image_unique_name;

        if (!copy($image_tmp_name, $destination)) {
            $errors[] = "Erro ao copiar a imagem '{$image_name}' para o diretório de destino.";
            continue;
        }
        unlink($image_tmp_name);
        
        $image_url = 'public/images/' . $image_unique_name;
        saveImageToDatabase($user_id, $product_id, $image_unique_name, $image_url, pathinfo($image_name, PATHINFO_FILENAME));
    }

    if (!empty($errors)) {
        return createResponse($errors, 500);
    }

    return createResponse("Imagens adicionadas com sucesso!", 200);
}

function deleteProductImages($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['product_id']) && isset($data['image_id'])) {
            $product_id = $data['product_id'];
            $image_id = $data['image_id'];
            
            if (!itemExists("product_image", "image_id", $image_id)) {
                return createResponse("Imagem não encontrada.", 404);
            }

            $result = deleteProductImageFromDatabase($user_id, $product_id, $image_id);

            return createResponse($result['response'], $result['status']);
        } else {
            return createResponse("O ID da imagem e o ID do produto são obrigatórios.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

?>
