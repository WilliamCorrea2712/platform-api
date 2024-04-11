<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once __DIR__ . '/../../global/helpers.php';
require_once(__DIR__ . '/../../config.php');

class ProductStockModel {
    private static $insertedId = null;
    
    public function addStockOptions($user_id, $options) {
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

        if(!isset($attribute_id)){
            $attribute_id = $this->getAttributeId($attribute_name);
        } else {
            $attribute_id = $attribute_id;
        }

        if (!$attribute_id) {
            $attribute_id = $this->addAttribute($attribute_name);
        }

        if ($this->checkAttributeValueExists($product_id, $attribute_id, $attribute_value)) {
            return createResponse("O valor do atributo já existe para este produto.", 400);
        }

        if(isset($parent_attribute_id)){
            $this->addAttributeValue($user_id, $product_id, $attribute_id, $attribute_value, $quantity, $group_id, $parent_attribute_id);
        } else {
            $parent_attribute_id = $this->addAttributeValue($user_id, $product_id, $attribute_id, $attribute_value, $quantity, $group_id);
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

    public function addAttributeValue($user_id, $product_id, $attribute_id, $value, $quantity, $group_id = null, $parent_attribute_id = null) {
        global $conn;
    
        $created_at = date('Y-m-d H:i:s');
        $created_by_user_id = $user_id;
    
        $query = "INSERT INTO " . PREFIX . "product_attribute_value 
                  (product_id, attribute_id, value, quantity, group_id, parent_attribute_id, created_at, created_by_user_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iisiiiss", $product_id, $attribute_id, $value, $quantity, $group_id, $parent_attribute_id, $created_at, $created_by_user_id);
        $stmt->execute();
    
        if (self::$insertedId === null) {
            self::$insertedId = $stmt->insert_id;
        }
    
        $stmt->close();
        return self::$insertedId;
    }

    public function editStockOptions($user_id, $product_id, $id, $attribute_id, $quantity, $operation) {
        global $conn;
    
        if (!$this->stockOptionExists($product_id, $id, $attribute_id)) {
            return createResponse("Opção de estoque não encontrada para o produto especificado.", 404);
        }
    
        if (!is_int($quantity) || $quantity <= 0) {
            return createResponse("A quantidade fornecida deve ser um número inteiro positivo.", 400);
        }
    
        $current_quantity_data = $this->getCurrentQuantity($product_id, $id, $attribute_id);
    
        if ($current_quantity_data['parent_attribute_id'] === null) {
            return createResponse("Opção de estoque não pode ser alterada, verifique o ID para não alterar o atributo pai.", 400);
        }
    
        $new_quantity = $current_quantity_data['quantity'];
    
        if ($operation === 'add') {
            $new_quantity += $quantity;
        } elseif ($operation === 'subtract') {
            $new_quantity -= $quantity;
        } else {
            return createResponse("Operação inválida. Use 'add' para adicionar e 'subtract' para subtrair a quantidade de estoque.", 400);
        }
    
        if ($new_quantity < 0) {
            return createResponse("A quantidade fornecida é maior do que a quantidade disponível no estoque.", 400);
        }
    
        $parent_attribute_id = $current_quantity_data['parent_attribute_id'];
    
        $sql = "UPDATE " . PREFIX . "product_attribute_value SET 
                    quantity = CASE WHEN id = ? THEN ? ELSE quantity END, 
                    updated_at = NOW(),
                    updated_by_user_id = ?
                WHERE product_id = ? AND attribute_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiii", $id, $new_quantity, $user_id, $product_id, $attribute_id);
        $stmt->execute();
    
        if ($stmt->affected_rows > 0) {
            $new_parent_quantity = $this->getCurrentQuantityParent($product_id, $parent_attribute_id, $attribute_id)['quantity'];
    
            if ($operation === 'add') {
                $new_parent_quantity += $quantity;
            } elseif ($operation === 'subtract') {
                $new_parent_quantity -= $quantity;
            }
    
            $sql_parent = "UPDATE " . PREFIX . "product_attribute_value SET 
                                quantity = ?, 
                                updated_at = NOW(), 
                                updated_by_user_id = ? 
                            WHERE product_id = ? AND id = ?";
            $stmt_parent = $conn->prepare($sql_parent);
            $stmt_parent->bind_param("iiii", $new_parent_quantity, $user_id, $product_id, $parent_attribute_id);
            $stmt_parent->execute();
    
            if ($stmt_parent->affected_rows > 0) {
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

    public function getStockOptions($product_id) {
        global $conn;
    
        $sql = "SELECT pav.id, pav.attribute_id, pav.quantity, pav.parent_attribute_id, ap.product_id, apd.name 
        FROM " . PREFIX . "product_attribute_value pav
        INNER JOIN " . PREFIX . "product ap ON pav.product_id = ap.product_id
        INNER JOIN " . PREFIX . "product_description apd ON ap.product_id = apd.product_id";
        
        if ($product_id !== null) {
            $sql .= " WHERE pav.product_id = ?";
        }
    
        $stmt = $conn->prepare($sql);
    
        if ($product_id !== null) {
            $stmt->bind_param("i", $product_id);
        }   
    
        $stmt->execute();
        $result = $stmt->get_result();
    
        $options = array();
    
        while ($row = $result->fetch_assoc()) {
            if (!isset($options[$row['product_id']])) {
                $options[$row['product_id']] = array(
                    'product_id' => $row['product_id'],
                    'name' => $row['name'],
                    'options' => array()
                );
            }
    
            $option = array(
                'id' => $row['id'],
                'attribute_id' => $row['attribute_id'],
                'quantity' => $row['quantity'],
                'parent_attribute_id' => $row['parent_attribute_id']
            );
    
            $options[$row['product_id']]['options'][] = $option;
        }
    
        $stmt->close();        
    
        if (empty($options)) {
            return createResponse("Nenhuma opção de estoque encontrada para o produto especificado.", 404);
        } else {
            return createResponse(array_values($options), 200);
        }
    }  

    public function deleteStockOptions($user_id, $product_id, $ids, $attribute_id) {
        global $conn;

        foreach ($ids as $id) {
            if (!$this->stockOptionExists($product_id, $id, $attribute_id)) {
                return createResponse("Opção de estoque não encontrada para o produto especificado.", 404);
            }
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "DELETE FROM " . PREFIX . "product_attribute_value WHERE product_id = ? AND id IN ($placeholders) AND attribute_id = ?";
        $stmt = $conn->prepare($sql);

        $params = array_merge([$product_id], $ids, [$attribute_id]);
        $types = str_repeat('i', count($ids) + 2);

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        insertLog($user_id, "product_attribute_value_id=$product_id", "deleted");

        if ($stmt->affected_rows > 0) {
            return createResponse("Opções de estoque excluídas com sucesso.", 200);
        } else {
            return createResponse("Falha ao excluir as opções de estoque.", 500);
        }
    }

}
?>
