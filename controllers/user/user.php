<?php
require_once __DIR__ . '/../../models/user/user.php'; 
require_once __DIR__ . '/../../global/helpers.php';

function addUser() {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['name']) && isset($_POST['password']) && isset($_POST['email'])) {
            $name = $_POST['name'];
            $password = $_POST['password'];
            $email = $_POST['email'];

            if (userExists($name, $email)) {
                http_response_code(400);
                echo json_encode(array("error" => "Usuário já existe."), JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(array("message" => "O formato do email é inválido."), JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (strlen($password) < 6) {
                http_response_code(400);
                echo json_encode(array("message" => "A senha deve ter pelo menos 6 caracteres."), JSON_UNESCAPED_UNICODE);
                exit;
            }

            $result = addUserWithToken($name, $password, $email);

            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Dados incompletos."), JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
    }
}

function getUsers() {
    return getAllUsers();
}

function editUser($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "PATCH") { 
        $data = json_decode(file_get_contents("php://input"), true);
        if (isset($data['id']) && isset($data['name']) && isset($data['email'])) {
            $id = $data['id'];
            $name = $data['name'];
            $email = $data['email'];

            if(isset($data['password'])){
                $password = $data['password'];
            } else {
                $password = null;
            }

            if ((int)$id != $user_id) {
                http_response_code(401);
                echo json_encode(array("message" => "Você não tem permissão para editar este usuário."), JSON_UNESCAPED_UNICODE);
                exit;
            }

            $result = updateUser($id, $name, $email, $password);

            http_response_code(200);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Dados incompletos."), JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
    }
}

function deleteUser($user_id){
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['id'])) {
            $id = $data['id'];

            if ((int)$id != $user_id) {
                http_response_code(401);
                echo json_encode(array("message" => "Você não tem permissão para excluir este usuário."), JSON_UNESCAPED_UNICODE);
                exit;
            }

            $result = delUser($id);

            http_response_code(200);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID do usuário não fornecido."), JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
    }
}

function login() {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['email']) && isset($_POST['password'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $result = loginUser($email, $password);

            http_response_code(200);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Dados incompletos."), JSON_UNESCAPED_UNICODE);
        }
    } else {
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido."), JSON_UNESCAPED_UNICODE);
    }
}
?>
