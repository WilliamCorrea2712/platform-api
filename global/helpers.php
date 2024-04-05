<?php
require_once __DIR__ . '/../mysql/conn.php';

function existsInTable($table, $column, $value) {
    global $conn;

    $sql = "SELECT COUNT(*) AS total FROM $table WHERE $column = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] > 0;
}

function customerExists($customer_id) {
    return existsInTable('api_customers', 'id', $customer_id);
}

function customerExistsByEmail($email) {
    return existsInTable('api_customers', 'email', $email);
}

function userExists($name, $email) {
    return existsInTable('api_user', 'name', $name) || userEmailExists($email);
}

function userEmailExists($email) {
    return existsInTable('api_user', 'email', $email);
}
?>
