<?php
require_once __DIR__ . '/../mysql/conn.php';
require_once(__DIR__ . '/../config.php');

function insertLog($user_id, $customer_id) {
  global $conn;

  $sql = "INSERT INTO " . PREFIX . "delete_customer (customer_id, deleted_by_user_id, deleted_at) VALUES (?, ?, NOW())";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $customer_id, $user_id);

  if ($stmt->execute()) {
      return true;
  } else {
      return false;
  }
}
?>
