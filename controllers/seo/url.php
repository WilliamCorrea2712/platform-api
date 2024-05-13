<?php
require_once __DIR__ . "/../../models/seo/url.php";

class ApiUrlController {
    public static function getUrl($value = null) {
        if (empty($value)) {
            return createResponse("O parâmetro 'url' não foi fornecido.", 400);
        }
    
        $ApiUrlModel = new ApiUrlModel();
        $result = $ApiUrlModel->getUrlData($value);
        
        if (!empty($result)) {
            return createResponse(array('data' => $result), 200); 
        } else {
            return createResponse(array('error' => "Nenhuma url encontrada."), 404);
        }
    }   
}
?>
