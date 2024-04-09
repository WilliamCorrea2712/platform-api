<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../global/helpers.php';
require_once __DIR__ . "/../../mysql/conn.php";

function checkAndAddStockTypeOptions($type, $options) {
    $typeExists = checkStockTypeExists($type);
    if ($typeExists) {
        return createResponse("O tipo de estoque '$type' já existe.", 400);
    }

    global $conn;
    $conn->begin_transaction();

    $sql_insert_type = "INSERT INTO " . PREFIX . "stock_types (name) VALUES (?)";
    $stmt_insert_type = $conn->prepare($sql_insert_type);
    $stmt_insert_type->bind_param("s", $type);
    
    if (!$stmt_insert_type->execute()) {
        $conn->rollback();
        return createResponse("Erro ao adicionar tipo de estoque: " . $conn->error, 500);
    }

    $type_id = $stmt_insert_type->insert_id;

    $sql_insert_option = "INSERT INTO " . PREFIX . "stock_options (type_id, name) VALUES (?, ?)";
    $stmt_insert_option = $conn->prepare($sql_insert_option);
    $stmt_insert_option->bind_param("is", $type_id, $option);

    foreach ($options as $option) {
        if (!$stmt_insert_option->execute()) {
            $conn->rollback();
            return createResponse("Erro ao adicionar opção de estoque: " . $conn->error, 500);
        }
    }

    $conn->commit();
    $stmt_insert_type->close();
    $stmt_insert_option->close();
    $conn->close();

    return createResponse("Tipo e opções de estoque adicionados com sucesso.", 200);
}

function getAllStockOptions() {
    global $conn;

    $sql = "SELECT t.id AS type_id, t.name AS type_name, o.id AS option_id, o.name AS option_name
            FROM " . PREFIX . "stock_types t
            LEFT JOIN " . PREFIX . "stock_options o ON t.id = o.type_id
            ORDER BY t.id, o.id";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $stockOptions = array();
        $currentType = null;

        while ($row = $result->fetch_assoc()) {
            if ($row['type_id'] != $currentType) {
                $currentType = $row['type_id'];
                $stockOptions[] = array(
                    'id' => $row['type_id'],
                    'name' => $row['type_name'],
                    'options' => array()
                );
            }

            $stockOptions[count($stockOptions) - 1]['options'][] = array(
                'id' => $row['option_id'],
                'name' => $row['option_name']
            );
        }

        return $stockOptions;
    } else {
        return array();
    }
}

function deleteStockOptionsFromDatabase($type_id, $option_ids) {
    global $conn;

    $conn->begin_transaction();

    $deleted_type = false;
    $deleted_options = false;

    if ($type_id) {
        $sql_check_type = "SELECT id FROM " . PREFIX . "stock_types WHERE id = ?";
        $stmt_check_type = $conn->prepare($sql_check_type);
        $stmt_check_type->bind_param("i", $type_id);
        $stmt_check_type->execute();
        $result_check_type = $stmt_check_type->get_result();
        if ($result_check_type->num_rows == 0) {
            return array("response" => "Tipo de estoque com ID $type_id não encontrado.", "status" => 404);
        }
        $stmt_check_type->close();

        $sql_delete_type = "DELETE FROM " . PREFIX . "stock_types WHERE id = ?";
        $stmt_delete_type = $conn->prepare($sql_delete_type);
        $stmt_delete_type->bind_param("i", $type_id);
        if (!$stmt_delete_type->execute()) {
            $conn->rollback();
            return array("response" => "Erro ao excluir tipo de estoque: " . $conn->error, "status" => 500);
        }
        $deleted_type = true;
        $stmt_delete_type->close();
    }

    if (!empty($option_ids)) {
        $existing_options = array();
        $sql_check_options = "SELECT id FROM " . PREFIX . "stock_options WHERE id IN (". implode(',', array_fill(0, count($option_ids), '?')) . ")";
        $stmt_check_options = $conn->prepare($sql_check_options);
        $stmt_check_options->bind_param(str_repeat('i', count($option_ids)), ...$option_ids);
        $stmt_check_options->execute();
        $result_check_options = $stmt_check_options->get_result();
        while ($row = $result_check_options->fetch_assoc()) {
            $existing_options[] = $row['id'];
        }
        $stmt_check_options->close();

        if (count($existing_options) != count($option_ids)) {
            $conn->rollback();
            return array("response" => "Uma ou mais opções de estoque não existem.", "status" => 404);
        }

        $placeholders = str_repeat('?,', count($option_ids) - 1) . '?';
        $sql_delete_options = "DELETE FROM " . PREFIX . "stock_options WHERE id IN ($placeholders)";
        $stmt_delete_options = $conn->prepare($sql_delete_options);
        $stmt_delete_options->bind_param(str_repeat('i', count($option_ids)), ...$option_ids);
        if (!$stmt_delete_options->execute()) {
            $conn->rollback();
            return array("response" => "Erro ao excluir opções de estoque: " . $conn->error, "status" => 500);
        }
        $deleted_options = true;
        $stmt_delete_options->close();
    }

    $conn->commit();

    if ($deleted_type && $deleted_options) {
        return array("response" => "Tipo e opções de estoque excluídos com sucesso.", "status" => 200);
    } elseif ($deleted_type) {
        return array("response" => "Tipo de estoque excluído com sucesso.", "status" => 200);
    } elseif ($deleted_options) {
        return array("response" => "Opções de estoque excluídas com sucesso.", "status" => 200);
    } else {
        return array("response" => "Nada foi excluído.", "status" => 200);
    }
}

?>
