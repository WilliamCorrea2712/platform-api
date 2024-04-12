<?php
require_once __DIR__ . "/../security/token.php"; 
require_once(__DIR__ . '/../config.php');
require_once __DIR__ . "/../global/helpers.php";

date_default_timezone_set('America/Sao_Paulo');

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
        PRODUCT . "addProduct" => "addProduct",
        PRODUCT . "editProduct" => "editProduct",
        PRODUCT . "deleteProduct" => "deleteProduct",
        PRODUCT . "addProductImages" => "addProductImages",
        PRODUCT . "deleteProductImages" => "deleteProductImages",
        PRODUCT . "getProducts" => "getProducts",
        PRODUCT . "addCategory" => "addCategory",
        PRODUCT . "editCategory" => "editCategory",
        PRODUCT . "deleteCategory" => "deleteCategory",
        PRODUCT . "getCategories" => "getCategories",
        PRODUCT . "addBrand" => "addBrand",
        PRODUCT . "editBrand" => "editBrand",
        PRODUCT . "deleteBrand" => "deleteBrand",
        PRODUCT . "getBrands" => "getBrands",
        PRODUCT . "addStockOptions" => "addStockOptions",
        PRODUCT . "editStockOptions" => "editStockOptions",
        PRODUCT . "deleteStockOptions" => "deleteStockOptions",
        PRODUCT . "getStockOptions" => "getStockOptions",
        CHECKOUT. "addCart" => "ShoppingCart::addCart", 
        CHECKOUT. "clearSession" => "ShoppingCart::clearSession", 
        CHECKOUT. "getProductsCart" => "ShoppingCart::getProductsCart",
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
        } else if (strpos($route, "checkout/") === 0) {
            require_once __DIR__ . "/../checkout/cart.php";
        }

        if(strpos($route, "user/addUser") !== false){
            $handler();
        } else if(strpos($route, "account/getCustomers") !== false || 
                strpos($route, "product/getCategories") !== false || 
                strpos($route, "product/getBrands") !== false ||
                strpos($route, "product/getProducts") !== false){

            if (isset($_GET['id'])){
                $handler($_GET['id']);
            } else{
                $handler();
            }
        } else if(strpos($route, "checkout/getProductsCart") !== false || strpos($route, "checkout/clearSession") !== false){
            if (isset($_GET['session_id'])){
                $handler($user_id, $_GET['session_id']);
            } else{
                $handler();
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
