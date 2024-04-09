<?php
require_once __DIR__ . "/../security/token.php"; 
require_once(__DIR__ . '/../config.php');
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
  );

  if (isset($_GET['route']) && isset($routes[$_GET['route']])) {
    $route = $_GET['route'];
    $handler = $routes[$route];

    if (strpos($route, "account/") === 0) {
      require_once __DIR__ . "/../controllers/account/account.php";
    } else if (strpos($route, "user/") === 0) {
      require_once __DIR__ . "/../controllers/user/user.php";
    } else if (strpos($route, "product/") === 0) {
      require_once __DIR__ . "/../controllers/product/product.php";
      require_once __DIR__ . "/../controllers/product/category.php";
      require_once __DIR__ . "/../controllers/product/brand.php";
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
    } else {
      $user_id = verifyToken()->user_id;

      if(isset($user_id) && $user_id > 0){
        $handler($user_id);
      } else {
        echo json_encode(array("message" => "Usuário nao autenticado."));
      }
    }
  } else {
    echo "Rota não encontrada!";
  }
?>
