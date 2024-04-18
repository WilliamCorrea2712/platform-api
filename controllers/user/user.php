<?php
require_once __DIR__ . '/../../models/user/user.php'; 
require_once __DIR__ . '/../../global/helpers.php';

function addUser() {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['name']) && isset($data['password']) && isset($data['email'])) {
            $name = $data['name'];
            $password = $data['password'];
            $email = $data['email'];

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

function getUsers($id = null) {
    $users = getAllUsers($id);

    if (!empty($users)) {
        return createResponse(array('users' => $users), 200); 
    } else {
        return createResponse(array('error' => "Nenhum usuário encontrado."), 404);
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
                
                if(strlen($password) < 6) {
                    return createResponse("A senha deve ter pelo menos 6 caracteres.", 401);
                }
            } else {
                $password = null;
            }

            /*if ((int)$id != $user_id) {
                return createResponse("Você não tem permissão para editar este usuário.", 401);
            }*/

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
        $postData = json_decode(file_get_contents('php://input'), true);

        if (isset($postData['email']) && isset($postData['password'])) {
            $email = $postData['email'];
            $password = $postData['password'];

            if (empty($email)) {
                return createResponse("O email é obrigatório!", 400);
            }
            if (empty($password)) {
                return createResponse("A senha é obrigatória!", 400);
            }

            $result = loginUser($email, $password);

            return $result;
        } else {
            return createResponse("Dados incompletos.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

?>
