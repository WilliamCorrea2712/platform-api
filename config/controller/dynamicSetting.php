<?php
require_once __DIR__ . "/../../config/model/dynamicSetting.php";
function addDynamicSetting($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['settings']) || !is_array($data['settings'])) {
            return createResponse("A estrutura de dados enviada é inválida.", 400);
        }

        $settings = $data['settings'];

        foreach ($settings as $setting) {
            if (!isset($setting['name']) || !isset($setting['value']) || !isset($setting['key']) || !isset($setting['group_name'])) {
                return createResponse("A estrutura de dados enviada é inválida.", 400);
            }

            if (settingExists($setting['key'], $setting['group_name'])) {
                return createResponse("A configuração '{$setting['key']}' no grupo '{$setting['group_name']}' já existe.", 400);
            }
        }

        foreach ($settings as $setting) {
            addSettingToDatabase($user_id, $setting['name'], $setting['value'], $setting['key'], $setting['group_name']);
        }

        return createResponse("Configurações adicionadas com sucesso.", 201);
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function getDynamicSetting($id, $key, $name, $group_name) {
    $settings = getAllSettingFromDatabase($id, $key, $name, $group_name);

    if (!empty($settings)) {
        return createResponse(array('settings' => $settings), 200); 
    } else {
        return createResponse(array('error' => "Nenhuma configuração encontrada."), 404);
    }
}

function editDynamicSetting($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "PATCH") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['setting_id']) && isset($data['value'])) {
            $setting_id = $data['setting_id'];
            $value = $data['value'];

            if (!settingExistsById($setting_id)) {
                return createResponse("Configuração não encontrada.", 404);
            }

            $result = updateSettingInDatabase($user_id, $setting_id, $value);

            return $result;
        } else {
            return createResponse("Parâmetros inválidos para edição da configuração.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}

function deleteDynamicSetting($user_id) {
    if ($_SERVER["REQUEST_METHOD"] == "DELETE") { 
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['setting_id'])) {
            $setting_id = $data['setting_id'];

            if (!settingExistsById($setting_id)) {
                return createResponse("Configuração não encontrada.", 404);
            }

            $result = deleteSettingFromDatabase($user_id, $setting_id);

            return $result;
        } else if (isset($data['key']) && isset($data['group_name'])) {
            $key = $data['key'];
            $group_name = $data['group_name'];

            if (!settingExistsByNameAndGroup($key, $group_name)) {
                return createResponse("Configuração não encontrada.", 404);
            }

            $result = deleteSettingFromDatabase($user_id, null, $key, $group_name);

            return $result;
        } else {
            return createResponse("Parâmetros inválidos para exclusão da configuração.", 400);
        }
    } else {
        return createResponse("Método não permitido.", 405);
    }
}


