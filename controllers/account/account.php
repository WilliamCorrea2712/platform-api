<?php
require_once __DIR__ . "/../../models/account/account.php";
require_once __DIR__ . '/../../global/helpers.php';

function addCustomer($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if ($data !== null && 
            array_key_exists('name', $data) && 
            array_key_exists('email', $data) && 
            array_key_exists('phone_number', $data) && 
            array_key_exists('birth_date', $data) && 
            array_key_exists('cnpj_cpf', $data) && 
            array_key_exists('rg_ie', $data) && 
            array_key_exists('type_person', $data) && 
            array_key_exists('sex', $data) &&
            array_key_exists('password', $data) &&
            array_key_exists('confirmPassword', $data)
        ) {
            $name = $data['name'];
            $email = $data['email'];
            $phone_number = $data['phone_number'];
            $birth_date = $data['birth_date'];
            $cnpj_cpf = $data['cnpj_cpf'];
            $rg_ie = $data['rg_ie'];
            $type_person = $data['type_person'];
            $sex = $data['sex'];
            $password = $data['password'];
            $confirmPassword = $data['confirmPassword'];

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return createResponse("O formato do email é inválido.", 400);
            }

            if (customerExistsByEmail($email)) {
                return createResponse("O cliente com o email fornecido já existe.", 400);
            }

            if (!preg_match("/^\(\d{2}\)\s\d{4,5}-\d{4}$/", $phone_number)) {
                return createResponse("O formato do número de telefone é inválido. O formato esperado é (XX) XXXX-XXXX.", 400);
            }

            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $birth_date) || !strtotime($birth_date)) {
                return createResponse("A data de nascimento é inválida. O formato esperado é YYYY-MM-DD.", 400);
            }

            if (!isValidCnpjCpf($cnpj_cpf)) {
                return createResponse("O CNPJ/CPF fornecido é inválido.", 400);
            }

            if (cpfExists($cnpj_cpf)) {
                return createResponse("O CNPJ/CPF fornecido já existe em outra conta", 400);
            }

            if (!isValidRgIe($rg_ie)) {
                return createResponse("O RG/IE fornecido é inválido.", 400);
            }

            if (!isValidTypePerson($type_person)) {
                return createResponse("O tipo de pessoa fornecido é inválido. Deve ser 'fisica' ou 'juridica'.", 400);
            }

            if (strlen($password) < 6) {
                return createResponse("A senha deve ter pelo menos 6 caracteres.", 400);
            } else {
                if ($password !== $confirmPassword) {
                    return createResponse("A senha e a confirmação de senha não coincidem.", 400);
                }
            }

            $result = addCustomerToDatabase($name, $email, $phone_number, $birth_date, $cnpj_cpf, $rg_ie, $type_person, $sex, $password, $user_id);
            return $result;
        } else {
            return createResponse("Dados incompletos.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function getCustomers($customer_id = null) {
    $result = getAllCustomers($customer_id);

    if (!empty($result)) {
        return createResponse(array('customers' => $result), 200); 
    } else {
        return createResponse(array('error' => "Nenhum cliente encontrado."), 404);
    }
}

function editCustomer($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "PATCH") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['customer_id'])) {
            $customer_id = $data['customer_id'];
            
            if (!customerExists($customer_id)) {
                return createResponse("Cliente não encontrado.", 404);
            }

            if (count($data) <= 1) {
                return createResponse("Nenhum dado a ser alterado foi fornecido.", 400);
            }

            $name = isset($data['name']) ? $data['name'] : null;
            $email = isset($data['email']) ? $data['email'] : null;
            $phone_number = isset($data['phone_number']) ? $data['phone_number'] : null;
            $birth_date = isset($data['birth_date']) ? $data['birth_date'] : null;
            $cnpj_cpf = isset($data['cnpj_cpf']) ? $data['cnpj_cpf'] : null;
            $rg_ie = isset($data['rg_ie']) ? $data['rg_ie'] : null;
            $type_person = isset($data['type_person']) ? $data['type_person'] : null;
            $sex = isset($data['sex']) ? $data['sex'] : null;
            $password = isset($data['password']) ? $data['password'] : null;
            $confirmPassword = isset($data['confirmPassword']) ? $data['confirmPassword'] : null;

            if ($password !== null && strlen($password) < 6) {
                return createResponse("A senha deve ter pelo menos 6 caracteres.", 400);
            } else {
                if ($password !== $confirmPassword) {
                    return createResponse("A senha e a confirmação de senha não coincidem.", 400);
                }
            }

            if ($phone_number !== null && !preg_match("/^\(\d{2}\)\s\d{4,5}-\d{4}$/", $phone_number)) {
                return createResponse("O formato do número de telefone é inválido. O formato esperado é (XX) XXXX-XXXX.", 400);
            }

            if ($birth_date !== null && (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $birth_date) || !strtotime($birth_date))) {
                return createResponse("A data de nascimento é inválida. O formato esperado é YYYY-MM-DD.", 400);
            }

            if ($cnpj_cpf !== null) {
                if (!isValidCnpjCpf($cnpj_cpf)) {
                    return createResponse("O CNPJ/CPF fornecido é inválido.", 400);
                }
            
                if (cpfExists($cnpj_cpf, $customer_id)) {
                    return createResponse("O CNPJ/CPF fornecido já existe em outra conta.", 400);
                }
            }

            if ($rg_ie !== null && !isValidRgIe($rg_ie)) {
                return createResponse("O RG/IE fornecido é inválido.", 400);
            }

            if ($type_person !== null && !isValidTypePerson($type_person)) {
                return createResponse("O tipo de pessoa fornecido é inválido. Deve ser 'fisica' ou 'juridica'.", 400);
            }

            if ($email !== null) {
                return createResponse("Não é permitido alterar o e-mail.", 400);
            }

            $result = editCustomerInDatabase($user_id, $customer_id, $name, $phone_number, $birth_date, $cnpj_cpf, $rg_ie, $type_person, $sex, $password);

            return $result;
        } else {
            return createResponse("O ID do cliente é obrigatório.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function deleteCustomer($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['customer_id'])) {
            $customer_id = $data['customer_id'];

            if (!customerExists($customer_id)) {
                return createResponse("Cliente não encontrado.", 404);
            }

            $result = deleteCustomerFromDatabase($user_id, $customer_id);

            if ($result['success']) {
                return createResponse("Cliente excluído com sucesso.", 200);
            } else {
                return createResponse("Erro ao excluir cliente: " . $result['error'], 500);
            }
        } else {
            return createResponse("ID do cliente não fornecido.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function addAddress($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if ($data !== null && 
            array_key_exists('customer_id', $data) &&
            array_key_exists('street', $data) &&
            array_key_exists('city', $data) &&
            array_key_exists('state', $data) &&
            array_key_exists('zip_code', $data) &&
            array_key_exists('name', $data) &&
            array_key_exists('number', $data) &&
            array_key_exists('country', $data)
        ) {
            $customer_id = $data['customer_id'];
            $street = $data['street'];
            $city = $data['city'];
            $state = $data['state'];
            $zip_code = $data['zip_code'];
            $name = $data['name'];
            $number = $data['number'];
            $country = $data['country'];

            if (!preg_match('/^\d{5}-\d{3}$/', $zip_code)) {
                return createResponse("Formato inválido para o CEP. O formato esperado é XXXXX-XX", 400);
            }

            $result = addAddressToCustomer($customer_id, $street, $city, $state, $zip_code, $name, $number, $country, $user_id);

            return createResponse($result, 200);
        } else {
            return createResponse("Dados incompletos.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function editAddress($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "PATCH") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['address_id'])) {
            $address_id = $data['address_id'];
            
            if (!itemExists("addresses", "id", $address_id)) {
                return createResponse("Endereço não encontrado.", 404);
            }

            if (count($data) <= 1) {
                return createResponse("Nenhum dado a ser alterado foi fornecido.", 400);
            }

            $street = isset($data['street']) ? $data['street'] : null;
            $city = isset($data['city']) ? $data['city'] : null;
            $state = isset($data['state']) ? $data['state'] : null;
            $zip_code = isset($data['zip_code']) ? $data['zip_code'] : null;
            $name = isset($data['name']) ? $data['name'] : null;
            $number = isset($data['number']) ? $data['number'] : null;
            $country = isset($data['country']) ? $data['country'] : null;

            if ($zip_code !== null && !preg_match('/^\d{5}-\d{3}$/', $zip_code)) {
                return createResponse("Formato inválido para o CEP. O formato esperado é XXXXX-XX", 400);
            }

            $result = editAddressInDatabase($user_id, $address_id, $street, $city, $state, $zip_code, $name, $number, $country);

            return $result;
        } else {
            return createResponse("O ID do endereço é obrigatório.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function deleteAddress($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['address_id'])) {
            $address_id = $data['address_id'];

            if (!itemExists("addresses", "id", $address_id)) {
                return createResponse("Endereço não encontrado.", 404);
            }

            $result = deleteAddressFromDatabase($user_id, $address_id);

            if ($result['success']) {
                return createResponse("Endereço excluído com sucesso.", 200);
            } else {
                return createResponse("Erro ao excluir endereço: " . $result['error'], 500);
            }
        } else {
            return createResponse("ID do endereço não fornecido.", 400);
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

            $result = loginCustomer($email, $password);

            return $result;
        } else {
            return createResponse("Dados incompletos.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}
?>
