<?php
require_once __DIR__ . '/../mysql/conn.php';
require_once(__DIR__ . '/../config.php');

function insertLog($user_id, $entity_id_string) {
    global $conn;

    preg_match('/^(.+)_id=(\d+)$/', $entity_id_string, $matches);
    if (count($matches) !== 3) {
        return false;
    }
    $entity_type = $matches[1];
    $entity_id = $matches[2];

    $table_name = PREFIX . 'entity_logs';

    $sql = "INSERT INTO $table_name (entity_type, entity_id, action, user_id, timestamp) VALUES (?, ?, 'Deleted', ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $entity_type, $entity_id, $user_id);

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

?>
