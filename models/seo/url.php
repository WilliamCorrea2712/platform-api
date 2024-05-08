<?php
require_once __DIR__ . "/../../mysql/conn.php";
require_once(__DIR__ . '/../../config.php');

class ApiUrlModel {
    private $conn;

    public function __construct() {
        $this->conn = $GLOBALS['conn'];
    }

    public function valid($name) {
        $formatted_value = $this->formatUrlValue($name);
        $url_exists = $this->checkUrlExist($formatted_value);

        if ($url_exists !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function createUrl($key, $id, $value) {
        $formatted_value = $this->formatUrlValue($value);
    
        if ($formatted_value === false) {
            return array("error" => "Formato Inválido.");
        }
    
        $url_exists = $this->checkUrlExist($formatted_value);
    
        if ($url_exists !== false) {
            return array("error" => "URL já existe.");
        }
    
        $sql = "INSERT INTO " . PREFIX . "urls (`key`, id, `value`) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
    
        if (!$stmt) {
            return array("error" => "Erro ao preparar a declaração SQL: " . $this->conn->error);
        }
    
        $stmt->bind_param("sis", $key, $id, $formatted_value);
        $stmt->execute();
    
        if ($stmt->affected_rows > 0) {
            return array("success" => "URL criada com sucesso.");
        } else {
            return array("error" => "Erro ao criar a URL: " . $this->conn->error);
        }
    }       

    private function formatUrlValue($value) {
        $formatted_value = preg_replace('/[áàãâä]/u', 'a', $value);
        $formatted_value = preg_replace('/[éèêë]/u', 'e', $formatted_value);
        $formatted_value = preg_replace('/[íìîï]/u', 'i', $formatted_value);
        $formatted_value = preg_replace('/[óòõôö]/u', 'o', $formatted_value);
        $formatted_value = preg_replace('/[úùûü]/u', 'u', $formatted_value);
        $formatted_value = preg_replace('/[ç]/u', 'c', $formatted_value);
        $formatted_value = preg_replace('/[^a-z0-9]+/i', '-', $formatted_value);
        $formatted_value = preg_replace('/-+/', '-', $formatted_value);
        $formatted_value = trim($formatted_value, '-');
        $formatted_value = strtolower($formatted_value);
            
        if (empty($formatted_value)) {
            return false;
        }
    
        return $formatted_value;
    }

    private function checkUrlExist($formatted_value) {
        $sql = "SELECT url_id FROM " . PREFIX . "urls WHERE `value` = ?";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return array("error" => "Erro ao preparar a declaração SQL: " . $this->conn->error);
        }
    
        $stmt->bind_param("s", $formatted_value);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['url_id'];
        } else {
            return false;
        }
    }

    public function getUrlValue($key, $id) {
        $sql = "SELECT `value` FROM " . PREFIX . "urls WHERE `key` = ? AND id = ?";
        $stmt = $this->conn->prepare($sql);
    
        if (!$stmt) {
            return array("error" => "Erro ao preparar a declaração SQL: " . $this->conn->error);
        }
    
        $stmt->bind_param("si", $key, $id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['value'];
        } else {
            return '';
        }
    }    

    public function deleteUrl($key, $id) {
        $sql = "DELETE FROM " . PREFIX . "urls WHERE `key` = ? AND id = ?";
        $stmt = $this->conn->prepare($sql);
    
        if (!$stmt) {
            return array("error" => "Erro ao preparar a declaração SQL: " . $this->conn->error);
        }
    
        $stmt->bind_param("si", $key, $id);
        $stmt->execute();
    
        if ($stmt->affected_rows > 0) {
            return array("success" => "URL deletada com sucesso.");
        } else {
            return array("error" => "Erro ao deletar a URL: " . $this->conn->error);
        }
    }
}
?>
