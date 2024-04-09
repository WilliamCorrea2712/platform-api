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
                return createResponse("Usuário já existe.", 400);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return createResponse("O formato do email é inválido.", 400);
            }

            if (strlen($password) < 6) {
                return createResponse("A senha deve ter pelo menos 6 caracteres.", 400);
            }

            $result = addUserWithToken($name, $password, $email);

            return createResponse($result, 200);
        } else {
            return createResponse("Dados incompletos.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function getUsers() {
    $users = getAllUsers();

    if (!empty($users)) {
        return createResponse($users, 200);
    } else {
        return createResponse("Nenhum usuário encontrado.", 404);
    }
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
                return createResponse("Você não tem permissão para editar este usuário.", 401);
            }

            $result = updateUser($id, $name, $email, $password);

            return createResponse($result, 200);

        } else {
            return createResponse("Dados incompletos.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function deleteUser($user_id){
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['id'])) {
            $id = $data['id'];

            if ((int)$id != $user_id) {
                return createResponse("Você não tem permissão para excluir este usuário.", 401);
            }

            $result = delUser($id);

            return createResponse($result, 200);
        } else {
            return createResponse("ID do usuário não fornecido.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function login() {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['email']) && isset($_POST['password'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $result = loginUser($email, $password);

            return createResponse($result, 200);
        } else {
            return createResponse("Dados incompletos.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}
?>
