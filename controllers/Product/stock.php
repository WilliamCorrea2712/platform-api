<?php
require_once __DIR__ . '/../../models/product/stock.php';
require_once __DIR__ . '/../../global/helpers.php';

function addStockOptions($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!empty($data)) {
            $controller = new ProductAttributeController();
            return $controller->processAttributes($data);
        } else {
            return createResponse("Nenhum dado foi enviado.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function editStockOptions($user_id) {
  if ($_SERVER["REQUEST_METHOD"] == "PUT") {
      $data = json_decode(file_get_contents("php://input"), true);

      if (!isset($data['product_id']) || !isset($data['id']) || !isset($data['attribute_id']) || !isset($data['quantity'])) {
          return createResponse("Os campos 'product_id', 'id', 'attribute_id' e 'quantity' são obrigatórios para editar uma opção de estoque.", 400);
      }

      $product_id = $data['product_id'];
      $id = $data['id'];
      $attribute_id = $data['attribute_id'];
      $quantity = $data['quantity'];

      $controller = new ProductAttributeController();
      return $controller->updateStockOption($product_id, $id, $attribute_id, $quantity);
  } else {
      return createResponse("Método não permitido.", 405);
  }
}

class ProductAttributeController {
    public function processAttributes($options) {
        if (empty($options)) {
            return createResponse("Nenhuma opção de estoque foi fornecida.", 400);
        }

        $firstQuantity = $options[0]['quantity'];
        $otherQuantities = array_column(array_slice($options, 1), 'quantity');
        $totalQuantity = array_sum($otherQuantities);

        if ($firstQuantity !== $totalQuantity) {
            return createResponse("A quantidade do tipo não é igual à soma das quantidades dos atributos.", 400);
        }

        $model = new ProductStockModel();
        return $model->addStockOptions($options);
    }

    public function updateStockOption($product_id, $id, $attribute_id, $quantity) {
      $model = new ProductStockModel();
      return $model->editStockOptions($product_id, $id, $attribute_id, $quantity);
    }
}
?>
