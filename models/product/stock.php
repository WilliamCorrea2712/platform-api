<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once __DIR__ . '/../../global/helpers.php';
require_once(__DIR__ . '/../../config.php');

class ProductStockModel {
  private static $insertedId = null;
  
  public function addStockOptions($options) {
      if (empty($options)) {
          return createResponse("Nenhuma opção de estoque foi fornecida.", 400);
      }

      $first_option = $options[0];
      if (!isset($first_option['product_id'])) {
          return createResponse("O campo 'product_id' é obrigatório.", 400);
      }
      $product_id = $first_option['product_id'];

      if (!itemExists("product", "product_id", $product_id)) {
        return createResponse("Produto não encontrado.", 404);
      }

      $group_id = null;
      if (isset($first_option['group_id'])) {
          $group_id = $first_option['group_id'];
      }

      foreach ($options as $option) {
          if (!isset($option['attribute_name'], $option['attribute_value'], $option['quantity'])) {
              return createResponse("Os campos 'attribute_name', 'attribute_value' e 'quantity' são obrigatórios para cada opção.", 400);
          }

          $attribute_name = $option['attribute_name'];
          $attribute_value = $option['attribute_value'];
          $quantity = $option['quantity'];

          $attribute_id = $this->getAttributeId($attribute_name);
          if (!$attribute_id) {
              $attribute_id = $this->addAttribute($attribute_name);
          }

          if ($this->checkAttributeValueExists($product_id, $attribute_id, $attribute_value)) {
              return createResponse("O valor do atributo já existe para este produto.", 400);
          }

          if(isset($parent_attribute_id)){
            $this->addAttributeValue($product_id, $attribute_id, $attribute_value, $quantity, $group_id, $parent_attribute_id);
          } else {
            $parent_attribute_id = $this->addAttributeValue($product_id, $attribute_id, $attribute_value, $quantity, $group_id);
          }          
      }

      return createResponse("Opções de estoque adicionadas com sucesso.", 201);
  }

  private function checkAttributeValueExists($product_id, $attribute_id, $value) {
    global $conn;

    $query = "SELECT COUNT(*) as count FROM " . PREFIX . "product_attribute_value WHERE product_id = ? AND attribute_id = ? AND value = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $product_id, $attribute_id, $value);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['count'] > 0;
  }

    private function getAttributeId($name) {
        global $conn;

        $query = "SELECT id FROM " . PREFIX . "product_attribute WHERE name = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['id'];
        } else {
            return false;
        }
    }

    private function addAttribute($name) {
        global $conn;

        $query = "INSERT INTO " . PREFIX . "product_attribute (name) VALUES (?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $name);
        $stmt->execute();

        return $stmt->insert_id;
    }

    public function addAttributeValue($product_id, $attribute_id, $value, $quantity, $group_id = null, $parent_attribute_id = null) {
      global $conn;

      $query = "INSERT INTO " . PREFIX . "product_attribute_value (product_id, attribute_id, value, quantity, group_id, parent_attribute_id) VALUES (?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("iisiii", $product_id, $attribute_id, $value, $quantity, $group_id, $parent_attribute_id);
      $stmt->execute();

      if (self::$insertedId === null) {
          self::$insertedId = $stmt->insert_id;
      }

      $stmt->close();
      return self::$insertedId;
  }

    public function editStockOptions($product_id, $id, $attribute_id, $quantity) {
        global $conn;
    
        if (!$this->stockOptionExists($product_id, $id, $attribute_id)) {
            return createResponse("Opção de estoque não encontrada para o produto especificado.", 404);
        }    
        
        if (!is_int($quantity) || $quantity <= 0) {
          return createResponse("A quantidade fornecida deve ser um número inteiro positivo.", 400);
        }
        
        $current_quantity = $this->getCurrentQuantity($product_id, $id, $attribute_id);

        if($current_quantity['parent_attribute_id'] === null){
          return createResponse("Opção de estoque não pode ser alterada, verifique o id para não alterar o atributo pai.", 400);
        }

        $new_quantity = $current_quantity['quantity'] - $quantity;
    
        if ($new_quantity < 0) {
            return createResponse("A quantidade fornecida é maior do que a quantidade disponível no estoque.", 400);
        }
    
        $sql = "UPDATE " . PREFIX . "product_attribute_value SET quantity = ? WHERE product_id = ? AND id = ? AND attribute_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $new_quantity, $product_id, $id, $attribute_id);
        $stmt->execute(); 
        
        if ($stmt->affected_rows > 0) {
          $current_qtd = $this->getCurrentQuantityParent($product_id, $current_quantity['parent_attribute_id'], $attribute_id);
          $new_qtd = $current_qtd['quantity'] - $quantity;
      
          if ($new_qtd < 0) {
              return createResponse("A quantidade fornecida é maior do que a quantidade disponível no estoque.", 400);
          }
      
          $sqlParent = "UPDATE " . PREFIX . "product_attribute_value SET quantity = ? WHERE product_id = ? AND id = ? ";
          $stmtParent = $conn->prepare($sqlParent);
          $stmtParent->bind_param("iii", $new_qtd, $product_id, $current_quantity['parent_attribute_id']);
          $stmtParent->execute(); 

          if ($stmtParent->affected_rows > 0) {
            return createResponse("Opção de estoque atualizada com sucesso.", 200);
          } else {
            return createResponse("Falha ao atualizar a opção de estoque pai.", 500);
          }
        } else {
            return createResponse("Falha ao atualizar a opção de estoque.", 500);
        }
    }
  
    private function stockOptionExists($product_id, $id, $attribute_id) {
        global $conn;

        $sql = "SELECT * FROM " . PREFIX . "product_attribute_value WHERE product_id = ? AND id = ? AND attribute_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $product_id, $id, $attribute_id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }

    private function getCurrentQuantity($product_id, $id, $attribute_id) {
        global $conn;
    
        $sql = "SELECT quantity, parent_attribute_id FROM " . PREFIX . "product_attribute_value WHERE product_id = ? AND id = ? AND attribute_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $product_id, $id, $attribute_id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($quantity, $parent_attribute_id);
            $stmt->fetch();
            $stmt->free_result();
            $stmt->close();
            return array('quantity' => $quantity, 'parent_attribute_id' => $parent_attribute_id);
        } else {
            return null;
        }
    }

    private function getCurrentQuantityParent($product_id, $id) {
      global $conn;
  
      $sql = "SELECT quantity, parent_attribute_id FROM " . PREFIX . "product_attribute_value WHERE product_id = ? AND id = ? ";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ii", $product_id, $id);
      $stmt->execute();
      $stmt->store_result();
      
      if ($stmt->num_rows > 0) {
          $stmt->bind_result($quantity, $parent_attribute_id);
          $stmt->fetch();
          $stmt->free_result();
          $stmt->close();
          return array('quantity' => $quantity, 'parent_attribute_id' => $parent_attribute_id);
      } else {
          return null;
      }
  }
}
?>
