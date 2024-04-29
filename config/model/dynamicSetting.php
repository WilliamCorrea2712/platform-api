<?php
require_once __DIR__ . '/../../mysql/conn.php';
require_once __DIR__ . '/../../global/logs.php';
require_once(__DIR__ . '/../../config.php');
function addSettingToDatabase($user_id, $name, $value, $group_name) {
    global $conn;

    $sql = "INSERT INTO " .PREFIX. "dynamic_setting (name, value, group_name, created_by_user_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $name, $value, $group_name, $user_id);
    $stmt->execute();
    
    $inserted_id = $stmt->insert_id;

    $stmt->close();

    return $inserted_id;
}

function getAllSettingFromDatabase($id, $name, $group_name) {
    global $conn;

    $sql = "SELECT id, name, group_name FROM api_dynamic_setting WHERE 1 = 1";

    if ($id !== null) {
        $sql .= " AND id = ?";
    }

    if ($name !== null) {
        $sql .= " AND name = ?";
    }

    if ($group_name !== null) {
        $sql .= " AND group_name = ?";
    }

    $stmt = $conn->prepare($sql);

    if ($id !== null && $name !== null && $group_name !== null) {
        $stmt->bind_param("iss", $id, $name, $group_name);
    } elseif ($id !== null && $name !== null) {
        $stmt->bind_param("is", $id, $name);
    } elseif ($id !== null && $group_name !== null) {
        $stmt->bind_param("is", $id, $group_name);
    } elseif ($name !== null && $group_name !== null) {
        $stmt->bind_param("ss", $name, $group_name);
    } elseif ($id !== null) {
        $stmt->bind_param("i", $id);
    } elseif ($name !== null) {
        $stmt->bind_param("s", $name);
    } elseif ($group_name !== null) {
        $stmt->bind_param("s", $group_name);
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

function deleteSettingFromDatabase($user_id, $setting_id = null, $name = null, $group_name = null) {
    global $conn;

    if ($setting_id !== null) {
        $sql = "DELETE FROM " . PREFIX . "dynamic_setting WHERE id = ? AND created_by_user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $setting_id, $user_id);
    } else {
        $sql = "DELETE FROM " . PREFIX . "dynamic_setting WHERE name = ? AND group_name = ? AND created_by_user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $group_name, $user_id);
    }

    if ($stmt->execute()) {
        return createResponse("Configuração excluída com sucesso.", 200);
    } else {
        return createResponse("Erro ao excluir configuração: " . $stmt->error, 500);
    }
}

function settingExists($name, $group_name) {
    global $conn;

    $sql = "SELECT COUNT(*) AS count FROM " . PREFIX . "dynamic_setting WHERE name = ? AND group_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $name, $group_name);
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

function settingExistsByNameAndGroup($name, $group_name) {
    global $conn;

    $sql = "SELECT id FROM " . PREFIX . "dynamic_setting WHERE name = ? AND group_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $name, $group_name);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();

    return $num_rows > 0;
}

?>
