<?php
session_start();
require_once __DIR__ . "/../security/token.php"; 
require_once(__DIR__ . '/../config.php');
require_once __DIR__ . "/../global/helpers.php";
require_once __DIR__ . '/../rate_limit.php';
require_once __DIR__ . '/../bootstrap.php';

    $routes = array(
        USER . "addUser" => "addUser",
        USER . "editUser" => "editUser",
        USER . "deleteUser" => "deleteUser",
        USER . "getUsers" => "getUsers",
        USER . "login" => "login",
        ACCOUNT . "addCustomer" => "addCustomer",
        ACCOUNT . "editCustomer" => "editCustomer",
        ACCOUNT . "deleteCustomer" => "deleteCustomer",
        ACCOUNT . "getCustomers" => "getCustomers",
        ACCOUNT . "addAddress" => "addAddress",
        ACCOUNT . "editAddress" => "editAddress",
        ACCOUNT . "deleteAddress" => "deleteAddress",
        PRODUCT . "addProduct" => "ProductController::addProduct",
        PRODUCT . "editProduct" => "ProductController::editProduct",
        PRODUCT . "deleteProduct" => "ProductController::deleteProduct",
        PRODUCT . "addProductImages" => "ProductController::addProductImages",
        PRODUCT . "deleteProductImages" => "ProductController::deleteProductImages",
        PRODUCT . "getProducts" => "ProductController::getProducts",
        PRODUCT . "addCategory" => "CategoryController::addCategory",
        PRODUCT . "editCategory" => "CategoryController::editCategory",
        PRODUCT . "deleteCategory" => "CategoryController::deleteCategory",
        PRODUCT . "getCategories" => "CategoryController::getCategories",
        PRODUCT . "getProductsCategory" => "CategoryController::getProductsCategory",
        PRODUCT . "addBrand" => "BrandController::addBrand",
        PRODUCT . "editBrand" => "BrandController::editBrand",
        PRODUCT . "deleteBrand" => "BrandController::deleteBrand",
        PRODUCT . "getBrands" => "BrandController::getBrands",
        PRODUCT . "addStockOptions" => "addStockOptions",
        PRODUCT . "editStockOptions" => "editStockOptions",
        PRODUCT . "deleteStockOptions" => "deleteStockOptions",
        PRODUCT . "getStockOptions" => "getStockOptions",
        PRODUCT . "addProductList" => "addProductList",
        PRODUCT . "editProductList" => "editProductList",
        PRODUCT . "deleteProductList" => "deleteProductList",
        PRODUCT . "getAllProductLists" => "getAllProductLists",                
        CHECKOUT. "addCart" => "ShoppingCart::addCart", 
        CHECKOUT. "clearSession" => "ShoppingCart::clearSession", 
        CHECKOUT. "getProductsCart" => "ShoppingCart::getProductsCart",         
        CONFIG. "addDynamicSetting" => "addDynamicSetting",
        CONFIG. "editDynamicSetting" => "editDynamicSetting",
        CONFIG. "deleteDynamicSetting" => "deleteDynamicSetting",        
        CONFIG. "getDynamicSetting" => "getDynamicSetting",
        SEO. "getUrl" => "ApiUrlController::getUrl",
    );

    if (isset($_GET['route']) && isset($routes[$_GET['route']])) {
        $route = $_GET['route'];
        $handler = $routes[$route];

        $user_id = verifyToken()->user_id;

        if (strpos($route, "account/") === 0) {
            require_once __DIR__ . "/../controllers/account/account.php";
        } else if (strpos($route, "user/") === 0) {
            require_once __DIR__ . "/../controllers/user/user.php";
        } else if (strpos($route, "product/") === 0) {
            require_once __DIR__ . "/../controllers/product/product.php";
            require_once __DIR__ . "/../controllers/product/category.php";
            require_once __DIR__ . "/../controllers/product/brand.php";
            require_once __DIR__ . "/../controllers/product/stock.php";
            require_once __DIR__ . "/../controllers/product/listProducts.php";
        } else if (strpos($route, "config/") === 0) {
            require_once __DIR__ . "/../config/controller/dynamicSetting.php";
        } else if (strpos($route, "checkout/") === 0) {
            require_once __DIR__ . "/../checkout/cart.php";
        }else if (strpos($route, "seo/") === 0) {
            require_once __DIR__ . "/../controllers/seo/url.php";
        }

        if(strpos($route, "user/addUser") !== false){
            $handler();
        } else if(strpos($route, "account/getCustomers") !== false || 
                strpos($route, "product/getCategories") !== false || 
                strpos($route, "product/getBrands") !== false ||
                strpos($route, "product/getProducts") !== false ||
                strpos($route, "product/getAllProductLists") !== false ||
                strpos($route, "user/getUsers") !== false){

            if (isset($_GET['id'])) {
                $id = $_GET['id'];
            } else {
                $id = null;
            }            
            if (isset($_GET['parent_id'])) {
                $parent_id = $_GET['parent_id'];
            } else {
                $parent_id = null;
            }
            $handler($id, $parent_id);  
        } else if(strpos($route, "seo/getUrl") !== false){
            if (isset($_GET['url'])) {
                $handler($_GET['url']); 
            } else {
                $handler(); 
            }             
        } else if(strpos($route, "config/getDynamicSetting") !== false){                    
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
            } else {
                $id = null;
            }            
            if (isset($_GET['key'])) {
                $key = $_GET['key'];
            } else {
                $key = null;
            }      
            if (isset($_GET['name'])) {
                $name = $_GET['name'];
            } else {
                $name = null;
            }        
            if (isset($_GET['group_name'])) {
                $group_name = $_GET['group_name'];
            } else {
                $group_name = null;
            }
            $handler($id, $key, $name, $group_name);
        } else if(strpos($route, "checkout/getProductsCart") !== false || strpos($route, "checkout/clearSession") !== false){
            if (isset($_GET['session_id'])){
                $handler($user_id, $_GET['session_id']);
            } else{
                return createResponse("O 'session_id' é obrigatória!", 400);
            }
        } else {
            if(isset($user_id) && $user_id > 0){
                $handler($user_id);
            } else {
                return createResponse("Usuário não autenticado!", 400);
            }
        }
    } else {
        return createResponse("Rota não encontrada!", 400);
    }
?>
