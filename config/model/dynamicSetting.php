<?php
require_once __DIR__ . '/../../mysql/conn.php';
require_once __DIR__ . '/../../global/logs.php';
require_once(__DIR__ . '/../../config.php');

function addSettingToDatabase($user_id, $name, $value, $key, $group_name) {
    global $conn;

    $sql = "INSERT INTO " . PREFIX . "dynamic_setting (name, value, `key`, group_name, created_by_user_id) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ssssi", $name, $value, $key, $group_name, $user_id);
    $stmt->execute();
    
    $inserted_id = $stmt->insert_id;

    $stmt->close();

    return $inserted_id;
}

function getAllSettingFromDatabase($id, $key, $name, $group_name) {
    global $conn;

    $sql = "SELECT id, name, value, `key`, group_name FROM api_dynamic_setting WHERE 1 = 1";

    $params = array();

    if ($id !== null) {
        $sql .= " AND id = ?";
        $params[] = $id;
    }

    if ($key !== null) {
        $sql .= " AND `key` = ?";
        $params[] = $key;
    }

    if ($name !== null) {
        $sql .= " AND name = ?";
        $params[] = $name;
    }

    if ($group_name !== null) {
        $sql .= " AND group_name = ?";
        $params[] = $group_name;
    }

    $sql .= " ORDER BY group_name";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return createResponse("Erro na preparação da declaração SQL: " . $conn->error, 500);
    }

    if (!empty($params)) {
        $bind_types = str_repeat("s", count($params));
        $stmt->bind_param($bind_types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $settings = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $settings[] = $row;
        }
    }

    $stmt->close();
    $conn->close();

    return $settings;
}

function updateSettingInDatabase($user_id, $setting_id, $value){
    global $conn;

    $sql = "UPDATE " . PREFIX . "dynamic_setting SET value = ? WHERE id = ? ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $value, $setting_id);

    if ($stmt->execute()) {
        return createResponse("Configuração alterada com sucesso.", 200);
    } else {
        return createResponse("Erro ao editar configuração: " . $stmt->error, 500);
    }
}

function deleteSettingFromDatabase($user_id, $setting_id = null, $key = null, $group_name = null) {
    global $conn;

    if ($setting_id !== null) {
        $sql = "DELETE FROM " . PREFIX . "dynamic_setting WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $setting_id);
        insertLog($user_id, "setting_id=$setting_id", "deleted");
    } else {
        $sql = "DELETE FROM " . PREFIX . "dynamic_setting WHERE `key` = ? AND group_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $key, $group_name);
        insertLog($user_id, "setting_id-key-groupName=$key+$group_name", "deleted");
    }

    if ($stmt->execute()) {
        return createResponse("Configuração excluída com sucesso.", 200);
    } else {
        return createResponse("Erro ao excluir configuração: " . $stmt->error, 500);
    }
}

function settingExists($key, $group_name) {
    global $conn;

    $sql = "SELECT COUNT(*) AS count FROM " . PREFIX . "dynamic_setting WHERE `key` = ? AND group_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $key, $group_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = $row['count'];

    $stmt->close();

    return $count > 0;
}

function settingExistsById($setting_id) {
    global $conn;

    $sql = "SELECT id FROM " . PREFIX . "dynamic_setting WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $setting_id);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();

    return $num_rows > 0;
}

function settingExistsByNameAndGroup($key, $group_name) {
    global $conn;

    $sql = "SELECT id FROM " . PREFIX . "dynamic_setting WHERE `key` = ? AND group_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $key, $group_name);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();

    return $num_rows > 0;
}

?>
